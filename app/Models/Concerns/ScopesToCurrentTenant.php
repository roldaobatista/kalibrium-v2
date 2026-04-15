<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tenant;
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
