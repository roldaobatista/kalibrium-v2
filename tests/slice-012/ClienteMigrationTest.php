<?php

declare(strict_types=1);
use Database\Seeders\ClienteSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 012 — E03-S01a: Teste de migration e seeder (AC-009)
 *
 * Red natural: tabela `clientes` nao existe e artisan falha.
 */
uses(TestCase::class)->group('slice-012', 'cliente-migration');

// ---------------------------------------------------------------------------
// AC-009: migrate:fresh --seed executa com exit 0, tabela existe, seeder cria dados
// ---------------------------------------------------------------------------

test('AC-009: tabela clientes existe apos migration com todas as colunas do ERD', function () {
    /** @ac AC-009 */
    $columns = Schema::getColumnListing('clientes');

    expect($columns)->not->toBeEmpty('Tabela clientes nao existe ou nao tem colunas.');

    $expectedColumns = [
        'id',
        'tenant_id',
        'tipo_pessoa',
        'documento',
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
        'observacoes',
        'ativo',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    foreach ($expectedColumns as $col) {
        expect($columns)->toContain($col);
    }
});

test('AC-009: seeder cria ao menos um cliente por tenant de exemplo', function () {
    /** @ac AC-009 */
    $tenantCount = DB::table('tenants')->count();

    expect($tenantCount)->toBeGreaterThan(0, 'Nenhum tenant de exemplo encontrado. Seeders anteriores podem ter falhado.');

    // Roda o seeder para garantir que todos os tenants existentes recebam clientes.
    // O seeder usa updateOrInsert (idempotente), portanto é seguro re-executar.
    $this->seed(ClienteSeeder::class);

    $tenantIds = DB::table('tenants')->pluck('id');

    foreach ($tenantIds as $tenantId) {
        $clienteCount = DB::table('clientes')
            ->where('tenant_id', $tenantId)
            ->count();

        expect($clienteCount)->toBeGreaterThan(
            0,
            "Tenant ID {$tenantId} nao possui nenhum cliente. ClienteSeeder deve criar ao menos 1 por tenant."
        );
    }
});

test('AC-009: partial unique index existe na tabela clientes', function () {
    /** @ac AC-009 */
    $indexExists = DB::select(
        "SELECT 1 FROM pg_indexes WHERE tablename = 'clientes' AND indexdef LIKE '%documento%' AND indexdef LIKE '%WHERE%' LIMIT 1"
    );

    expect($indexExists)->not->toBeEmpty(
        'Partial unique index em (tenant_id, documento) WHERE deleted_at IS NULL nao encontrado na tabela clientes.'
    );
});
