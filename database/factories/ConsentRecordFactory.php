<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConsentRecord;
use App\Models\ConsentSubject;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentRecord>
 */
class ConsentRecordFactory extends Factory
{
    protected $model = ConsentRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'consent_subject_id' => ConsentSubject::factory(),
            'lgpd_category_id' => null,
            'channel' => $this->faker->randomElement(['email', 'whatsapp']),
            'status' => 'ativo',
            'granted_at' => now(),
            'revoked_at' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent_hash' => hash('sha256', $this->faker->userAgent()),
            'revocation_reason' => null,
            'created_at' => now(),
        ];
    }
}
