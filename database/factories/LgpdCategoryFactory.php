<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LgpdCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LgpdCategory>
 */
class LgpdCategoryFactory extends Factory
{
    protected $model = LgpdCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'code' => $this->faker->randomElement(LgpdCategory::CODES),
            'name' => $this->faker->words(3, true),
            'legal_basis' => $this->faker->randomElement(LgpdCategory::LEGAL_BASES),
            'retention_policy' => null,
            'comment' => null,
            'created_by_user_id' => User::factory(),
        ];
    }
}
