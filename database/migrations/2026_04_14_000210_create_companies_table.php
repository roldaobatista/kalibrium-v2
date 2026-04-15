<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('legal_name');
            $table->string('document_number', 14)->nullable();
            $table->string('trade_name')->nullable();
            $table->boolean('is_root')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_number'], 'companies_tenant_document_unique');
            $table->index(['tenant_id', 'is_root']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE UNIQUE INDEX companies_one_root_per_tenant ON companies (tenant_id) WHERE is_root = true;
                ALTER TABLE companies ENABLE ROW LEVEL SECURITY;
                ALTER TABLE companies FORCE ROW LEVEL SECURITY;
                CREATE POLICY companies_tenant_isolation ON companies
                    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
                    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
            SQL);
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('CREATE UNIQUE INDEX companies_one_root_per_tenant ON companies (tenant_id) WHERE is_root = 1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
