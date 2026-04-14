<?php

declare(strict_types=1);

use App\Mail\UserInvitationMail;
use App\Models\TenantUser;
use App\Support\Auth\TenantAccessResolver;
use App\Support\Settings\UserInvitationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/TestHelpers.php';

test('AC-003: convite valido permite definir senha, ativa vinculo correto e redireciona para login', function (): void {
    $context = slice009_invitation_context([
        'role' => 'tecnico',
        'status' => 'invited',
    ]);

    $response = $this->post(
        slice009_routes()['invitation']($context['token']),
        slice009_accept_payload(),
    );

    $response->assertRedirect('/auth/login');

    $tenantUser = TenantUser::query()->find($context['invited_tenant_user']->id);
    expect($tenantUser)->not->toBeNull();
    expect($tenantUser->tenant_id)->toBe($context['tenant']->id);
    expect($tenantUser->company_id)->toBe($context['company']->id);
    expect($tenantUser->branch_id)->toBe($context['branch']->id);
    expect($tenantUser->status)->toBe('active');

    if (Schema::hasColumn('tenant_users', 'accepted_at')) {
        expect($tenantUser->accepted_at)->not->toBeNull();
    }
    if (Schema::hasColumn('tenant_users', 'invitation_token_hash')) {
        expect($tenantUser->invitation_token_hash)->toBeNull();
    }

    $login = $this->post('/auth/login', [
        'email' => $context['invited_user']->email,
        'password' => 'NovaSenhaSegura123!',
    ]);
    $login->assertRedirect('/app');

    $decision = app(TenantAccessResolver::class)->resolve($context['invited_user']->fresh());
    expect($decision['tenant_id'])->toBe($context['tenant']->id);
    expect($decision['tenant_user_id'])->toBe($tenantUser->id);
})->group('slice-009', 'ac-003');

test('AC-003: aceite de convite para conta ativa em outro tenant preserva a senha global existente', function (): void {
    Mail::fake();
    $context = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'gerente',
    ]);
    $external = slice009_user_with_tenant_context([
        'tenant_status' => 'active',
        'role' => 'tecnico',
        'password' => 'SenhaAntigaGlobal123!',
    ]);
    $token = null;

    app(UserInvitationService::class)->invite($context['user'], $context['tenant_user'], slice009_invite_payload($context, [
        'email' => $external['user']->email,
        'role' => 'visualizador',
    ]));

    Mail::assertSent(UserInvitationMail::class, static function (UserInvitationMail $mail) use (&$token): bool {
        $path = parse_url($mail->invitationUrl, PHP_URL_PATH);
        $token = is_string($path) ? basename($path) : null;

        return is_string($token) && $token !== '';
    });

    expect($token)->toBeString()->not->toBe('');

    $response = $this->post(
        slice009_routes()['invitation']($token),
        slice009_accept_payload([
            'password' => 'NovaSenhaOutroTenant123!',
            'password_confirmation' => 'NovaSenhaOutroTenant123!',
        ]),
    );

    $response->assertRedirect('/auth/login');

    $freshUser = $external['user']->fresh();
    expect(Hash::check('SenhaAntigaGlobal123!', $freshUser->password))->toBeTrue();
    expect(Hash::check('NovaSenhaOutroTenant123!', $freshUser->password))->toBeFalse();
    expect(TenantUser::query()
        ->where('tenant_id', $context['tenant']->id)
        ->where('user_id', $external['user']->id)
        ->where('status', 'active')
        ->exists())->toBeTrue();
})->group('slice-009', 'ac-003');

test('AC-015: convite expirado, usado ou de outro tenant bloqueia aceite sem alterar senha nem ativar vinculo', function (array $overrides, string $expectedStatus): void {
    $context = slice009_invitation_context(array_merge([
        'role' => 'tecnico',
        'status' => 'invited',
    ], array_diff_key($overrides, ['request_token' => true])));
    $originalPassword = $context['invited_user']->password;
    $requestToken = (string) ($overrides['request_token'] ?? $context['token']);

    $response = $this->post(
        slice009_routes()['invitation']($requestToken),
        slice009_accept_payload(),
    );

    expect(in_array($response->status(), [302, 403, 404, 422], true))->toBeTrue();
    if ($response->status() === 302) {
        $response->assertSessionHas('status');
    } else {
        $response->assertSee('novo convite');
    }

    $tenantUser = TenantUser::query()->find($context['invited_tenant_user']->id);
    expect($tenantUser->status)->toBe($expectedStatus);
    expect($context['invited_user']->fresh()->password)->toBe($originalPassword);
    slice009_assert_body_does_not_leak($response, [
        $context['tenant']->name,
        $context['token'],
    ]);
})->with([
    'expirado' => [['invitation_expires_at' => now()->subMinute()], 'invited'],
    'ja usado' => [['status' => 'active', 'accepted_at' => now()], 'active'],
    'token inexistente' => [['request_token' => 'invitation-token-invalido'], 'invited'],
])->group('slice-009', 'ac-015');

test('AC-016: senha curta ou confirmacao divergente retorna validacao e mantem convite pendente', function (array $payloadOverrides): void {
    $context = slice009_invitation_context([
        'role' => 'tecnico',
        'status' => 'invited',
    ]);

    $response = $this->from(slice009_routes()['invitation']($context['token']))
        ->post(
            slice009_routes()['invitation']($context['token']),
            slice009_accept_payload($payloadOverrides),
        );

    expect(in_array($response->status(), [302, 422], true))->toBeTrue();
    if ($response->status() === 302) {
        $response->assertSessionHasErrors('password');
    }

    $tenantUser = TenantUser::query()->find($context['invited_tenant_user']->id);
    expect($tenantUser->status)->toBe('invited');
})->with([
    'senha curta' => [['password' => 'curta', 'password_confirmation' => 'curta']],
    'confirmacao divergente' => [['password_confirmation' => 'OutraSenhaSegura123!']],
])->group('slice-009', 'ac-016');
