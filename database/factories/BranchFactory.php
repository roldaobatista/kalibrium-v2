<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'document_number' => null,
            'city' => fake()->city(),
            'state' => 'MT',
            'is_root' => false,
        ];
    }
}
