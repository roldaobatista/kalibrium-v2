<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConsentSubject;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentSubject>
 */
class ConsentSubjectFactory extends Factory
{
    protected $model = ConsentSubject::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subject_type' => 'external_user',
            'subject_id' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => null,
        ];
    }
}
