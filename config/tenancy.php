<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Models sensíveis para isolamento cross-tenant
    |--------------------------------------------------------------------------
    |
    | Lista de model classes que possuem tenant_id e devem estar cobertos pela
    | suite de isolamento (TenantIsolationModelTest). Adicionar aqui qualquer
    | model novo que armazene dados de tenant. A suite falha explicitamente se
    | algum model desta lista não usar BelongsToTenant/ScopesToCurrentTenant.
    |
    */
    'sensitive_models' => [
        \App\Models\TenantUser::class,
        \App\Models\ConsentSubject::class,
        \App\Models\ConsentRecord::class,
        \App\Models\LgpdCategory::class,
        \App\Models\TenantPlanMetric::class,
        \App\Models\TenantAuditLog::class,
        \App\Models\PlanUpgradeRequest::class,
        \App\Models\RevocationToken::class,
        \App\Models\LoginAuditLog::class,
        \App\Models\Branch::class,
        \App\Models\Company::class,
    ],
];
