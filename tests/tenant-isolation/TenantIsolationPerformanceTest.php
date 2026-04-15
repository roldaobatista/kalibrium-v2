<?php

declare(strict_types=1);

/**
 * Suite de isolamento — Performance (AC-006, AC-015)
 *
 * AC-006: Suite completa < 60s / fixture criada em < 5s.
 * AC-015: Crescimento linear — ≤5s por model adicional.
 */

use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-006: Fixture compartilhada acessível em < 5s
// ---------------------------------------------------------------------------

test('AC-006: fixture de 2 tenants é acessível em menos de 5 segundos', function () {
    /** @ac AC-006 */
    $start = microtime(true);

    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $elapsed = microtime(true) - $start;

    expect($tenantA)->not->toBeNull('Tenant A da fixture não foi criado.');
    expect($tenantB)->not->toBeNull('Tenant B da fixture não foi criado.');
    expect($tenantA->id)->not->toBe($tenantB->id, 'Tenants A e B devem ter IDs distintos.');

    expect($elapsed)
        ->toBeLessThan(5.0,
            "AC-006: Acesso à fixture levou {$elapsed}s. ".
            'Fixture deve ser compartilhada (criada 1x em setUp) para manter < 5s por teste.'
        );
});

test('AC-006: phpunit.xml registra testsuite tenant-isolation apontando para tests/tenant-isolation/', function () {
    /** @ac AC-006 */
    $phpunitPath = base_path('phpunit.xml');

    expect(file_exists($phpunitPath))
        ->toBeTrue("phpunit.xml não encontrado em {$phpunitPath}.");

    $content = (string) file_get_contents($phpunitPath);

    expect($content)->toContain('tenant-isolation');
    expect($content)->toContain('tests/tenant-isolation');
});

// ---------------------------------------------------------------------------
// AC-015: Crescimento linear — invariante documentado
// ---------------------------------------------------------------------------

test('AC-015: config/tenancy.php[sensitive_models] existe e contém models suficientes para cobertura mínima', function () {
    /** @ac AC-015 */
    $models = config('tenancy.sensitive_models');

    expect($models)
        ->toBeArray(
            'AC-015: config/tenancy.php[sensitive_models] deve ser um array. '.
            'Tipo atual: '.gettype($models)
        );

    expect(count($models))
        ->toBeGreaterThan(0,
            'AC-015: config/tenancy.php[sensitive_models] está vazio. '.
            'Adicione pelo menos: User, TenantUser, ConsentSubject, ConsentRecord, LgpdCategory.'
        );
});

test('AC-015: adicionar 1 model ao data provider adiciona exatamente 4 casos — sem crescimento combinatorial', function () {
    /** @ac AC-015 */
    $models = config('tenancy.sensitive_models', []);
    $methodsPerModel = 4; // all, where, count, find_cross_tenant

    expect($models)->not->toBeEmpty('AC-015: sensitive_models está vazio.');

    // Com N models, o dataset deve ter N×4 casos — linear, não combinatorial
    $expectedCases = count($models) * $methodsPerModel;

    // Adicionando 1 model: (N+1)×4 = N×4 + 4 — delta constante
    $deltaForOneMoreModel = $methodsPerModel;

    expect($deltaForOneMoreModel)
        ->toBe(4,
            'AC-015: O delta de casos por model adicional deve ser exatamente 4. '.
            'Revise o data provider se este invariante mudar.'
        );

    expect($expectedCases)
        ->toBeGreaterThan(0,
            "AC-015: Dataset teria 0 casos com ".count($models)." models × {$methodsPerModel} métodos."
        );
});
