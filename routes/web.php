<?php

declare(strict_types=1);

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Middleware\EnsureReadOnlyTenantMode;
use App\Http\Middleware\EnsureTwoFactorChallengeCompleted;
use App\Http\Middleware\HealthCheckRateLimit;
use App\Http\Middleware\SetCurrentTenantContext;
use App\Http\Responses\Auth\AuthFailureResponse;
use App\Http\Responses\Auth\LoginResponse;
use App\Http\Responses\Auth\PasswordResetLinkSentResponse;
use App\Http\Responses\Auth\PasswordResetResponse;
use App\Http\Responses\Auth\TwoFactorLoginResponse;
use App\Livewire\Pages\App\HomePage;
use App\Livewire\Pages\Auth\ForgotPasswordPage;
use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\Auth\ResetPasswordPage;
use App\Livewire\Pages\Auth\TwoFactorChallengePage;
use App\Livewire\Pages\Settings\TenantPage;
use App\Livewire\Ping;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Auth\LoginAuditRecorder;
use App\Support\Auth\PostgresAuthContext;
use App\Support\Auth\RecoveryCodeHasher;
use App\Support\Auth\TenantAccessResolver;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/app');
    }

    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/login', LoginPage::class)->name('auth.login');

    Route::post('/auth/login', function (
        Request $request,
        TenantAccessResolver $resolver,
        LoginAuditRecorder $auditRecorder,
        PostgresAuthContext $postgresAuthContext,
    ) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $loginKey = mb_strtolower((string) $credentials['email']).'|'.$request->ip();
        $rateKey = 'auth:login:window:'.$loginKey;
        $lockoutKey = 'auth:login:lockout:'.$loginKey;
        if (RateLimiter::tooManyAttempts($lockoutKey, 10) || RateLimiter::tooManyAttempts($rateKey, 5)) {
            $auditRecorder->record($request, 'auth.login.locked_out');

            return AuthFailureResponse::make($request, 'Muitas tentativas. Tente novamente mais tarde.', 429);
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('email', (string) $credentials['email'])
            ->first();
        static $dummyPasswordHash = null;
        $passwordHash = $user?->password;
        if (! is_string($passwordHash) || $passwordHash === '') {
            $dummyPasswordHash ??= Hash::make(Str::random(32));
            $passwordHash = $dummyPasswordHash;
        }

        if (! Hash::check((string) $credentials['password'], $passwordHash) || $user === null) {
            if ($user !== null) {
                $postgresAuthContext->forUser($user->id);
                $user->loadMissing('primaryTenantUser');
            }

            RateLimiter::hit($rateKey, 900);
            RateLimiter::hit($lockoutKey, 3600);
            $auditRecorder->record(
                $request,
                'auth.login.failed',
                $user?->id,
                $user?->primaryTenantUser?->tenant_id,
            );

            return AuthFailureResponse::make($request, 'Credenciais invalidas.', 422);
        }

        $postgresAuthContext->forUser($user->id);
        $decision = $resolver->resolve($user);
        $postgresAuthContext->forTenant($decision['tenant_id']);

        if (! $decision['allowed']) {
            RateLimiter::clear($rateKey);
            RateLimiter::clear($lockoutKey);
            $auditRecorder->record(
                $request,
                $decision['event'],
                $user->id,
                $decision['tenant_id'],
            );

            return AuthFailureResponse::make($request, 'Acesso indisponivel para esta conta.', 403);
        }

        if ($decision['requires_two_factor']) {
            RateLimiter::clear($rateKey);
            RateLimiter::clear($lockoutKey);
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put([
                'auth.two_factor_pending' => true,
                'auth.two_factor_user_id' => $user->id,
                'auth.two_factor_tenant_id' => $decision['tenant_id'],
                'auth.two_factor_tenant_user_id' => $decision['tenant_user_id'],
                'auth.two_factor_access_mode' => $decision['access_mode'],
            ]);

            return redirect('/auth/two-factor-challenge');
        }

        RateLimiter::clear($rateKey);
        RateLimiter::clear($lockoutKey);
        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();
        $request->session()->put('tenant.access_mode', $decision['access_mode']);

        $auditRecorder->record(
            $request,
            $decision['event'],
            $user->id,
            $decision['tenant_id'],
        );

        return (new LoginResponse)->toResponse($request);
    });

    Route::get('/auth/forgot-password', ForgotPasswordPage::class)->name('auth.password.request');

    Route::post('/auth/forgot-password', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::broker()->sendResetLink($credentials);

        return (new PasswordResetLinkSentResponse)->toResponse($request);
    })->name('auth.password.email');

    Route::get('/auth/reset-password/{token}', function (Request $request, string $token) {
        $request->session()->put('auth.password_reset_token', $token);

        return redirect('/auth/reset-password');
    })->name('auth.password.reset');

    Route::get('/auth/reset-password', ResetPasswordPage::class)->name('auth.password.reset.form');

    Route::post('/auth/reset-password', function (Request $request) {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $token = (string) $request->session()->get(
            'auth.password_reset_token',
            (string) $request->input('token', ''),
        );

        if ($token === '') {
            return AuthFailureResponse::make($request, 'Token invalido ou expirado. Solicite novo link.', 422, 'token');
        }

        $status = Password::broker()->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => $token,
            ],
            static function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return AuthFailureResponse::make($request, 'Token invalido ou expirado. Solicite novo link.', 422, 'token');
        }

        $request->session()->forget('auth.password_reset_token');

        return (new PasswordResetResponse)->toResponse($request);
    })->name('auth.password.update');
});

