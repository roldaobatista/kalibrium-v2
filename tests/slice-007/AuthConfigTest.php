<?php

declare(strict_types=1);

test('AC-021: config padrao do Sanctum separa explicitamente os dominios stateful', function (): void {
    $sanctumConfig = file_get_contents(config_path('sanctum.php')) ?: '';

    expect($sanctumConfig)->not->toContain("'%s%s'");
    expect($sanctumConfig)->toContain("ltrim(Sanctum::currentApplicationUrlWithPort(), ',')");
    expect(config('sanctum.stateful'))
        ->not->toContain('')
        ->not->toContain('::1localhost');
})->group('slice-007', 'ac-021', 'security');
