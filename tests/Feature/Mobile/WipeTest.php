<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Livewire\MobileDevices\IndexPage;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;

uses(DatabaseTransactions::class);

afterEach(function (): void {
    TenantContext::reset();
});

// ---------------------------------------------------------------------------
// Helpers locais
// ---------------------------------------------------------------------------

function wipe_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function wipe_gerente(Tenant $tenant): array
{
    $user = User::factory()->create(['password' => Hash::make('SenhaSegura123!')]);
    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    return compact('user', 'tenantUser');
}

function wipe_tecnico(Tenant $tenant): array
{
    $user = User::factory()->create(['password' => Hash::make('SenhaSegura123!')]);
    $tenantUser = TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    return compact('user', 'tenantUser');
}

function wipe_set_context(Tenant $tenant, TenantUser $tenantUser): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
    request()->attributes->set('current_tenant_id', $tenant->id);
    request()->attributes->set('current_tenant_user', $tenantUser);
}

/** Cria token Sanctum com nome 'mobile:tenant:{id}' e retorna o plain text token. */
function wipe_make_token(User $user, int $tenantId): string
{
    $token = $user->createToken(
        name: 'mobile:tenant:'.$tenantId,
        abilities: ['mobile:full'],
        expiresAt: now()->addDays(4),
    );

    return $token->plainTextToken;
}

// ---------------------------------------------------------------------------
// Testes da action Livewire limparEBloquear
// ---------------------------------------------------------------------------

test('gerente limpa device aprovado: status vira wiped_and_revoked e campos sao setados', function (): void {
    $tenant = wipe_tenant();
    ['user' => $gerente, 'tenantUser' => $tuGerente] = wipe_gerente($tenant);
    ['user' => $tecnico] = wipe_tecnico($tenant);

    $device = MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $tecnico->id,
    ]);

    wipe_set_context($tenant, $tuGerente);

    Livewire\Livewire::actingAs($gerente)
        ->test(IndexPage::class)
        ->call('limparEBloquear', $device->id);

    $device->refresh();

    expect($device->status)->toBe(MobileDeviceStatus::WipedAndRevoked);
    expect($device->wiped_at)->not->toBeNull();
    expect($device->wiped_by_user_id)->toBe($gerente->id);
    expect($device->revoked_at)->not->toBeNull();
});

test('tecnico nao tem acesso ao painel — mount retorna 403', function (): void {
    $tenant = wipe_tenant();
    ['user' => $tecnico, 'tenantUser' => $tuTecnico] = wipe_tecnico($tenant);

    wipe_set_context($tenant, $tuTecnico);

    // Técnico não tem a permissão mobile-devices.viewAny — mount lança 403.
    Livewire\Livewire::actingAs($tecnico)
        ->test(IndexPage::class)
        ->assertForbidden();
});

test('limparEBloquear e isolada por tenant: gerente A nao wipe device do tenant B', function (): void {
    $tenantA = wipe_tenant();
    $tenantB = wipe_tenant();
    ['user' => $gerenteA, 'tenantUser' => $tuGerenteA] = wipe_gerente($tenantA);
    ['user' => $tecnicoB] = wipe_tecnico($tenantB);

    $device = MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $tecnicoB->id,
    ]);

    wipe_set_context($tenantA, $tuGerenteA);

    Livewire\Livewire::actingAs($gerenteA)
        ->test(IndexPage::class)
        ->call('limparEBloquear', $device->id)
        ->assertNotFound();

    $device->refresh();
    expect($device->status)->toBe(MobileDeviceStatus::Approved);
});

test('limparEBloquear registra auditoria', function (): void {
    $tenant = wipe_tenant();
    ['user' => $gerente, 'tenantUser' => $tuGerente] = wipe_gerente($tenant);
    ['user' => $tecnico] = wipe_tecnico($tenant);

    $device = MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $tecnico->id,
    ]);

    wipe_set_context($tenant, $tuGerente);

    Livewire\Livewire::actingAs($gerente)
        ->test(IndexPage::class)
        ->call('limparEBloquear', $device->id);

    expect(
        TenantAuditLog::where('action', 'mobile_device.wiped')
            ->where('user_id', $gerente->id)
            ->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// Testes do middleware CheckMobileDeviceStatus via /api/mobile/me
// ---------------------------------------------------------------------------

test('chamada autenticada com device wiped_and_revoked retorna 401 com wipe true e marca wipe_acknowledged_at', function (): void {
    $tenant = wipe_tenant();
    $user = User::factory()->create();

    $device = MobileDevice::factory()->wipedAndRevoked()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-wipe-001',
        'wipe_acknowledged_at' => null,
    ]);

    $plainToken = wipe_make_token($user, (int) $tenant->id);

    $response = $this->withToken($plainToken)
        ->getJson('/api/mobile/me', ['X-Device-Id' => 'test-device-wipe-001']);

    $response->assertStatus(401);
    $response->assertJsonFragment(['wipe' => true]);

    $device->refresh();
    expect($device->wipe_acknowledged_at)->not->toBeNull();
});

