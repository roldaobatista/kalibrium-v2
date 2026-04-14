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
        Schema::create('login_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                ALTER TABLE login_audit_logs ENABLE ROW LEVEL SECURITY;
                ALTER TABLE login_audit_logs FORCE ROW LEVEL SECURITY;
                CREATE POLICY login_audit_logs_tenant_read ON login_audit_logs
                    FOR SELECT
                    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
                CREATE POLICY login_audit_logs_auth_insert ON login_audit_logs
                    FOR INSERT
                    WITH CHECK (
                        tenant_id IS NULL
                        OR tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint
                        OR user_id = NULLIF(current_setting('app.auth_user_id', true), '')::bigint
                    );
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_audit_logs');
    }
};
