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
            'cliente_id' => ['sometimes', 'integer'],
        ];
    }

    /**
     * Add after-validation rule: payload must contain at least one editable field.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $hasEditableField = false;
            foreach (self::EDITABLE_FIELDS as $field) {
                if ($this->has($field)) {
                    $hasEditableField = true;
                    break;
                }
            }

            if (! $hasEditableField) {
                $v->errors()->add('fields', 'Ao menos um campo editavel deve ser enviado.');
            }
        });
    }
}
