<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\Note;
use App\Models\SyncChange;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

afterEach(function (): void {
    TenantContext::reset();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function sync_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function sync_technician(Tenant $tenant): User
{
    $user = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::TECHNICIAN,
        'status' => 'active',
    ]);

    return $user;
}

/**
 * Cria device aprovado e retorna o plain-text token Sanctum.
 */
function sync_token(User $user, Tenant $tenant, string $deviceId = 'device-sync-test'): string
{
    MobileDevice::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => $deviceId,
        'status' => MobileDeviceStatus::Approved,
    ]);

    $token = $user->createToken(
        name: 'mobile:tenant:'.$tenant->id,
        abilities: ['mobile:full'],
        expiresAt: now()->addDays(4),
    );

    return $token->plainTextToken;
}

/**
 * Seta contexto de tenant nas request attributes e TenantContext (como o middleware faz).
 */
function sync_set_context(Tenant $tenant): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
}

function sync_push_url(): string
{
    return '/api/mobile/sync/push';
}

function sync_pull_url(): string
{
    return '/api/mobile/sync/pull';
}

function sync_change_payload(array $overrides = []): array
{
    return array_merge([
        'local_id' => (string) Str::ulid(),
        'entity_type' => 'note',
        'entity_id' => (string) Str::uuid(),
        'action' => 'create',
        'payload' => [
            'title' => 'Nota de teste',
            'body' => 'Conteúdo da nota',
            'updated_at' => now()->toIso8601String(),
        ],
    ], $overrides);
}

// ---------------------------------------------------------------------------
// 1. Push create → cria Note + SyncChange
// ---------------------------------------------------------------------------

