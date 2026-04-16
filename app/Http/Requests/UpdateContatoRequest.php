<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateContatoRequest extends FormRequest
{
    private const array EDITABLE_FIELDS = [
        'nome',
        'email',
        'whatsapp',
        'papel',
        'principal',
    ];

    /** Campos aceitos no payload mas silenciosamente ignorados (não editáveis). */
    private const array IGNORED_FIELDS = [
        'cliente_id',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:254'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^\d{10,20}$/'],
            'papel' => ['sometimes', 'string', Rule::in(['comprador', 'responsavel_tecnico', 'financeiro', 'outro'])],
            'principal' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Add after-validation rule: payload must contain at least one editable field.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $knownFields = array_merge(self::EDITABLE_FIELDS, self::IGNORED_FIELDS);
            $hasKnownField = false;
            foreach ($knownFields as $field) {
                if ($this->has($field)) {
                    $hasKnownField = true;
                    break;
                }
            }

            if (! $hasKnownField) {
                $v->errors()->add('fields', 'Ao menos um campo editavel deve ser enviado.');
            }
        });
    }
}
