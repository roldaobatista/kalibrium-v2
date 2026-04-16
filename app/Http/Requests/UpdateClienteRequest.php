<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateClienteRequest extends FormRequest
{
    /** Label -> slug mapping for regime_tributario */
    private const array REGIME_MAP = [
        'Simples' => 'simples',
        'Lucro Presumido' => 'presumido',
        'Lucro Real' => 'real',
        'MEI' => 'mei',
        'Isento' => 'isento',
    ];

    private const array EDITABLE_FIELDS = [
        'razao_social',
        'nome_fantasia',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'regime_tributario',
        'limite_credito',
    ];

    public function authorize(): bool
    {
        // RBAC is enforced in ClienteController via Gate::authorize('clientes.update').
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'razao_social' => ['sometimes', 'string', 'max:255'],
            'nome_fantasia' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logradouro' => ['sometimes', 'string', 'max:255'],
            'numero' => ['sometimes', 'string', 'max:20'],
            'complemento' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bairro' => ['sometimes', 'string', 'max:100'],
            'cidade' => ['sometimes', 'string', 'max:100'],
            'uf' => ['sometimes', 'string', 'size:2'],
            'cep' => ['sometimes', 'string', 'max:9'],
            'regime_tributario' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(array_merge(
                    ['simples', 'presumido', 'real', 'mei', 'isento'],
                    array_keys(self::REGIME_MAP),
                )),
            ],
            'limite_credito' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
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

    /**
     * Normalize regime_tributario label -> slug and cep (remove hyphens).
     *
     * @return array<string, mixed>
     */
    public function validatedForStorage(): array
    {
        $data = $this->validated();

        // Normalize regime_tributario label to slug
        if (isset($data['regime_tributario']) && isset(self::REGIME_MAP[$data['regime_tributario']])) {
            $data['regime_tributario'] = self::REGIME_MAP[$data['regime_tributario']];
        }

        // Remove hyphens from cep
        if (isset($data['cep'])) {
            $data['cep'] = str_replace('-', '', (string) $data['cep']);
        }

        return $data;
    }
}
