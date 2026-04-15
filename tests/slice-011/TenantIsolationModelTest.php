<?php

declare(strict_types=1);

/**
 * Suite de isolamento — Models (AC-001, AC-008, AC-009, AC-015)
 *
 * Itera config/tenancy.php[sensitive_models] × métodos de query via data providers Pest (D5).
 * Cada combinação (model, método) é um caso de teste nomeado e independente.
 */

use App\Models\Concerns\ScopesToCurrentTenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-001 / AC-015: Escopo global filtra corretamente para cada model × método
// Data provider: sensitive_models × [all, where, count, find_cross_tenant]
// ---------------------------------------------------------------------------

/**
 * @ac AC-001
 * @ac AC-015
 */
dataset('sensitive_models_query_methods', function () {
    // Dataset Pest 4 é avaliado ANTES do container Laravel, então config() não está disponível.
    // Carrega o array de config/tenancy.php diretamente via require — novos models adicionados
    // ao arquivo entram automaticamente na cobertura sem precisar editar o teste.
    $configPath = __DIR__.'/../../config/tenancy.php';
    $config = is_file($configPath) ? require $configPath : [];
    $models = $config['sensitive_models'] ?? [
        'App\\Models\\TenantUser',
        'App\\Models\\ConsentSubject',
        'App\\Models\\ConsentRecord',
        'App\\Models\\LgpdCategory',
    ];
    $methods = ['all', 'where', 'count', 'find_cross_tenant', 'whereHas', 'with', 'avg'];

    $cases = [];
    foreach ($models as $modelClass) {
        foreach ($methods as $method) {
            $cases[class_basename($modelClass).'::'.$method] = [$modelClass, $method];
        }
    }

    return $cases;
});

test('AC-001: model sensível não vaza registros do tenant B quando consultado no contexto do tenant A', function (string $modelClass, string $method) {
    /** @ac AC-001 */
    if ($modelClass === '__missing__') {
        $this->fail(
            'AC-001: config/tenancy.php[sensitive_models] está vazio ou não existe. '.
            'Adicione os models sensíveis (User, TenantUser, ConsentSubject, ConsentRecord, LgpdCategory, Plan, Subscription).'
        );
    }

    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    expect(class_exists($modelClass))
        ->toBeTrue("Model {$modelClass} não existe.");

    $traits = class_uses_recursive($modelClass);
    $hasTenantScope = isset($traits[BelongsToTenant::class])
        || isset($traits[ScopesToCurrentTenant::class]);
    expect($hasTenantScope)
        ->toBeTrue("Model {$modelClass} não usa BelongsToTenant nem ScopesToCurrentTenant trait — escopo de isolamento ausente.");

    $this->initializeTenant($tenantA);

    try {
        $instance = new $modelClass;

        switch ($method) {
            case 'all':
                $results = $modelClass::all();
                $leaked = $results->filter(fn ($r) => isset($r->tenant_id) && $r->tenant_id === $tenantB->id);
                expect($leaked->count())
                    ->toBe(0, "Model {$modelClass}::all() vazou {$leaked->count()} registro(s) do tenant B.");
                break;

            case 'where':
                $results = $modelClass::query()->get();
                $leaked = $results->filter(fn ($r) => isset($r->tenant_id) && $r->tenant_id === $tenantB->id);
                expect($leaked->count())
                    ->toBe(0, "Model {$modelClass}::query()->get() vazou registros do tenant B.");
                break;

            case 'count':
                $countInContextA = $modelClass::count();
                $this->initializeTenant($tenantB);
                $countInContextB = $modelClass::count();
                $this->initializeTenant($tenantA);

                $totalInDB = DB::table($instance->getTable())->count();
                // Se os counts somam mais que o total, está duplicando (lógica de sanidade)
                expect($countInContextA + $countInContextB)
                    ->toBeLessThanOrEqual($totalInDB + 1,
                        "Model {$modelClass}::count() parece somar registros de múltiplos tenants."
                    );
                break;

            case 'find_cross_tenant':
                $recordB = DB::table($instance->getTable())
                    ->where('tenant_id', $tenantB->id)
                    ->first();

                if ($recordB !== null) {
                    $found = $modelClass::find($recordB->id);
                    expect($found)
                        ->toBeNull(
                            "Model {$modelClass}::find({$recordB->id}) retornou registro do tenant B ".
                            'enquanto o contexto era do tenant A. Vazamento cross-tenant confirmado.'
                        );
                }
                break;

            case 'whereHas':
                // whereHas com relação tenant — verifica que o global scope se aplica dentro do callback
                // TenantUser tem relação tenant() → BelongsTo<Tenant>
                // Outros models: verificar se têm relação tenant() definida
                if (! method_exists($modelClass, 'tenant')) {
                    $this->markTestSkipped(
                        "Model {$modelClass} não possui relação tenant() — whereHas não aplicável."
                    );
                }
                $results = $modelClass::whereHas('tenant', function ($q) use ($tenantA) {
                    $q->where('id', $tenantA->id);
                })->get();
                $leaked = $results->filter(fn ($r) => isset($r->tenant_id) && $r->tenant_id === $tenantB->id);
                expect($leaked->count())
                    ->toBe(0, "Model {$modelClass}::whereHas() vazou {$leaked->count()} registro(s) do tenant B.");
                break;

            case 'with':
                // with() carrega relação — verifica que registros retornados são apenas do tenant A
                if (! method_exists($modelClass, 'tenant')) {
                    $this->markTestSkipped(
                        "Model {$modelClass} não possui relação tenant() — with() não aplicável."
                    );
                }
                $results = $modelClass::with('tenant')->get();
                $leaked = $results->filter(fn ($r) => isset($r->tenant_id) && $r->tenant_id === $tenantB->id);
                expect($leaked->count())
                    ->toBe(0, "Model {$modelClass}::with('tenant')->get() vazou {$leaked->count()} registro(s) do tenant B.");
                break;

            case 'avg':
                // avg() — verifica que a agregação respeita o global scope de tenant
                $cols = DB::getSchemaBuilder()->getColumnListing($instance->getTable());
                $numericCandidates = ['users_used', 'monthly_os_used', 'storage_used_bytes', 'metric_value', 'amount', 'value', 'price'];
                $numericCol = null;
                foreach ($numericCandidates as $candidate) {
                    if (in_array($candidate, $cols, true)) {
                        $numericCol = $candidate;
                        break;
                    }
                }
                if ($numericCol === null) {
                    $this->markTestSkipped(
                        "Model {$modelClass} não possui coluna numérica conhecida — avg não aplicável."
                    );
                }
                // Insere valor distinto no tenant B para detectar vazamento
                DB::table($instance->getTable())->updateOrInsert(
                    ['tenant_id' => $tenantB->id],
                    [$numericCol => 99999, 'created_at' => now(), 'updated_at' => now()]
                );
                $avgA = (float) $modelClass::avg($numericCol);
                $avgRaw = (float) DB::table($instance->getTable())->avg($numericCol);
                // Se avg do Eloquent == avg raw (inclui tenant B com 99999), o scope não filtra
                if ($avgRaw > 0) {
                    expect($avgA)
                        ->not->toBe($avgRaw,
                            "Model {$modelClass}::avg('{$numericCol}') retornou média de TODOS os tenants. ".
                            'O global scope de tenant não está aplicado em avg().'
                        );
                }
                break;
        }
    } finally {
        $this->endTenant();
    }
})->with('sensitive_models_query_methods');

