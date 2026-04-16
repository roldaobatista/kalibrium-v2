<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\ConsentRecord;
use App\Models\ConsentSubject;
use App\Models\LgpdCategory;
use App\Models\LoginAuditLog;
use App\Models\PlanUpgradeRequest;
use App\Models\RevocationToken;
use App\Models\TenantAuditLog;
use App\Models\TenantPlanMetric;
use App\Models\TenantUser;

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
        Cliente::class,
        TenantUser::class,
        ConsentSubject::class,
        ConsentRecord::class,
        LgpdCategory::class,
        TenantPlanMetric::class,
        TenantAuditLog::class,
        PlanUpgradeRequest::class,
        RevocationToken::class,
        LoginAuditLog::class,
        Branch::class,
        Company::class,
    ],
];
