<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantPlanMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantPlanMetric>
 */
class TenantPlanMetricFactory extends Factory
{
    protected $model = TenantPlanMetric::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'users_used' => 1,
            'monthly_os_used' => 0,
            'storage_used_bytes' => 0,
            'sampled_at' => now(),
        ];
    }
}
