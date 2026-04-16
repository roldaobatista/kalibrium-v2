<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreContatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:254'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^\d{10,20}$/'],
            'papel' => ['required', 'string', Rule::in(['comprador', 'responsavel_tecnico', 'financeiro', 'outro'])],
            'principal' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Add after-validation rule: at least email or whatsapp must be present.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $email = $this->input('email');
            $whatsapp = $this->input('whatsapp');

            $hasEmail = $email !== null && $email !== '';
            $hasWhatsapp = $whatsapp !== null && $whatsapp !== '';

            if (! $hasEmail && ! $hasWhatsapp) {
                $v->errors()->add('email', 'Ao menos email ou whatsapp deve ser informado.');
            }
        });
    }
}
