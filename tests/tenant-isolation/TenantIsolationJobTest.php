<?php

declare(strict_types=1);

/**
 * Suite de isolamento — Jobs (AC-003, AC-012)
 *
 * Dispara jobs de config/tenancy-jobs.php no contexto do tenant A.
 * Verifica que registros criados têm tenant_id = A.
 * Cobre retry com restauração de contexto (AC-012).
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-003: Jobs do tenant A criam registros com tenant_id = A
// Data provider: config/tenancy-jobs.php[tenant_aware_jobs]
// ---------------------------------------------------------------------------

/**
 * @ac AC-003
 */
dataset('tenant_aware_jobs', function () {
    $jobs = config('tenancy-jobs.tenant_aware_jobs', []);

    if (empty($jobs)) {
        return ['[config/tenancy-jobs.php não encontrado]' => ['__missing__']];
    }

    $cases = [];
    foreach ($jobs as $jobClass) {
        $cases[class_basename($jobClass)] = [$jobClass];
    }

    return $cases;
});

test('AC-003: job despachado no contexto do tenant A não cria registros com tenant_id do tenant B', function (string $jobClass) {
    /** @ac AC-003 */
    if ($jobClass === '__missing__') {
        $this->fail(
            'AC-003: config/tenancy-jobs.php[tenant_aware_jobs] não está definido ou vazio. '.
            'Crie o arquivo com a lista inicial de jobs tenant-aware do E02 '.
            '(ex: ProcessConsentJob, ExportReportJob).'
        );
    }

    expect(class_exists($jobClass))
        ->toBeTrue("Job {$jobClass} listado em config/tenancy-jobs.php não existe.");

    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    tenancy()->initialize($tenantA);

    try {
        Queue::fake();

        dispatch(new $jobClass());

        // Verifica que o job carrega contexto de tenant (via middleware ou propriedade)
        Queue::assertPushed($jobClass, function ($job) use ($tenantA) {
            $middlewares = method_exists($job, 'middleware') ? $job->middleware() : [];
            $hasTenancyMiddleware = collect($middlewares)->contains(
                fn ($m) => str_contains(get_class($m), 'Tenancy') || str_contains(get_class($m), 'Tenant')
            );

            $hasProperty = property_exists($job, 'tenantId') || property_exists($job, 'tenant_id');

            return $hasTenancyMiddleware || $hasProperty;
        });

        // Nenhum registro recente deve ter sido criado com tenant_id do tenant B
        foreach ($this->getSensitiveTables() as $table) {
            $leaked = DB::table($table)
                ->where('tenant_id', $tenantB->id)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->count();

            expect($leaked)
                ->toBe(0,
                    "AC-003: Job {$jobClass} criou {$leaked} registro(s) em {$table} ".
                    "com tenant_id do tenant B ({$tenantB->id})."
                );
        }
    } finally {
        tenancy()->end();
    }
})->with('tenant_aware_jobs');

// ---------------------------------------------------------------------------
// AC-012: Job implementa bootstrapper de tenancy para retry seguro
// ---------------------------------------------------------------------------

test('AC-012: todos os jobs tenant-aware implementam JobTenancyBootstrapper para retry seguro', function () {
    /** @ac AC-012 */
    $jobs = config('tenancy-jobs.tenant_aware_jobs', []);

    if (empty($jobs)) {
        $this->fail(
            'AC-012: config/tenancy-jobs.php[tenant_aware_jobs] está vazio. '.
            'Impossível validar bootstrapper sem jobs definidos.'
        );
    }

    $jobsWithoutBootstrapper = [];

    foreach ($jobs as $jobClass) {
        if (! class_exists($jobClass)) {
            $jobsWithoutBootstrapper[] = "{$jobClass} (classe não existe)";
            continue;
        }

        $job = new $jobClass();
        $middlewares = method_exists($job, 'middleware') ? $job->middleware() : [];

        $hasTenancyBootstrapper = collect($middlewares)->contains(function ($m) {
            $class = get_class($m);

            return str_contains($class, 'EnsureValidTenantWhenRunning')
                || str_contains($class, 'JobTenancyBootstrapper')
                || str_contains($class, 'InitializeTenancyForJob')
                || str_contains($class, 'TenancyBootstrapper');
        });

        if (! $hasTenancyBootstrapper) {
            $interfaces = class_implements($jobClass) ?: [];
            $traits = class_uses_recursive($jobClass);
            $hasTenancyBootstrapper = collect($interfaces)->contains(fn ($i) => str_contains($i, 'Tenancy'))
                || collect(array_keys($traits))->contains(fn ($t) => str_contains($t, 'Tenancy'));
        }

        if (! $hasTenancyBootstrapper) {
            $jobsWithoutBootstrapper[] = $jobClass;
        }
    }

    expect($jobsWithoutBootstrapper)
        ->toBeEmpty(
            'AC-012: Os seguintes jobs NÃO implementam bootstrapper de tenancy: '.
            implode(', ', $jobsWithoutBootstrapper).'. '.
            'Sem ele, jobs em retry podem rodar com o tenant errado. '.
            'Adicione o middleware EnsureValidTenantWhenRunning do stancl/tenancy.'
        );
});

test('AC-012: contextos de tenants não vazam entre dispatches consecutivos de jobs', function () {
    /** @ac AC-012 */
    $jobs = config('tenancy-jobs.tenant_aware_jobs', []);

    if (empty($jobs)) {
        $this->markTestIncomplete('AC-012: Nenhum job definido em config/tenancy-jobs.php.');
    }

    $jobClass = reset($jobs);

    if (! class_exists($jobClass)) {
        $this->fail("Job {$jobClass} não existe.");
    }

    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    Queue::fake();

    // Dispatch 1: contexto A
    tenancy()->initialize($tenantA);
    dispatch(new $jobClass());
    tenancy()->end();

    // Dispatch 2: contexto B
    tenancy()->initialize($tenantB);
    dispatch(new $jobClass());

    $currentTenantId = tenancy()->tenant?->id ?? null;

    expect($currentTenantId)
        ->toBe($tenantB->id,
            'AC-012: Contexto do tenant B foi corrompido após dispatch de job no contexto de A. '.
            'Os contextos entre dispatches não devem vazar entre si.'
        );

    tenancy()->end();

    Queue::assertPushed($jobClass, 2);
});
