<?php

declare(strict_types=1);

use App\Exceptions\LgpdBaseLegalAusenteException;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Middleware\EnsureReadOnlyTenantMode;
use App\Http\Middleware\EnsureTwoFactorChallengeCompleted;
use App\Http\Middleware\HealthCheckRateLimit;
use App\Http\Middleware\RequireTwoFactorSession;
use App\Http\Middleware\SetCurrentTenantContext;
use App\Http\Responses\Auth\AuthFailureResponse;
use App\Http\Responses\Auth\LoginResponse;
use App\Http\Responses\Auth\PasswordResetLinkSentResponse;
use App\Http\Responses\Auth\PasswordResetResponse;
use App\Http\Responses\Auth\TwoFactorLoginResponse;
use App\Livewire\Pages\App\HomePage;
use App\Livewire\Pages\Auth\AcceptInvitationPage;
use App\Livewire\Pages\Auth\ForgotPasswordPage;
use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\Auth\ResetPasswordPage;
use App\Livewire\Pages\Auth\TwoFactorChallengePage;
use App\Livewire\Pages\Privacy\RevokeConsentPage;
use App\Livewire\Pages\Settings\PlansPage;
use App\Livewire\Pages\Settings\TenantPage;
use App\Livewire\Pages\Settings\UsersPage;
use App\Livewire\Ping;
use App\Livewire\Settings\ConsentSubjectsPage;
use App\Livewire\Settings\LgpdCategoriesPage;
use App\Mail\RevocationConfirmationMail;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ConsentRecordService;
use App\Services\RevocationTokenService;
use App\Support\Auth\LoginAuditRecorder;
use App\Support\Auth\PostgresAuthContext;
use App\Support\Auth\RecoveryCodeHasher;
use App\Support\Auth\TenantAccessResolver;
use App\Support\Lgpd\LgpdCategoryService;
use App\Support\Settings\UserInvitationService;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    Route::get('/auth/invitations/{token}', AcceptInvitationPage::class)->name('auth.invitations.accept');

    Route::post('/auth/invitations/{token}', function (
        Request $request,
        string $token,
        UserInvitationService $service,
    ) {
        try {
            $service->accept($token, $request->only(['password', 'password_confirmation']));
        } catch (ValidationException $exception) {
            if (array_key_exists('token', $exception->errors())) {
                return response('Convite invalido. Solicite novo convite.', 404);
            }

            throw $exception;
        }

        return redirect('/auth/login')->with('status', 'Convite aceito.');
    })->name('auth.invitations.store');
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
        Route::get('/settings/users', UsersPage::class)->name('settings.users');
        Route::get('/settings/plans', PlansPage::class)->name('settings.plans');
    });

// ---------------------------------------------------------------------------
// Slice 010 — LGPD: rotas autenticadas + 2FA
// ---------------------------------------------------------------------------

// GET /settings/privacy requer 2FA completado na sessão corrente
Route::middleware([
    'auth',
    EnsureTwoFactorChallengeCompleted::class,
    RequireTwoFactorSession::class,
    SetCurrentTenantContext::class,
    EnsureReadOnlyTenantMode::class,
])
    ->group(function (): void {
        Route::get('/settings/privacy', LgpdCategoriesPage::class)->name('settings.privacy');
    });

Route::middleware([
    'auth',
    EnsureTwoFactorChallengeCompleted::class,
    SetCurrentTenantContext::class,
    EnsureReadOnlyTenantMode::class,
])
    ->group(function (): void {
        Route::post('/settings/privacy/lgpd-categories', function (
            Request $request,
            CurrentTenantResolver $resolver,
            LgpdCategoryService $service,
        ) {
            $user = $request->user();
            if (! $user instanceof User) {
                abort(403);
            }
            $context = $resolver->resolve($user);
            try {
                $service->declare($context['tenant'], $user, $request->all());
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors());
            }

            return redirect()->back()->with('status', 'Base legal registrada.');
        })->name('settings.privacy.lgpd-categories.store');

        Route::get('/settings/privacy/consentimentos', ConsentSubjectsPage::class)->name('settings.privacy.consents');
    });

// POST consentimentos fora do grupo SetCurrentTenantContext para capturar tenant suspenso como 422
Route::middleware(['auth', EnsureTwoFactorChallengeCompleted::class])
    ->post('/settings/privacy/consentimentos', function (
        Request $request,
        CurrentTenantResolver $resolver,
        ConsentRecordService $service,
    ) {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $context = $resolver->resolve($user);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'Conta suspenso. Operacao nao permitida.',
            ], 422);
        }

        $tenant = $context['tenant'];

        try {
            $service->createForSubject((int) $tenant->id, $request->all());
        } catch (LgpdBaseLegalAusenteException $e) {
            return response()->json([
                'message' => 'Registre a base legal LGPD em Configuracoes > LGPD antes de capturar consentimentos',
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['message' => 'ok'], 201);
    })->name('settings.privacy.consents.store');

// ---------------------------------------------------------------------------
// Slice 010 — LGPD: rota pública de revogação
// ---------------------------------------------------------------------------
Route::get('/privacy/revoke/{token}', RevokeConsentPage::class)
    ->middleware('web')
    ->name('lgpd.revoke');

Route::post('/privacy/revoke/{token}', function (
    Request $request,
    string $token,
    RevocationTokenService $tokenService,
    ConsentRecordService $consentService,
) {
    $validToken = $tokenService->findValidToken($token);

    if ($validToken === null) {
        // Verifica se é expirado
        $anyToken = $tokenService->findByRaw($token);
        if ($anyToken !== null && $anyToken->used_at === null && $anyToken->expires_at->isPast()) {
            $subject = $anyToken->consentSubject;
            if ($subject !== null) {
                $tokenService->regenerate(
                    (int) $anyToken->tenant_id,
                    (int) $anyToken->consent_subject_id,
                    $anyToken->channel
                );
                Mail::send(new RevocationConfirmationMail(
                    $subject,
                    $anyToken->channel,
                    now()
                ));
            }

            return response('Link expirado. Solicite um novo link de revogação.', 200);
        }
        abort(404);
    }

    $subject = $validToken->consentSubject;
    $channel = $validToken->channel;
    $reason = (string) $request->input('revocation_reason', 'other_without_details');

    $record = $consentService->revokeConsent(
        (int) $validToken->tenant_id,
        (int) $validToken->consent_subject_id,
        $channel,
        $reason,
        ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent() ?? '']
    );

    if ($record === null) {
        return response('Voce nao tem consentimento ativo para este canal', 200);
    }

    $tokenService->consume($validToken);

    if ($subject !== null) {
        Mail::send(new RevocationConfirmationMail(
            $subject,
            $channel,
            now()
        ));
    }

    return response('Consentimento revogado com sucesso.', 200);
})->middleware('web')->name('lgpd.revoke.post');

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);

if (! app()->environment('production')) {
    Route::get('/ping', Ping::class);
}
