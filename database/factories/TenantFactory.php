<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => null,
            'document_number' => null,
            'trade_name' => null,
            'main_email' => null,
            'phone' => null,
            'operational_profile' => null,
            'emits_metrological_certificate' => false,
            'status' => 'active',
        ];
    }
}
