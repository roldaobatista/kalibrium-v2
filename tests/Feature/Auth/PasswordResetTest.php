<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

uses(DatabaseTransactions::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pr_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function pr_user(?string $email = null): User
{
    return User::factory()->create([
        'email' => $email ?? fake()->unique()->safeEmail(),
        'password' => Hash::make('SenhaSegura123!'),
    ]);
}

/** Associa usuário ao tenant (necessário para o controller encontrar o usuário). */
function pr_associate(User $user, Tenant $tenant): void
{
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ]);
}

function pr_url(): string
{
    return '/api/mobile/password/forgot';
}

// ---------------------------------------------------------------------------
// 1. Email cadastrado → 200, notificação enviada, token criado
// ---------------------------------------------------------------------------

test('forgot com email cadastrado retorna 200 e cria token de reset', function (): void {
    Notification::fake();

    $tenant = pr_tenant();
    $user = pr_user();
    pr_associate($user, $tenant);

    $response = $this->postJson(pr_url(), [
        'email' => $user->email,
        'tenant_id' => $tenant->id,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['mensagem' => 'Se este e-mail estiver cadastrado, você vai receber em alguns minutos uma mensagem com o link pra redefinir a senha. Confira sua caixa de entrada (e a pasta de spam, se não encontrar).']);

    Notification::assertSentTo($user, ResetPasswordNotification::class);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email,
    ]);
});

// ---------------------------------------------------------------------------
// 2. Email NÃO cadastrado → 200, mesma mensagem, nenhum e-mail
// ---------------------------------------------------------------------------

test('forgot com email nao cadastrado retorna 200 sem enviar email', function (): void {
    Notification::fake();

    $tenant = pr_tenant();

    $response = $this->postJson(pr_url(), [
        'email' => 'nao-existe@example.com',
        'tenant_id' => $tenant->id,
    ]);

    $response->assertStatus(200);
    Notification::assertNothingSent();

    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'nao-existe@example.com',
    ]);
});

// ---------------------------------------------------------------------------
// 3. Idempotência: 2a chamada pro mesmo email não duplica token
// ---------------------------------------------------------------------------

test('forgot idempotente nao duplica token no banco', function (): void {
    Notification::fake();

    $tenant = pr_tenant();
    $user = pr_user();
    pr_associate($user, $tenant);

    $this->postJson(pr_url(), ['email' => $user->email, 'tenant_id' => $tenant->id]);
    $this->postJson(pr_url(), ['email' => $user->email, 'tenant_id' => $tenant->id]);

    $count = DB::table('password_reset_tokens')->where('email', $user->email)->count();
    expect($count)->toBe(1);
});

// ---------------------------------------------------------------------------
// 4. Throttle: 7ª chamada retorna 429
// ---------------------------------------------------------------------------

test('setima tentativa de forgot retorna 429', function (): void {
    $tenant = pr_tenant();
    $user = pr_user();
    $email = mb_strtolower($user->email);
    $key = 'forgot-password:127.0.0.1|'.$tenant->id.'|'.$email;

    // Pré-popula com 6 tentativas (limite já atingido)
    RateLimiter::clear($key);
    for ($i = 0; $i < 6; $i++) {
        RateLimiter::hit($key, 3600);
    }

    $response = $this->postJson(pr_url(), [
        'email' => $user->email,
        'tenant_id' => $tenant->id,
    ]);

    $response->assertStatus(429);

    RateLimiter::clear($key);
});

// ---------------------------------------------------------------------------
// 5. Reset com token válido → senha atualiza
// ---------------------------------------------------------------------------

test('reset com token valido atualiza a senha', function (): void {
    $user = pr_user();
    $token = Password::broker('users')->createToken($user);

    $response = $this->postJson('/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NovaSenha456!',
        'password_confirmation' => 'NovaSenha456!',
    ]);

    // Fortify retorna redirect ou JSON 200 dependendo da config; aceitar 2xx ou 302
    $response->assertSuccessful();

    $user->refresh();
    expect(Hash::check('NovaSenha456!', $user->password))->toBeTrue();
});

// ---------------------------------------------------------------------------
// 6. Reset com token expirado → erro em pt-BR
// ---------------------------------------------------------------------------