test('push create cria Note e SyncChange no tenant', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    $change = sync_change_payload(['action' => 'create']);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-sync-test')
        ->postJson(sync_push_url(), [
            'device_id' => 'device-sync-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonStructure(['applied' => [['local_id', 'server_id', 'ulid', 'version']]]);
    $response->assertJsonCount(1, 'applied');
    $response->assertJsonCount(0, 'rejected');

    $serverId = $response->json('applied.0.server_id');

    expect(
        Note::withoutGlobalScope('current_tenant')
            ->where('id', $serverId)
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->exists()
    )->toBeTrue();

    expect(
        SyncChange::withoutGlobalScope('current_tenant')
            ->where('entity_type', 'note')
            ->where('entity_id', $serverId)
            ->where('action', 'create')
            ->where('tenant_id', $tenant->id)
            ->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// 2. Push update com updated_at mais antigo → rejected: stale_update
// ---------------------------------------------------------------------------

test('push update com updated_at mais antigo e rejeitado como stale_update', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    $note = Note::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'updated_at' => now(),
    ]);

    $change = sync_change_payload([
        'action' => 'update',
        'entity_id' => $note->id,
        'payload' => [
            'title' => 'Título novo',
            'body' => 'Corpo novo',
            'updated_at' => now()->subMinutes(5)->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-sync-test')
        ->postJson(sync_push_url(), [
            'device_id' => 'device-sync-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'applied');
    $response->assertJsonCount(1, 'rejected');
    $response->assertJsonPath('rejected.0.reason', 'stale_update');

    $note->refresh();
    expect($note->title)->not->toBe('Título novo');
});

// ---------------------------------------------------------------------------
// 3. Push update com updated_at mais recente → applied, version incrementa
// ---------------------------------------------------------------------------

test('push update com updated_at mais recente e aplicado e incrementa version', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    $note = Note::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'version' => 1,
        'updated_at' => now()->subMinutes(10),
    ]);

    $change = sync_change_payload([
        'action' => 'update',
        'entity_id' => $note->id,
        'payload' => [
            'title' => 'Título atualizado',
            'body' => 'Corpo atualizado',
            'updated_at' => now()->toIso8601String(),
        ],
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-sync-test')
        ->postJson(sync_push_url(), [
            'device_id' => 'device-sync-test',
            'changes' => [$change],
        ]);

    $response->assertOk();
    $response->assertJsonCount(1, 'applied');
    $response->assertJsonPath('applied.0.version', 2);

    $note->refresh();
    expect($note->title)->toBe('Título atualizado');
    expect($note->version)->toBe(2);
});

// ---------------------------------------------------------------------------
// 4. Push com >100 mudanças → 422
// ---------------------------------------------------------------------------

test('push com mais de 100 mudancas retorna 422', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    $changes = [];
    for ($i = 0; $i < 101; $i++) {
        $changes[] = sync_change_payload();
    }

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-sync-test')
        ->postJson(sync_push_url(), [
            'device_id' => 'device-sync-test',
            'changes' => $changes,
        ]);

    $response->assertStatus(422);
});

// ---------------------------------------------------------------------------
// 5. Pull sem cursor retorna todas mudanças do tenant escopadas pelo user
// ---------------------------------------------------------------------------

test('pull sem cursor retorna mudancas do usuario no tenant', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    // Cria 2 SyncChanges do usuário no tenant
    SyncChange::factory()->count(2)->create([
        'tenant_id' => $tenant->id,
        'source_user_id' => $user->id,
        'entity_type' => 'note',
        'action' => 'create',
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-sync-test')
        ->getJson(sync_pull_url());

    $response->assertOk();
    $response->assertJsonStructure(['changes', 'next_cursor', 'has_more']);
    expect(count($response->json('changes')))->toBeGreaterThanOrEqual(2);
});

// ---------------------------------------------------------------------------
// 6. Pull com cursor retorna apenas posteriores
// ---------------------------------------------------------------------------

test('pull com cursor retorna apenas mudancas posteriores ao cursor', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    $change1 = SyncChange::factory()->create([
        'tenant_id' => $tenant->id,
        'source_user_id' => $user->id,
        'entity_type' => 'note',
        'action' => 'create',
        'ulid' => (string) Str::ulid(),
    ]);

    // Pequena pausa para garantir ULID posterior
    usleep(2000);

    $change2 = SyncChange::factory()->create([
        'tenant_id' => $tenant->id,
        'source_user_id' => $user->id,
        'entity_type' => 'note',
        'action' => 'update',
        'ulid' => (string) Str::ulid(),
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-sync-test')
        ->getJson(sync_pull_url().'?cursor='.$change1->ulid);

    $response->assertOk();

    $ulidRetornados = array_column($response->json('changes'), 'ulid');
    expect($ulidRetornados)->not->toContain($change1->ulid);
    expect($ulidRetornados)->toContain($change2->ulid);
});

// ---------------------------------------------------------------------------
// 7. Pull respeita has_more quando excede limit
// ---------------------------------------------------------------------------

test('pull com limit pequeno retorna has_more true e next_cursor', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    SyncChange::factory()->count(5)->create([
        'tenant_id' => $tenant->id,
        'source_user_id' => $user->id,
        'entity_type' => 'note',
        'action' => 'create',
    ]);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-sync-test')
        ->getJson(sync_pull_url().'?limit=2');

    $response->assertOk();
    expect($response->json('has_more'))->toBeTrue();
    expect($response->json('next_cursor'))->not->toBeNull();
    expect($response->json('changes'))->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// 8. Multi-tenant: push tenant A invisível em pull tenant B
// ---------------------------------------------------------------------------

test('mudancas do tenant A nao aparecem no pull do tenant B', function (): void {
    $tenantA = sync_tenant();
    $tenantB = sync_tenant();
    $userA = sync_technician($tenantA);
    $userB = sync_technician($tenantB);
    $tokenA = sync_token($userA, $tenantA, 'device-a');
    $tokenB = sync_token($userB, $tenantB, 'device-b');

    // Push no tenant A
    sync_set_context($tenantA);

    $change = sync_change_payload(['action' => 'create']);

    $pushResponse = $this->withToken($tokenA)
        ->withHeader('X-Device-Id', 'device-a')
        ->postJson(sync_push_url(), [
            'device_id' => 'device-a',
            'changes' => [$change],
        ]);

    $pushResponse->assertOk();
    $serverId = $pushResponse->json('applied.0.server_id');

    TenantContext::reset();

    // Pull no tenant B — não deve ver o registro do tenant A
    sync_set_context($tenantB);

    $pullResponse = $this->withToken($tokenB)
        ->withHeader('X-Device-Id', 'device-b')
        ->getJson(sync_pull_url());

    $pullResponse->assertOk();

    $entityIds = array_column($pullResponse->json('changes'), 'entity_id');
    expect($entityIds)->not->toContain($serverId);
});

// ---------------------------------------------------------------------------
// 9. Técnico A não vê notes de técnico B do mesmo tenant via pull
// ---------------------------------------------------------------------------

test('tecnico A nao ve notes do tecnico B no pull', function (): void {
    $tenant = sync_tenant();
    $userA = sync_technician($tenant);
    $userB = sync_technician($tenant);
    $tokenA = sync_token($userA, $tenant, 'device-tecnico-a');

    sync_set_context($tenant);

    // SyncChange criado por B, sem source_user_id de A
    $changeB = SyncChange::factory()->create([
        'tenant_id' => $tenant->id,
        'source_user_id' => $userB->id,
        'entity_type' => 'note',
        'action' => 'create',
        'payload_after' => ['user_id' => $userB->id, 'title' => 'nota do B'],
    ]);

    $response = $this->withToken($tokenA)
        ->withHeader('X-Device-Id', 'device-tecnico-a')
        ->getJson(sync_pull_url());

    $response->assertOk();

    $ulidRetornados = array_column($response->json('changes'), 'ulid');
    expect($ulidRetornados)->not->toContain($changeB->ulid);
});

// ---------------------------------------------------------------------------
// 10. Device wiped_and_revoked → 401 wipe:true em push e pull
// ---------------------------------------------------------------------------

test('device wiped retorna 401 com wipe true em push e pull', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);

    MobileDevice::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'device_identifier' => 'device-wiped',
        'status' => MobileDeviceStatus::WipedAndRevoked,
        'wipe_acknowledged_at' => null,
    ]);

    $token = $user->createToken(
        name: 'mobile:tenant:'.$tenant->id,
        abilities: ['mobile:full'],
        expiresAt: now()->addDays(4),
    );

    $pushResp = $this->withToken($token->plainTextToken)
        ->withHeader('X-Device-Id', 'device-wiped')
        ->postJson(sync_push_url(), [
            'device_id' => 'device-wiped',
            'changes' => [sync_change_payload()],
        ]);

    $pushResp->assertStatus(401);
    $pushResp->assertJsonFragment(['wipe' => true]);

    $pullResp = $this->withToken($token->plainTextToken)
        ->withHeader('X-Device-Id', 'device-wiped')
        ->getJson(sync_pull_url());

    $pullResp->assertStatus(401);
    $pullResp->assertJsonFragment(['wipe' => true]);
});

// ---------------------------------------------------------------------------
// 11. Sem X-Device-Id → 400
// ---------------------------------------------------------------------------

test('push e pull sem X-Device-Id retornam 400', function (): void {
    $tenant = sync_tenant();
    $user = sync_technician($tenant);
    $token = sync_token($user, $tenant);

    sync_set_context($tenant);

    $pushResp = $this->withToken($token)
        ->postJson(sync_push_url(), [
            'device_id' => 'qualquer',
            'changes' => [sync_change_payload()],
        ]);

    $pushResp->assertStatus(400);

    $pullResp = $this->withToken($token)
        ->getJson(sync_pull_url());

    $pullResp->assertStatus(400);
});
