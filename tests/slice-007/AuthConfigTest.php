<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('AC-021: config padrao do Sanctum separa explicitamente os dominios stateful', function (): void {
    expect(config('sanctum.stateful'))
        ->not->toContain('')
        ->not->toContain('::1localhost');
})->group('slice-007', 'ac-021', 'security');

test('AC-021: DatabaseSeeder nao cria conta demo autenticavel fora do ambiente local', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'production');
    $before = User::query()
        ->where('email', 'test@example.com')
        ->count();

    app(DatabaseSeeder::class)->run();

    $this->assertDatabaseHas('roles', [
        'name' => 'gerente',
    ]);
    expect(User::query()
        ->where('email', 'test@example.com')
        ->count())->toBe($before);
})->group('slice-007', 'ac-021', 'security');
