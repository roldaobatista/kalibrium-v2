<?php

declare(strict_types=1);

/**
 * Slice 003 — AC-001, AC-002, AC-003 (estrutura YAML).
 *
 * Verifica que o trigger `push` do ci.yml cobre feature branches
 * E que o job específico de cada AC existe no workflow.
 */
function ciYmlContent(): string
{
    return file_get_contents(base_path('.github/workflows/ci.yml'));
}

function pushCoversAllBranches(string $content): bool
{
    return (bool) preg_match(
        '/on:\s.*?push:\s.*?branches:\s*\[[\'"]\*\*[\'"]\]/s',
        $content,
    );
}

test('AC-001: trigger push cobre feature branches E job php-lint existe', function (): void {
    $content = ciYmlContent();

    expect(pushCoversAllBranches($content))->toBeTrue(
        'AC-001 requer push.branches: ["**"] para que o job lint dispare em feature branches.'
    );

    expect(str_contains($content, 'php-lint:'))->toBeTrue(
        'AC-001 requer que o job php-lint exista no ci.yml.'
    );

    expect(str_contains($content, 'vendor/bin/pint --test'))->toBeTrue(
        'AC-001 requer que o job php-lint execute pint --test.'
    );
})->group('slice-003', 'ac-001');

test('AC-002: trigger push cobre feature branches E job php-static existe', function (): void {
    $content = ciYmlContent();

    expect(pushCoversAllBranches($content))->toBeTrue(
        'AC-002 requer push.branches: ["**"] para que php-static dispare em feature branches.'
    );

    expect(str_contains($content, 'php-static:'))->toBeTrue(
        'AC-002 requer que o job php-static exista no ci.yml.'
    );

    expect(str_contains($content, 'vendor/bin/phpstan analyse'))->toBeTrue(
        'AC-002 requer que o job php-static execute phpstan analyse.'
    );
})->group('slice-003', 'ac-002');

test('AC-003: trigger push cobre feature branches E job php-test existe', function (): void {
    $content = ciYmlContent();

    expect(pushCoversAllBranches($content))->toBeTrue(
        'AC-003 requer push.branches: ["**"] para que php-test dispare em feature branches.'
    );

    expect(str_contains($content, 'php-test:'))->toBeTrue(
        'AC-003 requer que o job php-test exista no ci.yml.'
    );

    expect(str_contains($content, 'vendor/bin/pest --ci'))->toBeTrue(
        'AC-003 requer que o job php-test execute pest --ci.'
    );
})->group('slice-003', 'ac-003');
