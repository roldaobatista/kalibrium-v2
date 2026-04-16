<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = self::normalize(is_scalar($value) ? (string) $value : '');

        if (! self::hasValidDigits($digits)) {
            $fail('Informe um CPF valido.');
        }
    }

    public static function normalize(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private static function hasValidDigits(string $digits): bool
    {
        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits) === 1) {
            return false;
        }

        // First check digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $digits[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $firstDigit = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $digits[9] !== $firstDigit) {
            return false;
        }

        // Second check digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $digits[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $secondDigit = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $digits[10] === $secondDigit;
    }
}
