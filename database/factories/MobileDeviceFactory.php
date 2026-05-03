<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileDevice>
 */
class MobileDeviceFactory extends Factory
{
    protected $model = MobileDevice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'user_id' => User::factory(),
            'device_identifier' => 'device-'.fake()->unique()->lexify('??????????'),
            'device_label' => fake()->optional()->randomElement([
                'Samsung Galaxy A54',
                'iPhone 14',
                'Motorola Edge 30',
            ]),
            'status' => MobileDeviceStatus::Pending,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'revoked_at' => null,
            'last_seen_at' => null,
            'wiped_at' => null,
            'wiped_by_user_id' => null,
            'wipe_acknowledged_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => MobileDeviceStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => MobileDeviceStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state([
            'status' => MobileDeviceStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }

    public function wipedAndRevoked(): static
    {
        return $this->state([
            'status' => MobileDeviceStatus::WipedAndRevoked,
            'revoked_at' => now(),
            'wiped_at' => now(),
        ]);
    }
}
