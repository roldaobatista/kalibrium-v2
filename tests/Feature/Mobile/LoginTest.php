<?php

declare(strict_types=1);

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(DatabaseTransactions::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mobile_url(): string
{
    return '/api/mobile/login';
}

function mobile_user(string $password = 'SenhaSegura123!'): User
{
    return User::factory()->create([
        'password' => Hash::make($password),
    ]);
}

function mobile_payload(User $user, array $overrides = []): array
{
    return array_merge([
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
    $user = mobile_user();

    $response = $this->postJson(mobile_url(), mobile_payload($user, [
        'password' => 'senha-errada',
    ]));

    $response->assertStatus(401);
    $response->assertJsonFragment(['erro' => 'Email ou senha incorretos']);
});

test('credenciais corretas com device novo criam MobileDevice pending e retornam 202', function (): void {
    $user = mobile_user();

    $response = $this->postJson(mobile_url(), mobile_payload($user));

    $response->assertStatus(202);
    $response->assertJsonFragment(['status' => 'aguardando_aprovacao']);

    $this->assertDatabaseHas('mobile_devices', [
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
        'status' => MobileDeviceStatus::Pending->value,
    ]);
});

test('device ja pending retorna 202 e atualiza last_seen_at sem duplicar', function (): void {
    $user = mobile_user();

    $device = MobileDevice::factory()->pending()->create([
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
        'last_seen_at' => now()->subHour(),
    ]);

    $beforeCount = MobileDevice::where('user_id', $user->id)->count();

    $response = $this->postJson(mobile_url(), mobile_payload($user));

    $response->assertStatus(202);
    $response->assertJsonFragment(['status' => 'aguardando_aprovacao']);

    expect(MobileDevice::where('user_id', $user->id)->count())->toBe($beforeCount);

    $device->refresh();
    expect($device->last_seen_at)->not->toBeNull();
    expect($device->last_seen_at->isAfter(now()->subMinute()))->toBeTrue();
});

test('device approved retorna 200 com token Sanctum', function (): void {
    $user = mobile_user();

    MobileDevice::factory()->approved()->create([
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user));

    $response->assertStatus(200);
    $response->assertJsonStructure(['status', 'token', 'user']);
    $response->assertJsonFragment(['status' => 'ok']);

    $token = $response->json('token');
    expect($token)->toBeString()->not->toBeEmpty();
});

test('token Sanctum retornado tem expires_at por volta de 4 dias', function (): void {
    $user = mobile_user();

    MobileDevice::factory()->approved()->create([
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user));

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
    $user = mobile_user();

    MobileDevice::factory()->revoked()->create([
        'user_id' => $user->id,
        'device_identifier' => 'test-device-abc123',
    ]);

    $response = $this->postJson(mobile_url(), mobile_payload($user));

    $response->assertStatus(403);
    $response->assertJsonFragment(['erro' => 'Este celular foi bloqueado pelo gerente. Entre em contato com ele.']);
});

test('sexta tentativa errada retorna 429', function (): void {
    $user = mobile_user();
    $ip = '127.0.0.1';
    $cacheKey = 'mobile_login_rate_limit:'.$ip;

    // Pre-popula o cache com 5 tentativas (limite atingido)
    Cache::put($cacheKey, 5, 15 * 60);

    $response = $this->postJson(mobile_url(), mobile_payload($user, ['password' => 'errada']));

    $response->assertStatus(429);

    Cache::forget($cacheKey);
});
