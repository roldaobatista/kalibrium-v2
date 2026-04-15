<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_upgrade_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('feature_code');
            $table->text('justification')->nullable();
            $table->string('status')->default('requested');
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'feature_code']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                ALTER TABLE plan_upgrade_requests ENABLE ROW LEVEL SECURITY;
                ALTER TABLE plan_upgrade_requests FORCE ROW LEVEL SECURITY;
                CREATE POLICY plan_upgrade_requests_tenant_isolation ON plan_upgrade_requests
                    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
                    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_upgrade_requests');
    }
};
