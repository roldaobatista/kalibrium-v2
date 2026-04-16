<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Rules\CnpjFormat;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreClienteRequest extends FormRequest
{
    /** Label -> slug mapping for regime_tributario */
    private const array REGIME_MAP = [
        'Simples' => 'simples',
        'Lucro Presumido' => 'presumido',
        'Lucro Real' => 'real',
        'MEI' => 'mei',
        'Isento' => 'isento',
    ];

    public function authorize(): bool
    {
        // RBAC is enforced in ClienteController via Gate::authorize('clientes.create').
        // FormRequest authorize() returns true — the gate check happens in the controller
        // where current_tenant_user is available from request attributes.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tipoPessoa = $this->input('tipo_pessoa');
        $tenantId = $this->getTenantId();

        // Determine which document rule to apply
        $documentRule = $tipoPessoa === 'PF'
            ? new Cpf
            : new CnpjFormat;

        return [
            'tipo_pessoa' => ['required', 'string', Rule::in(['PJ', 'PF'])],
            'cnpj_cpf' => [
                'required',
                'string',
                $documentRule,
                Rule::unique('clientes', 'documento')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->ignore(null),
            ],
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'logradouro' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['required', 'string', 'max:100'],
            'cidade' => ['required', 'string', 'max:100'],
            'uf' => ['required', 'string', 'size:2'],
            'cep' => ['required', 'string', 'max:8'],
            'regime_tributario' => [
                'nullable',
                'string',
                Rule::in(array_merge(
                    ['simples', 'presumido', 'real', 'mei', 'isento'],
                    array_keys(self::REGIME_MAP),
                )),
            ],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:254'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Prepare the data for validation.
     * Normalize cnpj_cpf to digits-only for the unique rule comparison.
     */
    protected function prepareForValidation(): void
    {
        $cnpjCpf = $this->input('cnpj_cpf');
        if (is_string($cnpjCpf)) {
            $this->merge([
                'cnpj_cpf' => preg_replace('/\D+/', '', $cnpjCpf) ?? '',
            ]);
        }

        // Normalize regime_tributario label to slug
        $regime = $this->input('regime_tributario');
        if (is_string($regime) && isset(self::REGIME_MAP[$regime])) {
            $this->merge([
                'regime_tributario' => self::REGIME_MAP[$regime],
            ]);
        }
    }

    /**
     * Map validated data from API field names to database column names.
     *
     * @return array<string, mixed>
     */
    public function validatedForStorage(): array
    {
        $data = $this->validated();

        // Map cnpj_cpf (API) -> documento (database), normalized to digits only
        $data['documento'] = preg_replace('/\D+/', '', (string) ($data['cnpj_cpf'] ?? '')) ?? '';
        unset($data['cnpj_cpf']);

        return $data;
    }

    private function getTenantId(): ?int
    {
        $tenant = $this->attributes->get('current_tenant');

        if ($tenant instanceof Tenant) {
            return (int) $tenant->id;
        }

        $tenantId = $this->attributes->get('current_tenant_id');

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }
}
