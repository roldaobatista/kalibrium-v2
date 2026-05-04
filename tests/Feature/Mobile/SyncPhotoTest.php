<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderPhoto;
use App\Models\SyncChange;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

afterEach(function (): void {
    TenantContext::reset();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function photo_tenant(): Tenant
{
    return Tenant::factory()->create();
}

function photo_technician(Tenant $tenant): User
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

function photo_manager(Tenant $tenant): User
{
    $user = User::factory()->create();
    TenantUser::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantRole::MANAGER,
        'status' => 'active',
    ]);

    return $user;
}

function photo_token(User $user, Tenant $tenant, string $deviceId = 'device-photo-test'): string
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

function photo_set_context(Tenant $tenant): void
{
    TenantContext::setTenantId((int) $tenant->id);
    request()->attributes->set('current_tenant', $tenant);
}

function photo_service_order(Tenant $tenant, User $user): ServiceOrder
{
    return ServiceOrder::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'client_name' => 'Empresa Teste',
        'instrument_description' => 'Paquímetro 200mm',
        'status' => 'received',
        'version' => 1,
        'updated_at' => now()->subMinutes(5),
        'created_at' => now()->subMinutes(5),
    ]);
}

function photo_fake_image(int $kilobytes = 100, string $extension = 'jpg'): UploadedFile
{
    // UploadedFile::fake()->image() cria arquivo com tamanho real via GD
    return UploadedFile::fake()->image("photo.{$extension}", 100, 100)->size($kilobytes);
}

// ---------------------------------------------------------------------------
// 1. Upload feliz cria registro, salva arquivo, retorna URL assinada
// ---------------------------------------------------------------------------

test('upload de foto cria registro e retorna url assinada', function (): void {
    Storage::fake('local');

    $tenant = photo_tenant();
    $user = photo_technician($tenant);
    $token = photo_token($user, $tenant);
    $order = photo_service_order($tenant, $user);

    photo_set_context($tenant);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-photo-test')
        ->post('/api/mobile/sync/upload-photo', [
            'service_order_id' => $order->id,
            'client_uuid' => (string) Str::ulid(),
            'photo' => photo_fake_image(500),
        ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['id', 'server_id', 'ulid', 'url_signed_get']);

    $serverId = $response->json('server_id');

    $photo = ServiceOrderPhoto::withoutGlobalScope('current_tenant')->where('id', $serverId)->first();
    expect($photo)->not->toBeNull();
    expect($photo->tenant_id)->toBe($tenant->id);
    expect($photo->service_order_id)->toBe($order->id);
    expect($photo->user_id)->toBe($user->id);

    Storage::disk('local')->assertExists($photo->path);

    expect(
        SyncChange::withoutGlobalScope('current_tenant')
            ->where('entity_type', 'service_order_photo')
            ->where('entity_id', $serverId)
            ->where('action', 'create')
            ->where('tenant_id', $tenant->id)
            ->exists()
    )->toBeTrue();
});

// ---------------------------------------------------------------------------
// 2. Upload > 8 MB rejeitado (422)
// ---------------------------------------------------------------------------

test('upload de foto acima de 8mb e rejeitado com 422', function (): void {
    Storage::fake('local');

    // Aumenta limite do PHP para que o arquivo chegue ao validator do Laravel
    $prev_upload = ini_set('upload_max_filesize', '20M');
    $prev_post = ini_set('post_max_size', '20M');

    $tenant = photo_tenant();
    $user = photo_technician($tenant);
    $token = photo_token($user, $tenant);
    $order = photo_service_order($tenant, $user);

    photo_set_context($tenant);

    $response = $this->withToken($token)
        ->withHeaders(['X-Device-Id' => 'device-photo-test', 'Accept' => 'application/json'])
        ->post('/api/mobile/sync/upload-photo', [
            'service_order_id' => $order->id,
            'client_uuid' => (string) Str::ulid(),
            'photo' => photo_fake_image(9 * 1024), // 9 MB
        ]);

    ini_set('upload_max_filesize', $prev_upload ?: '2M');
    ini_set('post_max_size', $prev_post ?: '8M');

    $response->assertStatus(422);
});

// ---------------------------------------------------------------------------
// 3. Upload com mime não aceito rejeitado (422)
// ---------------------------------------------------------------------------

test('upload de foto com mime invalido e rejeitado com 422', function (): void {
    Storage::fake('local');

    $tenant = photo_tenant();
    $user = photo_technician($tenant);
    $token = photo_token($user, $tenant);
    $order = photo_service_order($tenant, $user);

    photo_set_context($tenant);

    $response = $this->withToken($token)
        ->withHeaders(['X-Device-Id' => 'device-photo-test', 'Accept' => 'application/json'])
        ->post('/api/mobile/sync/upload-photo', [
            'service_order_id' => $order->id,
            'client_uuid' => (string) Str::ulid(),
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

    $response->assertStatus(422);
});

// ---------------------------------------------------------------------------
// 4. Multi-tenant: user do tenant B não consegue fazer upload na OS do tenant A
// ---------------------------------------------------------------------------

test('user do tenant B nao consegue fazer upload na OS do tenant A', function (): void {
    Storage::fake('local');

    $tenantA = photo_tenant();
    $tenantB = photo_tenant();
    $userA = photo_technician($tenantA);
    $userB = photo_technician($tenantB);
    $tokenB = photo_token($userB, $tenantB, 'device-b');

    $orderA = photo_service_order($tenantA, $userA);

    // Contexto do tenant B — tenta subir foto na OS do tenant A
    photo_set_context($tenantB);

    $response = $this->withToken($tokenB)
        ->withHeader('X-Device-Id', 'device-b')
        ->post('/api/mobile/sync/upload-photo', [
            'service_order_id' => $orderA->id,
            'client_uuid' => (string) Str::ulid(),
            'photo' => photo_fake_image(100),
        ]);

    $response->assertStatus(404);
});

// ---------------------------------------------------------------------------
// 5. Signed URL: técnico não pode baixar foto de outro técnico do mesmo tenant
// ---------------------------------------------------------------------------

test('tecnico B nao consegue obter signed url da foto do tecnico A', function (): void {
    Storage::fake('local');

    $tenant = photo_tenant();
    $userA = photo_technician($tenant);
    $userB = photo_technician($tenant);
    $tokenB = photo_token($userB, $tenant, 'device-b');

    $orderA = photo_service_order($tenant, $userA);

    // Cria foto pertencente ao técnico A
    $photo = ServiceOrderPhoto::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'service_order_id' => $orderA->id,
        'user_id' => $userA->id,
        'disk' => 'local',
        'path' => 'tenants/1/photo.jpg',
        'original_filename' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
        'uploaded_at' => now(),
        'version' => 1,
    ]);

    photo_set_context($tenant);

    $response = $this->withToken($tokenB)
        ->withHeader('X-Device-Id', 'device-b')
        ->getJson("/api/mobile/sync/photo/{$photo->id}/signed-url");

    $response->assertStatus(403);
});

// ---------------------------------------------------------------------------
// 6. Gerente consegue obter signed url de foto de qualquer técnico do tenant
// ---------------------------------------------------------------------------

test('gerente consegue obter signed url da foto do tecnico', function (): void {
    Storage::fake('local');

    $tenant = photo_tenant();
    $technician = photo_technician($tenant);
    $manager = photo_manager($tenant);
    $managerToken = photo_token($manager, $tenant, 'device-manager');

    $order = photo_service_order($tenant, $technician);

    $photo = ServiceOrderPhoto::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'service_order_id' => $order->id,
        'user_id' => $technician->id,
        'disk' => 'local',
        'path' => 'tenants/1/photo.jpg',
        'original_filename' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
        'uploaded_at' => now(),
        'version' => 1,
    ]);

    photo_set_context($tenant);

    $response = $this->withToken($managerToken)
        ->withHeader('X-Device-Id', 'device-manager')
        ->getJson("/api/mobile/sync/photo/{$photo->id}/signed-url");

    $response->assertOk();
    $response->assertJsonStructure(['url', 'expires_at']);
});

