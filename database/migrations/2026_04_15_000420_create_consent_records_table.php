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
        Schema::create('consent_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('consent_subject_id')->constrained('consent_subjects')->cascadeOnDelete();
            $table->foreignId('lgpd_category_id')->nullable()->constrained('lgpd_categories')->nullOnDelete();
            $table->string('channel', 20);
            $table->string('status', 20);
            $table->timestampTz('granted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('revocation_reason', 50)->nullable();
            // Append-only: trigger PostgreSQL bloqueia UPDATE; Eloquent model
            // tem timestamps=false. Apenas created_at é populado pelo service.
            $table->timestamp('created_at')->nullable();
            $table->index(['consent_subject_id', 'channel', 'created_at']);
            $table->index(['tenant_id', 'consent_subject_id', 'channel', 'status']);
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION consent_records_append_only()
            RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'audit append-only: operacao proibida em consent_records';
            END;
            $$;

            CREATE TRIGGER consent_records_append_only_trigger
                BEFORE UPDATE OR DELETE OR TRUNCATE ON consent_records
                FOR EACH STATEMENT EXECUTE FUNCTION consent_records_append_only();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS consent_records_append_only_trigger ON consent_records');
        DB::unprepared('DROP FUNCTION IF EXISTS consent_records_append_only()');
        Schema::dropIfExists('consent_records');
    }
};
