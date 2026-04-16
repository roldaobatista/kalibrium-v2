<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cliente */
final class ClienteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_pessoa' => $this->tipo_pessoa,
            'cnpj_cpf' => $this->documento,
            'razao_social' => $this->razao_social,
            'nome_fantasia' => $this->nome_fantasia,
            'ativo' => $this->ativo,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
