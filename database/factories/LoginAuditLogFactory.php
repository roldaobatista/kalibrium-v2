<?php

namespace Database\Factories;

use App\Models\LoginAuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoginAuditLog>
 */
class LoginAuditLogFactory extends Factory
{
    protected $model = LoginAuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => 'auth.login.success',
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'ip_address' => fake()->ipv4(),
            'user_agent_hash' => hash('sha256', (string) Str::uuid()),
            'context' => null,
        ];
    }
}
