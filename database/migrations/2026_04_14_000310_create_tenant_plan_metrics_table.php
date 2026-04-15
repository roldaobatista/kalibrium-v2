<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('status')->default('active');
            $table->date('trial_ends_on')->nullable();
            $table->date('current_period_ends_on')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('plan_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->cascadeOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->string('feature_code')->nullable();
            $table->string('limit_key')->nullable();
            $table->unsignedBigInteger('limit_value')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->index(['plan_id', 'feature_code']);
            $table->index(['limit_key']);
        });

        Schema::create('tenant_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->string('feature_code')->nullable();
            $table->unsignedBigInteger('limit_value')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'feature_code']);
        });

        Schema::create('tenant_plan_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('users_used')->default(0);
            $table->unsignedBigInteger('monthly_os_used')->default(0);
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->timestamp('sampled_at')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (['subscriptions', 'tenant_entitlements', 'tenant_plan_metrics'] as $table) {
                DB::unprepared(<<<SQL
                    ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY;
                    ALTER TABLE {$table} FORCE ROW LEVEL SECURITY;
                    CREATE POLICY {$table}_tenant_isolation ON {$table}
                        USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
                        WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
                SQL);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plan_metrics');
        Schema::dropIfExists('tenant_entitlements');
        Schema::dropIfExists('plan_entitlements');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('features');
        Schema::dropIfExists('plans');
    }
};
