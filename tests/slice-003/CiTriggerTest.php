<?php

declare(strict_types=1);

/**
 * Slice 003 — AC-001, AC-002, AC-003 (estrutura YAML).
 *
 * Verifica que o trigger `push` do ci.yml cobre feature branches.
 */
function ciYmlCoversAllBranches(): bool
{
    $content = file_get_contents(base_path('.github/workflows/ci.yml'));

    return (bool) preg_match(
        '/on:\s.*?push:\s.*?branches:\s*\[[\'"]\*\*[\'"]\]/s',
        $content,
    );
}

test('AC-001: trigger push cobre todas as branches para lint disparar em feature branches', function (): void {
    expect(ciYmlCoversAllBranches())->toBeTrue(
        'AC-001 requer push.branches: ["**"] para que o job lint dispare em feature branches.'
    );
})->group('slice-003', 'ac-001');

test('AC-002: trigger push cobre todas as branches para static-analysis disparar', function (): void {
    expect(ciYmlCoversAllBranches())->toBeTrue(
        'AC-002 requer push.branches: ["**"] para que php-static dispare em feature branches.'
    );
})->group('slice-003', 'ac-002');

test('AC-003: trigger push cobre todas as branches para tests disparar', function (): void {
    expect(ciYmlCoversAllBranches())->toBeTrue(
        'AC-003 requer push.branches: ["**"] para que php-test dispare em feature branches.'
    );
})->group('slice-003', 'ac-003');
