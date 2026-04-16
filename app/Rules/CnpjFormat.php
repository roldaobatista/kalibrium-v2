<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates CNPJ format and check-digits only (no uniqueness check).
 * Use this for contexts where uniqueness is handled separately (e.g., clientes table).
 */
final readonly class CnpjFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $documentNumber = Cnpj::normalize(is_scalar($value) ? (string) $value : '');

        if (! Cnpj::hasValidDigits($documentNumber)) {
            $fail('Informe um CNPJ valido.');
        }
    }
}
