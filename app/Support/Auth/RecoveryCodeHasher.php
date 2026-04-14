<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Support\Facades\Hash;

final class RecoveryCodeHasher
{
    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public static function hashMany(array $codes): array
    {
        return array_map(
            static fn (string $code): string => Hash::make($code),
            $codes
        );
    }

    /**
     * @param  array<int, mixed>  $hashedCodes
     */
    public static function matchingHash(string $code, array $hashedCodes): ?string
    {
        foreach ($hashedCodes as $hashedCode) {
            if (is_string($hashedCode) && Hash::check($code, $hashedCode)) {
                return $hashedCode;
            }
        }

        return null;
    }
}
