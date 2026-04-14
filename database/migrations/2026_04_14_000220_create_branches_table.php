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
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('document_number', 14)->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->boolean('is_root')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'name'], 'branches_tenant_company_name_unique');
            $table->index(['tenant_id', 'is_root']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE UNIQUE INDEX branches_one_root_per_tenant ON branches (tenant_id) WHERE is_root = true;
                ALTER TABLE branches ENABLE ROW LEVEL SECURITY;
                ALTER TABLE branches FORCE ROW LEVEL SECURITY;
                CREATE POLICY branches_tenant_isolation ON branches
                    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
                    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
            SQL);
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('CREATE UNIQUE INDEX branches_one_root_per_tenant ON branches (tenant_id) WHERE is_root = 1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
