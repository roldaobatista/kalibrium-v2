<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('tipo_pessoa', 2);
            $table->string('documento', 18);
            $table->string('razao_social', 255);
            $table->string('nome_fantasia', 255)->nullable();
            $table->string('regime_tributario', 20)->nullable();
            $table->decimal('limite_credito', 15, 2)->nullable();
            $table->string('logradouro', 255)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->char('uf', 2)->nullable();
            $table->string('cep', 8)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email', 254)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->constrained('tenant_users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('tenant_users')->restrictOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            // Composite indexes
            $table->index(['tenant_id', 'razao_social']);
            $table->index(['tenant_id', 'ativo']);
            $table->index(['deleted_at']);
        });

        // CHECK constraints
        DB::unprepared("ALTER TABLE clientes ADD CONSTRAINT clientes_tipo_pessoa_check CHECK (tipo_pessoa IN ('PJ', 'PF'))");
        DB::unprepared("ALTER TABLE clientes ADD CONSTRAINT clientes_regime_tributario_check CHECK (regime_tributario IS NULL OR regime_tributario IN ('simples', 'presumido', 'real', 'mei', 'isento'))");
        DB::unprepared('ALTER TABLE clientes ADD CONSTRAINT clientes_limite_credito_check CHECK (limite_credito IS NULL OR limite_credito >= 0)');

        // Partial unique index for document uniqueness per tenant (only active/non-deleted)
        DB::unprepared('CREATE UNIQUE INDEX clientes_tenant_documento_unique ON clientes (tenant_id, documento) WHERE deleted_at IS NULL');

        // RLS policy
        DB::unprepared('ALTER TABLE clientes ENABLE ROW LEVEL SECURITY');
        DB::unprepared("CREATE POLICY clientes_tenant_isolation ON clientes USING (tenant_id = current_setting('app.current_tenant_id')::bigint)");
    }

    public function down(): void
    {
        DB::unprepared('DROP POLICY IF EXISTS clientes_tenant_isolation ON clientes');
        Schema::dropIfExists('clientes');
    }
};
