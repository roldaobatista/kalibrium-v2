<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConsentSubject;
use App\Models\RevocationToken;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevocationToken>
 */
class RevocationTokenFactory extends Factory
{
    protected $model = RevocationToken::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $raw = bin2hex(random_bytes(32));

        return [
            'tenant_id' => Tenant::factory(),
            'consent_subject_id' => ConsentSubject::factory(),
            'channel' => 'email',
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addDays(30),
            'granted_at' => now(),
            'used_at' => null,
        ];
    }
}
