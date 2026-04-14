<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\Tenant;
use App\Models\TenantPlanMetric;
use App\Models\TenantUser;

final class TenantPlanMetricsUpdater
{
    public function refreshForTenant(Tenant $tenant): TenantPlanMetric
    {
        $metric = TenantPlanMetric::query()->firstOrNew(['tenant_id' => $tenant->id]);
        $metric->forceFill([
            'users_used' => TenantUser::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->count(),
            'monthly_os_used' => $metric->exists ? $metric->monthly_os_used : 0,
            'storage_used_bytes' => $metric->exists ? $metric->storage_used_bytes : 0,
            'sampled_at' => now(),
        ])->save();

        return $metric;
    }
}
