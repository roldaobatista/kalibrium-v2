<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\MobileDeviceRequested;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;

uses(DatabaseTransactions::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mobile_url(): string
{
    return '/api/mobile/login';
}

function mobile_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function mobile_user(string $password = 'SenhaSegura123!'): User
{
    return User::factory()->create([
        'password' => Hash::make($password),
    ]);
}

function mobile_bind(User $user, Tenant $tenant): TenantUser
{
    return TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);
}

function mobile_payload(User $user, Tenant $tenant, array $overrides = []): array
{
    return array_merge([
        'tenant_id' => $tenant->id,
        'email' => $user->email,
        'password' => 'SenhaSegura123!',
        'device_identifier' => 'test-device-abc123',
        'device_label' => 'iPhone 14',
    ], $overrides);
}

// ---------------------------------------------------------------------------
// Testes
// ---------------------------------------------------------------------------

test('credenciais erradas retornam 401 com mensagem generica', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant, [
        'password' => 'senha-errada',
    ]));

    $response->assertStatus(401);
    $response->assertJsonFragment(['erro' => 'Email ou senha incorretos']);
});

test('credenciais corretas com device novo criam MobileDevice pending e retornam 202', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenant);

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    $response->assertStatus(202);
    $response->assertJsonFragment(['status' => 'aguardando_aprovacao']);

    $this->assertDatabaseHas('mobile_devices', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
        'status' => MobileDeviceStatus::Pending->value,
    ]);
});

test('device ja pending retorna 202 e atualiza last_seen_at sem duplicar', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenant);

    $device = MobileDevice::factory()->pending()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
        'last_seen_at' => now()->subHour(),
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    $response->assertStatus(202);
    $response->assertJsonFragment(['status' => 'aguardando_aprovacao']);

    // Verifica que não duplicou no tenant — conta só registros deste tenant.
    expect(
        MobileDevice::withoutGlobalScope('current_tenant')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('device_identifier', 'test-device-abc123')
            ->count()
    )->toBe(1);

    $device->refresh();
    expect($device->last_seen_at)->not->toBeNull();
    expect($device->last_seen_at->isAfter(now()->subMinute()))->toBeTrue();
});

test('device approved retorna 200 com token Sanctum', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenant);

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    $response->assertStatus(200);
    $response->assertJsonStructure(['status', 'token', 'user']);
    $response->assertJsonFragment(['status' => 'ok']);

    $token = $response->json('token');
    expect($token)->toBeString()->not->toBeEmpty();
});

test('token Sanctum retornado tem expires_at por volta de 4 dias', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenant);

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    $response->assertStatus(200);

    $plainToken = $response->json('token');
    [$id] = explode('|', $plainToken);

    $pat = PersonalAccessToken::find($id);
    expect($pat)->not->toBeNull();
    expect($pat->expires_at)->not->toBeNull();

    $diffInHours = now()->diffInHours($pat->expires_at, false);
    // Entre 90h (3.75 dias) e 98h (4.08 dias)
    expect($diffInHours)->toBeGreaterThanOrEqual(90)->toBeLessThanOrEqual(98);
});

test('device revoked retorna 403', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenant);

    MobileDevice::factory()->revoked()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    $response->assertStatus(403);
    $response->assertJsonFragment(['erro' => 'Este celular foi bloqueado pelo gerente. Entre em contato com ele.']);
});

test('sexta tentativa errada retorna 429', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();
    $ip = '127.0.0.1';
    $email = mb_strtolower($user->email);
    $cacheKey = 'mobile_login_rate_limit:'.$ip.':'.$email;

    // Pre-popula o cache com 5 tentativas (limite atingido)
    Cache::put($cacheKey, 5, 15 * 60);

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant, ['password' => 'errada']));

    $response->assertStatus(429);

    Cache::forget($cacheKey);
});

