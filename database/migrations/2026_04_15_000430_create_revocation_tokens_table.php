<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revocation_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('consent_subject_id')->constrained('consent_subjects')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('token_hash', 64);
            $table->timestampTz('expires_at');
            $table->timestampTz('granted_at')->nullable();
            $table->timestampTz('used_at')->nullable();
            $table->timestamps();
            $table->index(['token_hash']);
            $table->index(['tenant_id', 'consent_subject_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revocation_tokens');
    }
};
