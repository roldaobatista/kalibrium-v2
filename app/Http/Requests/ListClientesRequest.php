<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListClientesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // RBAC is enforced in ClienteController via Gate::authorize('clientes.viewAny').
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('ativo')) {
            $this->merge([
                'ativo' => filter_var($this->input('ativo'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'tipo_pessoa' => ['nullable', 'string', Rule::in(['PJ', 'PF'])],
            'ativo' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => [
                'nullable',
                'string',
                Rule::in(['razao_social', '-razao_social', 'created_at', '-created_at']),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sort.in' => 'O campo sort deve ser um dos valores aceitos: razao_social, -razao_social, created_at, -created_at.',
        ];
    }
}