test('rate limit por ip+email nao bloqueia outro email do mesmo ip', function (): void {
    $tenant = mobile_tenant();
    $userA = mobile_user();
    $userB = mobile_user();
    $ip = '127.0.0.1';

    // Bloqueia userA
    $keyA = 'mobile_login_rate_limit:'.$ip.':'.mb_strtolower($userA->email);
    Cache::put($keyA, 5, 15 * 60);

    // userB no mesmo IP não deve ser bloqueado
    $response = $this->postJson(mobile_url(), mobile_payload($userB, $tenant, ['password' => 'errada']));

    $response->assertStatus(401);

    Cache::forget($keyA);
});

test('isolamento cross-tenant: device do tenant A nao aparece no tenant B', function (): void {
    $tenantA = mobile_tenant();
    $tenantB = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenantA);
    mobile_bind($user, $tenantB);

    // Cria device aprovado no tenant A
    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'device_identifier' => 'shared-device-xyz',
    ]);

    // Login no tenant B com mesmo device_identifier → device não existe nesse tenant → pending
    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenantB, [
        'device_identifier' => 'shared-device-xyz',
    ]));

    $response->assertStatus(202);
    $response->assertJsonFragment(['status' => 'aguardando_aprovacao']);

    // Confirma que o device do tenant B foi criado separado
    $this->assertDatabaseHas('mobile_devices', [
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'device_identifier' => 'shared-device-xyz',
        'status' => MobileDeviceStatus::Pending->value,
    ]);

    // Device do tenant A continua aprovado e intocado
    $this->assertDatabaseHas('mobile_devices', [
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'device_identifier' => 'shared-device-xyz',
        'status' => MobileDeviceStatus::Approved->value,
    ]);
});

test('device_identifier com caracteres invalidos retorna 422', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant, [
        'device_identifier' => 'device with spaces & special!',
    ]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['device_identifier']);
});

test('tenant_id ausente retorna 422', function (): void {
    $user = mobile_user();

    $response = $this->postJson(mobile_url(), [
        'email' => $user->email,
        'password' => 'SenhaSegura123!',
        'device_identifier' => 'test-device-abc123',
    ]);

    $response->assertStatus(422);
});

test('device aprovado retorna token ancorado ao tenant correto', function (): void {
    $tenant = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenant);

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    $response->assertStatus(200);

    $plainToken = $response->json('token');
    [$id] = explode('|', $plainToken);

    $pat = PersonalAccessToken::find($id);
    expect($pat)->not->toBeNull();
    expect($pat->name)->toBe('mobile:tenant:'.$tenant->id);
});

test('tenant_id inexistente retorna 422 com mensagem generica', function (): void {
    $user = mobile_user();

    // ID numérico alto o suficiente para não existir no banco de testes.
    $response = $this->postJson(mobile_url(), [
        'tenant_id' => 999999999,
        'email' => $user->email,
        'password' => 'SenhaSegura123!',
        'device_identifier' => 'test-device-abc123',
        'device_label' => 'iPhone 14',
    ]);

    // Middleware ResolveMobileTenant rejeita sem confirmar existência de outros tenants.
    $response->assertStatus(422);
    $response->assertJsonFragment(['erro' => 'Laboratório não encontrado.']);
});

test('login com device novo dispara notificacao para gerentes do tenant', function (): void {
    Notification::fake();

    $tenant = mobile_tenant();
    $user = mobile_user();
    mobile_bind($user, $tenant);

    // Cria um gerente ativo no tenant
    $gerenteUser = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $gerenteUser->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    Notification::assertSentTo($gerenteUser, MobileDeviceRequested::class);
});

test('login no tenant A nao notifica gerente do tenant B', function (): void {
    Notification::fake();

    $tenantA = mobile_tenant();
    $tenantB = mobile_tenant();
    $user = mobile_user();

    $gerenteB = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $gerenteB->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    $this->postJson(mobile_url(), mobile_payload($user, $tenantA));

    Notification::assertNotSentTo([$gerenteB], MobileDeviceRequested::class);
});

test('login com device ja pending nao dispara notificacao novamente', function (): void {
    Notification::fake();

    $tenant = mobile_tenant();
    $user = mobile_user();

    MobileDevice::factory()->pending()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $gerenteUser = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $gerenteUser->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    $this->postJson(mobile_url(), mobile_payload($user, $tenant));

    Notification::assertNothingSent();
});
