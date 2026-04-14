<?php

namespace Database\Factories;

use App\Models\PlanUpgradeRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanUpgradeRequest>
 */
class PlanUpgradeRequestFactory extends Factory
{
    protected $model = PlanUpgradeRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'feature_code' => 'fiscal',
            'justification' => fake()->sentence(),
            'status' => 'requested',
            'requested_at' => now(),
        ];
    }
}
