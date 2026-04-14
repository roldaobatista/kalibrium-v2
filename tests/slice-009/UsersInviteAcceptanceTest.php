<?php

declare(strict_types=1);

use App\Models\TenantUser;
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