// ---------------------------------------------------------------------------
// 7. Soft-delete: foto deletada não aparece para o técnico via signed-url
// ---------------------------------------------------------------------------

test('foto soft-deleted nao e encontrada via signed url', function (): void {
    $tenant = photo_tenant();
    $user = photo_technician($tenant);
    $token = photo_token($user, $tenant);

    $order = photo_service_order($tenant, $user);

    $photo = ServiceOrderPhoto::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'service_order_id' => $order->id,
        'user_id' => $user->id,
        'disk' => 'local',
        'path' => 'tenants/1/photo.jpg',
        'original_filename' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
        'uploaded_at' => now(),
        'version' => 1,
    ]);

    $photo->delete(); // soft-delete

    photo_set_context($tenant);

    $response = $this->withToken($token)
        ->withHeader('X-Device-Id', 'device-photo-test')
        ->getJson("/api/mobile/sync/photo/{$photo->id}/signed-url");

    // Foto soft-deletada não deve ser encontrada (404) — dados preservados no banco, mas invisível
    $response->assertStatus(404);
});

// ---------------------------------------------------------------------------
// 8. Multi-tenant hijack: user do tenant B não consegue obter signed url de foto do tenant A
// ---------------------------------------------------------------------------

test('user do tenant B nao consegue obter signed url de foto do tenant A', function (): void {
    $tenantA = photo_tenant();
    $tenantB = photo_tenant();
    $userA = photo_technician($tenantA);
    $userB = photo_technician($tenantB);
    $tokenB = photo_token($userB, $tenantB, 'device-b-hijack');

    $orderA = photo_service_order($tenantA, $userA);

    $photo = ServiceOrderPhoto::withoutGlobalScope('current_tenant')->create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantA->id,
        'service_order_id' => $orderA->id,
        'user_id' => $userA->id,
        'disk' => 'local',
        'path' => 'tenants/1/photo.jpg',
        'original_filename' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
        'uploaded_at' => now(),
        'version' => 1,
    ]);

    // Contexto do tenant B — tenta buscar foto do tenant A pelo ID
    photo_set_context($tenantB);

    $response = $this->withToken($tokenB)
        ->withHeader('X-Device-Id', 'device-b-hijack')
        ->getJson("/api/mobile/sync/photo/{$photo->id}/signed-url");

    $response->assertStatus(404);
});
