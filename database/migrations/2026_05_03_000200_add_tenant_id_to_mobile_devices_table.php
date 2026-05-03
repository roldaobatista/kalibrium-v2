<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarda de segurança: tabela deve estar vazia neste estágio do projeto.
        // Se houver registros sem tenant_id, a migration falha rápido para forçar
        // backfill manual antes de adicionar a coluna NOT NULL.
        if (DB::table('mobile_devices')->exists()) {
            throw new RuntimeException(
                'mobile_devices contém registros — limpe ou faça backfill de tenant_id manualmente antes desta migration.'
            );
        }

        Schema::table('mobile_devices', function (Blueprint $table): void {
            // Adiciona tenant_id para garantir isolamento multi-tenant (SEC-001).
            // Sem onUpdate pois tenant_id é UUID imutável por design (PKs de tenant não são atualizadas).
            $table->foreignId('tenant_id')
                ->after('id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Remove índice único anterior (user_id, device_identifier) e substitui por
            // índice que inclui tenant para garantir isolamento cross-tenant.
            $table->dropUnique(['user_id', 'device_identifier']);
            $table->unique(['tenant_id', 'user_id', 'device_identifier']);
        });
    }

    public function down(): void
    {
        Schema::table('mobile_devices', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'user_id', 'device_identifier']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique(['user_id', 'device_identifier']);
        });
    }
};
