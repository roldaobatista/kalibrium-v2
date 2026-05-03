<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SyncChange;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyncChange>
 */
final class SyncChangeFactory extends Factory
{
    protected $model = SyncChange::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'tenant_id' => Tenant::factory(),
            'entity_type' => 'note',
            'entity_id' => (string) Str::uuid(),
            'action' => $this->faker->randomElement(['create', 'update', 'delete']),
            'payload_before' => null,
            'payload_after' => ['title' => $this->faker->sentence(3), 'body' => $this->faker->paragraph()],
            'source_device_id' => 'device-factory-test',
            'source_user_id' => User::factory(),
            'applied_at' => now(),
        ];
    }
}
