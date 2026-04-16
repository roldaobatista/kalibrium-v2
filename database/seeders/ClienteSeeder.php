<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            // Get first tenant_user for this tenant (for created_by/updated_by)
            $tenantUser = DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($tenantUser === null) {
                continue;
            }

            // Create a PJ client
            DB::table('clientes')->insert([
                'tenant_id' => $tenant->id,
                'tipo_pessoa' => 'PJ',
                'documento' => '11222333000181',
                'razao_social' => 'Empresa Exemplo Ltda',
                'nome_fantasia' => 'Exemplo',
                'regime_tributario' => 'simples',
                'limite_credito' => 10000.00,
                'logradouro' => 'Rua das Industrias',
                'numero' => '100',
                'complemento' => null,
                'bairro' => 'Centro',
                'cidade' => 'Sao Paulo',
                'uf' => 'SP',
                'cep' => '01310100',
                'telefone' => null,
                'email' => 'contato@exemplo.com.br',
                'observacoes' => null,
                'ativo' => true,
                'created_by' => $tenantUser->id,
                'updated_by' => $tenantUser->id,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            // Create a PF client
            DB::table('clientes')->insert([
                'tenant_id' => $tenant->id,
                'tipo_pessoa' => 'PF',
                'documento' => '52998224725',
                'razao_social' => 'Joao da Silva',
                'nome_fantasia' => null,
                'regime_tributario' => 'isento',
                'limite_credito' => null,
                'logradouro' => 'Rua Principal',
                'numero' => '50',
                'complemento' => null,
                'bairro' => 'Centro',
                'cidade' => 'Rio de Janeiro',
                'uf' => 'RJ',
                'cep' => '20040020',
                'telefone' => null,
                'email' => null,
                'observacoes' => null,
                'ativo' => true,
                'created_by' => $tenantUser->id,
                'updated_by' => $tenantUser->id,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }
    }
}