test('reset com token expirado retorna erro', function (): void {
    $user = pr_user();

    // Insere token manualmente com created_at > 60 minutos no passado
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make('token-expirado'),
        'created_at' => now()->subMinutes(61),
    ]);

    $response = $this->postJson('/auth/reset-password', [
        'token' => 'token-expirado',
        'email' => $user->email,
        'password' => 'NovaSenha456!',
        'password_confirmation' => 'NovaSenha456!',
    ]);

    $response->assertStatus(422);
});

// ---------------------------------------------------------------------------
// 7. Reset com token já usado → 2ª tentativa falha
// ---------------------------------------------------------------------------

test('reset com token ja usado retorna erro na segunda tentativa', function (): void {
    $user = pr_user();
    $token = Password::broker('users')->createToken($user);

    // Primeira tentativa — deve funcionar
    $this->postJson('/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NovaSenha456!',
        'password_confirmation' => 'NovaSenha456!',
    ])->assertSuccessful();

    // Segunda tentativa com o mesmo token — deve falhar (token deletado após uso)
    $second = $this->postJson('/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'OutraSenha789!',
        'password_confirmation' => 'OutraSenha789!',
    ]);

    $second->assertStatus(422);
});

// ---------------------------------------------------------------------------
// 8. Senha fraca → 422 (< 8 chars)
// ---------------------------------------------------------------------------

test('reset com senha fraca retorna 422', function (): void {
    $user = pr_user();
    $token = Password::broker('users')->createToken($user);

    $response = $this->postJson('/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'abc',
        'password_confirmation' => 'abc',
    ]);

    $response->assertStatus(422);
});

// ---------------------------------------------------------------------------
// 9. Multi-tenant: reset do tenant A não afeta tenant B
// ---------------------------------------------------------------------------

test('reset multi-tenant nao vaza entre tenants com mesmo email', function (): void {
    Notification::fake();

    $tenantA = pr_tenant();
    $tenantB = pr_tenant();

    // Um único usuário (email único global) vinculado a DOIS tenants diferentes.
    // Esse é o modelo real: users é global; TenantUser é o vínculo.
    $email = fake()->unique()->safeEmail();
    $user = pr_user($email);
    pr_associate($user, $tenantA);
    pr_associate($user, $tenantB);

    // Pede reset via tenantA
    $this->postJson(pr_url(), [
        'email' => $email,
        'tenant_id' => $tenantA->id,
    ])->assertStatus(200);

    // Notificação enviada ao usuário
    Notification::assertSentTo($user, ResetPasswordNotification::class);

    // Somente 1 token criado — escopo de tenantA
    $this->assertDatabaseCount('password_reset_tokens', 1);
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $email,
        'tenant_id' => $tenantA->id,
    ]);
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => $email,
        'tenant_id' => $tenantB->id,
    ]);
});

// ---------------------------------------------------------------------------
// 10. tenant_id válido mas usuário pertence a outro tenant → 200 genérico, nenhum token
// ---------------------------------------------------------------------------

test('forgot com tenant_id valido mas usuario de outro tenant retorna 200 sem token', function (): void {
    Notification::fake();

    $tenantA = pr_tenant();
    $tenantB = pr_tenant();

    $user = pr_user();
    pr_associate($user, $tenantA); // usuário está no tenantA

    // Chama com tenantB — usuário não pertence a ele
    $response = $this->postJson(pr_url(), [
        'email' => $user->email,
        'tenant_id' => $tenantB->id,
    ]);

    $response->assertStatus(200); // não vaza informação
    Notification::assertNothingSent();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
});

// ---------------------------------------------------------------------------
// 11. tenant_id inválido (zero/negativo/string) → 422 de validação
// ---------------------------------------------------------------------------

// O endpoint /api/mobile/password/forgot retorna 200 genérico mesmo com
// tenant_id inválido — por design, para não vazar informação (proteção contra
// enumeração). O controlador captura a ValidationException internamente.
test('forgot com tenant_id invalido retorna 200 generico sem criar token', function (): void {
    foreach ([0, -1, 'abc'] as $invalidId) {
        $response = $this->postJson(pr_url(), [
            'email' => 'qualquer@exemplo.com',
            'tenant_id' => $invalidId,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'qualquer@exemplo.com',
        ]);
    }
});