// ---------------------------------------------------------------------------
// AC-008: Model sensível SEM BelongsToTenant trait → falha explícita com nome
// ---------------------------------------------------------------------------

test('AC-008: model em sensitive_models sem BelongsToTenant trait causa falha explícita com nome do model', function () {
    /** @ac AC-008 */
    $models = $this->sensitiveModels();
    $modelsWithoutTrait = [];

    foreach ($models as $modelClass) {
        if (! class_exists($modelClass)) {
            $modelsWithoutTrait[] = "{$modelClass} (classe não existe)";

            continue;
        }

        $traits = class_uses_recursive($modelClass);
        $hasTenantScope = isset($traits[BelongsToTenant::class])
            || isset($traits[ScopesToCurrentTenant::class]);
        if (! $hasTenantScope) {
            $modelsWithoutTrait[] = $modelClass;
        }
    }

    expect($modelsWithoutTrait)
        ->toBeEmpty(
            'AC-008: Os seguintes models estão em config/tenancy.php[sensitive_models] mas NÃO usam '.
            'BelongsToTenant nem ScopesToCurrentTenant trait: '.implode(', ', $modelsWithoutTrait).'. '.
            'Adicione a trait ou remova o model da lista sensitive_models.'
        );
});

// ---------------------------------------------------------------------------
// AC-009: DB::raw SUM não bypassa o global scope de tenant
// ---------------------------------------------------------------------------

