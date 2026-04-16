<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cliente */
final class ClienteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_pessoa' => $this->tipo_pessoa,
            'cnpj_cpf' => self::formatDocumento((string) $this->documento, (string) $this->tipo_pessoa),
            'razao_social' => $this->razao_social,
            'nome_fantasia' => $this->nome_fantasia,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'uf' => $this->uf,
            'cep' => $this->cep,
            'regime_tributario' => match($this->regime_tributario) {
                'simples'   => 'Simples',
                'presumido' => 'Lucro Presumido',
                'real'      => 'Lucro Real',
                'mei'       => 'MEI',
                'isento'    => 'Isento',
                default     => $this->regime_tributario,
            },
            'limite_credito' => $this->limite_credito,
            'ativo' => $this->ativo,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    // Format and mask documento for LGPD compliance (Art.6 III).
    // CNPJ: XX.XXX.nnn/nnXX-XX  |  CPF: nnn.XXX.XXX-nn
    private static function formatDocumento(string $digits, string $tipoPessoa): string
    {
        if ($tipoPessoa === 'PJ' && strlen($digits) === 14) {
            // Format: XX.XXX.XXX/XXXX-XX then mask middle
            return sprintf(
                '%s.%s.***/%s-**',
                substr($digits, 0, 2),
                substr($digits, 2, 3),
                substr($digits, 8, 4),
            );
        }

        if ($tipoPessoa === 'PF' && strlen($digits) === 11) {
            // Format: XXX.XXX.XXX-XX then mask edges
            return sprintf(
                '***.%s.%s-**',
                substr($digits, 3, 3),
                substr($digits, 6, 3),
            );
        }

        // Fallback: return as-is if unexpected format
        return $digits;
    }
}
