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
        $metric = TenantPlanMetric::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($metric !== null) {
            return $metric;
        }

        return new TenantPlanMetric([
            'tenant_id' => $tenant->id,
            'users_used' => TenantUser::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->count(),
            'monthly_os_used' => 0,
            'storage_used_bytes' => 0,
            'sampled_at' => null,
        ]);
    }
}