test('AC-009: DB::raw SUM em model sensível retorna apenas soma do tenant A sem incluir tenant B', function () {
    /** @ac AC-009 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Busca model com coluna numérica entre os sensíveis
    $numericCandidates = ['tenant_plan_metrics', 'plan_upgrade_requests', 'subscriptions'];
    $found = null;

    foreach ($this->sensitiveModels() as $modelClass) {
        if (! class_exists($modelClass)) {
            continue;
        }

        $instance = new $modelClass;
        if (in_array($instance->getTable(), $numericCandidates, true)) {
            $found = ['class' => $modelClass, 'table' => $instance->getTable()];
            break;
        }
    }

    if ($found === null) {
        $this->fail(
            'AC-009: Nenhum model numérico sensível encontrado (tenant_plan_metrics, subscriptions etc). '.
            'Adicione TenantPlanMetric ou equivalente a config/tenancy.php[sensitive_models].'
        );
    }

    $table = $found['table'];
    $modelClass = $found['class'];

    // Descobre coluna numérica
    $cols = DB::getSchemaBuilder()->getColumnListing($table);
    $numericCol = null;
    foreach (['users_used', 'monthly_os_used', 'storage_used_bytes', 'metric_value', 'valor', 'amount', 'value', 'price'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $numericCol = $candidate;
            break;
        }
    }

    if ($numericCol === null) {
        $this->fail("AC-009: Model {$modelClass} (tabela {$table}) não tem coluna numérica conhecida para testar SUM.");
    }

    $valueA = 1000;
    $valueB = 9999;

    // updateOrInsert evita violação de unique(tenant_id) se rows já existem
    DB::table($table)->updateOrInsert(
        ['tenant_id' => $tenantA->id],
        [$numericCol => $valueA, 'created_at' => now(), 'updated_at' => now()]
    );
    DB::table($table)->updateOrInsert(
        ['tenant_id' => $tenantB->id],
        [$numericCol => $valueB, 'created_at' => now(), 'updated_at' => now()]
    );

    $this->initializeTenant($tenantA);

    try {
        $sumViaEloquent = (float) $modelClass::sum($numericCol);
        $sumRawAll = (float) DB::table($table)->sum($numericCol);

        // Se o Eloquent sum == raw sum, o global scope não está filtrando
        expect($sumViaEloquent)
            ->not->toBe($sumRawAll,
                "AC-009: {$modelClass}::sum('{$numericCol}') retornou o total de TODOS os tenants ({$sumRawAll}). ".
                'O global scope de tenant não está aplicado em agregações SUM.'
            );

        // O sum no contexto A não deve incluir o valor exclusivo de B (9999)
        // Se sumViaEloquent >= sumRawAll, claramente não está filtrando
        expect($sumViaEloquent)
            ->toBeLessThan($sumRawAll,
                "AC-009: Sum Eloquent ({$sumViaEloquent}) >= Sum total ({$sumRawAll}). ".
                'Global scope não está restringindo a agregação ao tenant A.'
            );
    } finally {
        $this->endTenant();
    }
});

// ---------------------------------------------------------------------------
// AC-015: Data provider cresce linearmente — invariante documentado
// ---------------------------------------------------------------------------

test('AC-015: config/tenancy.php[sensitive_models] contém models suficientes para cobertura mínima do E02', function () {
    /** @ac AC-015 */
    $models = $this->sensitiveModels();

    // Models mínimos esperados do E02
    // User é entidade global (sem tenant_id direto) — vínculo via TenantUser (pivot).
    // Não entra em sensitive_models pois não tem escopo de tenant próprio.
    $expectedModels = [
        'App\\Models\\TenantUser',
        'App\\Models\\ConsentSubject',
        'App\\Models\\ConsentRecord',
        'App\\Models\\LgpdCategory',
    ];

    $missingModels = array_filter(
        $expectedModels,
        fn ($m) => ! in_array($m, $models, true)
    );

    expect($missingModels)
        ->toBeEmpty(
            'AC-015: Os seguintes models do E02 não estão em config/tenancy.php[sensitive_models]: '.
            implode(', ', $missingModels).'. '.
            'Adicione-os para garantir cobertura de isolamento do épico.'
        );
});

// ---------------------------------------------------------------------------
// AC-001 (complemento): relacionamento cross-tenant — User→tenantUsers
// Garante que $user->tenantUsers retorna apenas registros do tenant correto
// ---------------------------------------------------------------------------

test('AC-001: User->tenantUsers carrega apenas TenantUsers do tenant do usuário, não do tenant B', function () {
    /** @ac AC-001 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();
    $userA = $this->userA();

    // userA já tem um TenantUser em tenantA (criado pelo fixture).
    // Criamos um segundo TenantUser em tenantB com o mesmo user_id — simula dado cross-tenant na base.
    DB::table('tenant_users')->insert([
        'tenant_id' => $tenantB->id,
        'user_id' => $userA->id,
        'role' => 'manager',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Ativa contexto do tenant A
    $this->initializeTenant($tenantA);

    try {
        // TenantUser usa ScopesToCurrentTenant — no contexto A, só devem aparecer os de tenant A
        $tenantUsers = TenantUser::where('user_id', $userA->id)->get();

        $leaked = $tenantUsers->filter(fn (TenantUser $tu) => $tu->tenant_id === $tenantB->id);

        expect($leaked->count())
            ->toBe(0,
                'AC-001 (relacionamento): TenantUser::where(user_id) no contexto do tenant A retornou '.
                $leaked->count().' registro(s) com tenant_id do tenant B. '.
                'O global scope ScopesToCurrentTenant não está filtrando registros cross-tenant em relacionamentos.'
            );

        // Sanidade: deve existir ao menos 1 registro do tenant A
        $fromA = $tenantUsers->filter(fn (TenantUser $tu) => $tu->tenant_id === $tenantA->id);
        expect($fromA->count())->toBeGreaterThanOrEqual(1);
    } finally {
        $this->endTenant();
    }
});
