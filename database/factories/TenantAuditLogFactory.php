<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantAuditLog>
 */
class TenantAuditLogFactory extends Factory
{
    protected $model = TenantAuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'action' => 'tenant.settings.updated',
            'changed_fields' => ['legal_name'],
            'ip_address' => '127.0.0.1',
            'user_agent_hash' => hash('sha256', 'test-agent'),
        ];
    }
}
