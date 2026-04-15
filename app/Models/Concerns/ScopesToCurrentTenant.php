<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait ScopesToCurrentTenant
{
    protected static function bootScopesToCurrentTenant(): void
    {
        static::addGlobalScope('current_tenant', static function (Builder $builder): void {
            $tenantId = self::currentTenantIdForGlobalScope();
            if ($tenantId === null) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
        });
    }

    private static function currentTenantIdForGlobalScope(): ?int
    {
        // 1. stancl/tenancy inicializado via tenancy()->initialize() — suporta testes e jobs
        if (function_exists('tenancy') && tenancy()->initialized) {
            $stanclTenant = tenant();
            if ($stanclTenant !== null) {
                $key = $stanclTenant->getTenantKey();
                if (is_numeric($key)) {
                    return (int) $key;
                }
            }
        }

        // 2. TenantContext estático — seguro em queue workers (sem ciclo HTTP)
        $contextId = TenantContext::getTenantId();
        if ($contextId !== null) {
            return $contextId;
        }

        // 3. Contexto de request HTTP (middleware SetCurrentTenantContext) — fallback
        if (! app()->bound('request')) {
            return null;
        }

        $tenant = request()->attributes->get('current_tenant');
        if ($tenant instanceof Tenant) {
            return (int) $tenant->id;
        }

        $tenantId = request()->attributes->get('current_tenant_id');
        if (is_numeric($tenantId)) {
            return (int) $tenantId;
        }

        return null;
    }
}
