<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_changes', function (Blueprint $table): void {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('entity_type');
            $table->string('entity_id');
            $table->enum('action', ['create', 'update', 'delete']);
            $table->json('payload_before')->nullable();
            $table->json('payload_after')->nullable();
            $table->string('source_device_id')->nullable();
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at');

            $table->index(['tenant_id', 'ulid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_changes');
    }
};
