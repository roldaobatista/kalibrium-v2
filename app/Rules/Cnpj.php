<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Tenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class Cnpj implements ValidationRule
{
    public function __construct(
        private ?int $tenantId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $documentNumber = self::normalize(is_scalar($value) ? (string) $value : '');

        if (! self::hasValidDigits($documentNumber)) {
            $fail('Informe um CNPJ valido.');

            return;
        }

        $query = Tenant::query()->where('document_number', $documentNumber);

        if ($this->tenantId !== null) {
            $query->whereKeyNot($this->tenantId);
        }

        if ($query->exists()) {
            $fail('Informe um CNPJ valido para este laboratorio.');
        }
    }

    public static function normalize(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public static function hasValidDigits(string $documentNumber): bool
    {
        if (strlen($documentNumber) !== 14 || preg_match('/^(\d)\1{13}$/', $documentNumber) === 1) {
            return false;
        }

        $firstDigit = self::digit(substr($documentNumber, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = self::digit(substr($documentNumber, 0, 12).$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $documentNumber[12] === (string) $firstDigit
            && $documentNumber[13] === (string) $secondDigit;
    }

    /**
     * @param  array<int, int>  $weights
     */
    private static function digit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (int) $base[$index] * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