Route::get('/auth/two-factor-challenge', TwoFactorChallengePage::class)
    ->name('auth.two-factor');

Route::post('/auth/two-factor-challenge', function (
    Request $request,
    LoginAuditRecorder $auditRecorder,
    TenantAccessResolver $resolver,
    PostgresAuthContext $postgresAuthContext,
    TwoFactorAuthenticationProviderContract $twoFactorProvider,
) {
    $tenantId = $request->session()->has('auth.two_factor_tenant_id')
        ? (int) $request->session()->get('auth.two_factor_tenant_id')
        : null;
    if ($tenantId !== null && ! Tenant::query()->whereKey($tenantId)->exists()) {
        $tenantId = null;
    }

    $recordFailedAttempt = static function (?User $user = null) use ($auditRecorder, $request, $tenantId): void {
        $auditRecorder->record($request, 'auth.two_factor.failed', $user?->id, $tenantId);
    };
    $clearTwoFactorChallenge = static function () use ($request): void {
        $request->session()->forget([
            'auth.two_factor_pending',
            'auth.two_factor_user_id',
            'auth.two_factor_tenant_id',
            'auth.two_factor_tenant_user_id',
            'auth.two_factor_access_mode',
        ]);
    };

    if ($request->session()->get('auth.two_factor_pending') !== true) {
        $recordFailedAttempt();

        return AuthFailureResponse::make($request, 'Desafio de dois fatores nao encontrado.', 422, 'code');
    }

    $request->validate([
        'code' => ['nullable', 'string', 'required_without:recovery_code'],
        'recovery_code' => ['nullable', 'string', 'required_without:code'],
    ]);

    $rateKey = 'auth:two-factor:'.$request->session()->get('auth.two_factor_user_id').'|'.$request->ip();
    if (RateLimiter::tooManyAttempts($rateKey, 5)) {
        $recordFailedAttempt();

        return AuthFailureResponse::make($request, 'Muitas tentativas. Tente novamente mais tarde.', 429, 'code');
    }

    $userId = (int) $request->session()->get('auth.two_factor_user_id');

    /** @var User|null $user */
    $user = User::query()->find($userId);
    if ($user === null) {
        RateLimiter::hit($rateKey, 60);
        $recordFailedAttempt();
        Auth::logout();
        $clearTwoFactorChallenge();
        $request->session()->regenerate();

        return AuthFailureResponse::make($request, 'Codigo invalido.', 422, 'code');
    }

    $postgresAuthContext->forUser($user->id);
    $postgresAuthContext->forTenant($tenantId);
    $accessDecision = $resolver->resolve($user);
    $pendingTenantUserId = $request->session()->has('auth.two_factor_tenant_user_id')
        ? (int) $request->session()->get('auth.two_factor_tenant_user_id')
        : null;
    $bindingChanged = $pendingTenantUserId !== null
        && $accessDecision['tenant_user_id'] !== $pendingTenantUserId;
    $tenantChanged = $accessDecision['tenant_id'] !== $tenantId;

    if (! $accessDecision['allowed'] || $tenantChanged || $bindingChanged) {
        $event = $accessDecision['allowed'] ? 'auth.login.blocked_binding_status' : $accessDecision['event'];
        $auditRecorder->record(
            $request,
            $event,
            $user->id,
            $tenantId ?? $accessDecision['tenant_id'],
        );
        Auth::logout();
        $clearTwoFactorChallenge();
        $request->session()->regenerate();

        return AuthFailureResponse::make($request, 'Acesso indisponivel para esta conta.', 403, 'code');
    }

    $recoveryCode = (string) $request->input('recovery_code', '');
    if ($recoveryCode !== '') {
        $recoveryCodes = $user->two_factor_recovery_codes;
        $matchedRecoveryCode = is_array($recoveryCodes)
            ? RecoveryCodeHasher::matchingHash($recoveryCode, $recoveryCodes)
            : null;

        if (! is_array($recoveryCodes) || $matchedRecoveryCode === null) {
            RateLimiter::hit($rateKey, 60);
            $recordFailedAttempt($user);

            return AuthFailureResponse::make($request, 'Codigo invalido.', 422, 'recovery_code');
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_filter(
                $recoveryCodes,
                static fn (mixed $code): bool => $code !== $matchedRecoveryCode
            )),
        ])->save();

        RateLimiter::clear($rateKey);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('tenant.access_mode', $accessDecision['access_mode']);
        $clearTwoFactorChallenge();

        $auditRecorder->record($request, 'auth.two_factor.recovery_code_used', $user->id, $tenantId);

        return (new TwoFactorLoginResponse)->toResponse($request);
    }

    $code = trim((string) $request->input('code', ''));
    if ($code === '') {
        RateLimiter::hit($rateKey, 60);
        $recordFailedAttempt($user);

        return AuthFailureResponse::make($request, 'Codigo invalido.', 422, 'code');
    }

    $encryptedSecret = $user->two_factor_secret;
    if (! is_string($encryptedSecret) || $encryptedSecret === '') {
        RateLimiter::hit($rateKey, 60);
        $recordFailedAttempt($user);

        return AuthFailureResponse::make($request, 'Codigo invalido.', 422, 'code');
    }

    try {
        $secret = decrypt($encryptedSecret);
    } catch (DecryptException) {
        RateLimiter::hit($rateKey, 60);
        $recordFailedAttempt($user);

        return AuthFailureResponse::make($request, 'Codigo invalido.', 422, 'code');
    }

    if (! $twoFactorProvider->verify((string) $secret, $code)) {
        RateLimiter::hit($rateKey, 60);
        $recordFailedAttempt($user);

        return AuthFailureResponse::make($request, 'Codigo invalido.', 422, 'code');
    }

    RateLimiter::clear($rateKey);
    Auth::login($user);
    $request->session()->regenerate();
    $request->session()->put('tenant.access_mode', $accessDecision['access_mode']);
    $clearTwoFactorChallenge();

    $auditRecorder->record($request, 'auth.two_factor.success', $user->id, $tenantId);

    return (new TwoFactorLoginResponse)->toResponse($request);
})->name('auth.two-factor.store');

Route::middleware([
    'auth',
    EnsureTwoFactorChallengeCompleted::class,
    SetCurrentTenantContext::class,
    EnsureReadOnlyTenantMode::class,
])
    ->group(function (): void {
        Route::get('/app', HomePage::class)->name('app.home');
        Route::get('/settings/tenant', TenantPage::class)->name('settings.tenant');
        Route::post('/settings/tenant', TenantSettingsController::class)->name('settings.tenant.store');
    });

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);

if (! app()->environment('production')) {
    Route::get('/ping', Ping::class);
}
