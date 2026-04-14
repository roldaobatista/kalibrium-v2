<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\Tenant;
use App\Models\TenantPlanMetric;
use App\Models\TenantUser;

final class TenantPlanMetricsReader
{
    public function snapshotForTenant(Tenant $tenant): TenantPlanMetric
    {
        $activeUsers = TenantUser::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->count();

        $metric = TenantPlanMetric::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($metric !== null) {
            $attributes = $metric->getAttributes();
            $attributes['users_used'] = $activeUsers;
            $metric->setRawAttributes($attributes, true);

            return $metric;
        }

        return new TenantPlanMetric([
            'tenant_id' => $tenant->id,
            'users_used' => $activeUsers,
            'monthly_os_used' => 0,
            'storage_used_bytes' => 0,
            'sampled_at' => null,
        ]);
    }
}
