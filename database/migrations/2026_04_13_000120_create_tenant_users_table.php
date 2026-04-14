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
        Schema::create('tenant_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('tecnico');
            $table->string('status')->default('active');
            $table->boolean('requires_2fa')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['status', 'role']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                ALTER TABLE tenant_users ENABLE ROW LEVEL SECURITY;
                ALTER TABLE tenant_users FORCE ROW LEVEL SECURITY;
                CREATE POLICY tenant_users_tenant_isolation ON tenant_users
                    USING (
                        tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint
                        OR user_id = NULLIF(current_setting('app.auth_user_id', true), '')::bigint
                    )
                    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
