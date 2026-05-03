<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
