<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrder>
 */
final class ServiceOrderFactory extends Factory
{
    protected $model = ServiceOrder::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'client_name' => $this->faker->company(),
            'instrument_description' => $this->faker->words(3, true),
            'status' => 'received',
            'notes' => null,
            'version' => 1,
            'last_modified_by_device' => null,
        ];
    }
}
