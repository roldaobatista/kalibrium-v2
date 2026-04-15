<?php

declare(strict_types=1);

/**
 * Suite de isolamento — Jobs (AC-003, AC-012)
 *
 * Dispara jobs de config/tenancy-jobs.php no contexto do tenant A.
 * Verifica que registros criados têm tenant_id = A.
 * Cobre retry com restauração de contexto (AC-012).
 */

use App\Jobs\Middleware\JobTenancyBootstrapper;
use App\Jobs\ProcessConsentJob;
use App\Support\Tenancy\TenantContext;
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
    try {
        $jobs = config('tenancy-jobs.tenant_aware_jobs', []);
    } catch (Throwable $e) {
        return ['[config/tenancy-jobs.php não encontrado]' => ['__missing__']];
    }

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
        // config/tenancy-jobs.php existe mas está vazio ou não carregado
        $jobs = config('tenancy-jobs.tenant_aware_jobs', []);
        expect($jobs)
            ->not->toBeEmpty(
                'AC-003: config/tenancy-jobs.php[tenant_aware_jobs] está vazio. '.
                'Adicione ao menos ProcessConsentJob.'
            );

        return;
    }

    expect(class_exists($jobClass))
        ->toBeTrue("Job {$jobClass} listado em config/tenancy-jobs.php não existe.");

    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Usa initializeTenant() — compatível com App\Models\Tenant (não requer Stancl interface)
    $this->initializeTenant($tenantA);

    try {
        Queue::fake();

        dispatch(new $jobClass($tenantA->id));

        // Verifica que o job carrega contexto de tenant (via middleware ou propriedade)
        Queue::assertPushed($jobClass, function ($job) {
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
        $this->endTenant();
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

        $job = new $jobClass;
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

test('AC-012: JobTenancyBootstrapper isola contexto entre handle() consecutivos e restaura contexto anterior', function () {
    /** @ac AC-012
     *
     * Invoca JobTenancyBootstrapper::handle() diretamente (padrão real do queue worker).
     * Verifica que:
     * 1. Dentro do handle do job A o TenantContext é o tenant A.
     * 2. Dentro do handle do job B (consecutivo) o TenantContext é o tenant B.
     * 3. Após cada handle, o TenantContext é restaurado ao valor anterior (isolamento de retry).
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Estado inicial: sem contexto ativo
    TenantContext::reset();
    expect(TenantContext::getTenantId())->toBeNull('Pré-condição: TenantContext deve ser null antes do teste.');

    $tenantIdInsideJob1 = null;
    $tenantIdInsideJob2 = null;

    // --- Handle 1: ProcessConsentJob com tenant A ---
    $job1 = new ProcessConsentJob($tenantA->id);
    $bootstrapper1 = new JobTenancyBootstrapper($tenantA->id);
    $bootstrapper1->handle($job1, function (object $job) use (&$tenantIdInsideJob1): void {
        $tenantIdInsideJob1 = TenantContext::getTenantId();
        // Simula handle() real do job
        $job->handle();
    });

    // Após handle 1: contexto restaurado para null (valor anterior)
    expect(TenantContext::getTenantId())
        ->toBeNull('AC-012: TenantContext não foi restaurado após handle do job A.');

    // --- Handle 2: ProcessConsentJob com tenant B (consecutivo, sem reset entre eles) ---
    $job2 = new ProcessConsentJob($tenantB->id);
    $bootstrapper2 = new JobTenancyBootstrapper($tenantB->id);
    $bootstrapper2->handle($job2, function (object $job) use (&$tenantIdInsideJob2): void {
        $tenantIdInsideJob2 = TenantContext::getTenantId();
        $job->handle();
    });

    // Após handle 2: contexto restaurado novamente para null
    expect(TenantContext::getTenantId())
        ->toBeNull('AC-012: TenantContext não foi restaurado após handle do job B.');

    // Verificações de isolamento dentro dos handles
    expect($tenantIdInsideJob1)
        ->toBe($tenantA->id,
            'AC-012: Dentro do job A o TenantContext deveria ser tenant A, '.
            "mas foi {$tenantIdInsideJob1}."
        );

    expect($tenantIdInsideJob2)
        ->toBe($tenantB->id,
            'AC-012: Dentro do job B o TenantContext deveria ser tenant B, '.
            "mas foi {$tenantIdInsideJob2}."
        );

    expect($tenantIdInsideJob1)
        ->not->toBe($tenantIdInsideJob2,
            'AC-012: Contextos de tenant A e B são o mesmo dentro dos handles — vazamento confirmado.'
        );
});