test('chamada sem X-Device-Id retorna 400', function (): void {
    $user = User::factory()->create();
    $tenant = wipe_tenant();

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'any-device',
    ]);

    $plainToken = wipe_make_token($user, (int) $tenant->id);

    $response = $this->withToken($plainToken)->getJson('/api/mobile/me');

    $response->assertStatus(400);
});

test('chamada com token valido e device approved retorna 200', function (): void {
    $tenant = wipe_tenant();
    $user = User::factory()->create();

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-ok-001',
    ]);

    $plainToken = wipe_make_token($user, (int) $tenant->id);

    $response = $this->withToken($plainToken)
        ->getJson('/api/mobile/me', ['X-Device-Id' => 'test-device-ok-001']);

    $response->assertStatus(200);
    $response->assertJsonStructure(['id', 'name', 'email']);
});

test('reinstalacao do app cria novo device pending sem bloquear', function (): void {
    $tenant = wipe_tenant();
    $user = User::factory()->create(['password' => Hash::make('SenhaSegura123!')]);

    MobileDevice::factory()->wipedAndRevoked()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'old-device-identifier',
    ]);

    $response = $this->postJson('/api/mobile/login', [
        'tenant_id' => $tenant->id,
        'email' => $user->email,
        'password' => 'SenhaSegura123!',
        'device_identifier' => 'new-device-after-reinstall',
        'device_label' => 'Samsung Galaxy A54 Novo',
    ]);

    $response->assertStatus(202);
    $response->assertJsonFragment(['status' => 'aguardando_aprovacao']);

    expect(
        MobileDevice::where('device_identifier', 'new-device-after-reinstall')
            ->where('status', MobileDeviceStatus::Pending->value)
            ->exists()
    )->toBeTrue();
});

test('token com nome fora do padrao mobile:tenant:{id} retorna 401', function (): void {
    $tenant = wipe_tenant();
    $user = User::factory()->create();

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'device-legacy-001',
    ]);

    // Cria token com nome qualquer — fora do padrão esperado.
    $tokenResult = $user->createToken(
        name: 'tokenLegacy',
        abilities: ['mobile:full'],
        expiresAt: now()->addDays(4),
    );

    $response = $this->withToken($tokenResult->plainTextToken)
        ->getJson('/api/mobile/me', ['X-Device-Id' => 'device-legacy-001']);

    $response->assertStatus(401);
    $response->assertJsonMissing(['wipe' => true]);
});

test('token do tecnico A com device_id do tecnico B retorna 401', function (): void {
    $tenant = wipe_tenant();
    ['user' => $tecnicoA] = wipe_tecnico($tenant);
    ['user' => $tecnicoB] = wipe_tecnico($tenant);

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $tecnicoA->id,
        'device_identifier' => 'device-tecnico-a',
    ]);

    MobileDevice::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $tecnicoB->id,
        'device_identifier' => 'device-tecnico-b',
    ]);

    // Token do A, mas header aponta pro device do B — tentativa de hijack.
    $plainToken = wipe_make_token($tecnicoA, (int) $tenant->id);

    $response = $this->withToken($plainToken)
        ->getJson('/api/mobile/me', ['X-Device-Id' => 'device-tecnico-b']);

    $response->assertStatus(401);
});

test('device wiped com token expirado retorna 401 com wipe true antes do sanctum barrar', function (): void {
    $tenant = wipe_tenant();
    $user = User::factory()->create();

    $device = MobileDevice::factory()->wipedAndRevoked()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'device-wiped-expired',
        'wipe_acknowledged_at' => null,
    ]);

    // Cria token que já expirou no passado.
    $tokenResult = $user->createToken(
        name: 'mobile:tenant:'.$tenant->id,
        abilities: ['mobile:full'],
        expiresAt: now()->subMinutes(10),
    );

    $response = $this->withToken($tokenResult->plainTextToken)
        ->getJson('/api/mobile/me', ['X-Device-Id' => 'device-wiped-expired']);

    // O middleware de device (que roda antes do Sanctum) deve retornar wipe:true.
    // O app móvel precisa desse sinal mesmo com token expirado para limpar dados locais.
    $response->assertStatus(401);
    $response->assertJsonFragment(['wipe' => true]);

    $device->refresh();
    expect($device->wipe_acknowledged_at)->not->toBeNull();
});

test('wipe_acknowledged_at nao e sobrescrito na segunda chamada com device wiped', function (): void {
    $tenant = wipe_tenant();
    $user = User::factory()->create();

    $firstAck = now()->subMinutes(5);

    $device = MobileDevice::factory()->wipedAndRevoked()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'test-device-wipe-dup',
        'wipe_acknowledged_at' => $firstAck,
    ]);

    $plainToken = wipe_make_token($user, (int) $tenant->id);

    $this->withToken($plainToken)
        ->getJson('/api/mobile/me', ['X-Device-Id' => 'test-device-wipe-dup'])
        ->assertStatus(401);

    $device->refresh();
    expect($device->wipe_acknowledged_at->timestamp)->toBe($firstAck->timestamp);
});
