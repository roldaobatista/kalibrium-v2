<?php

declare(strict_types=1);

namespace App\Support\Auth;

final class AuthPayloadSanitizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        unset($payload['email']);

        foreach ([
            'password',
            'token',
            'code',
            'recovery_code',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ] as $sensitiveField) {
            if (array_key_exists($sensitiveField, $payload)) {
                $payload[$sensitiveField] = '[REDACTED]';
            }
        }

        return $payload;
    }
}
