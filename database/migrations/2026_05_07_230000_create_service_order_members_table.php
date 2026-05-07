<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 50)->default('technician');
            $table->timestamps();

            $table->unique(['service_order_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_members');
    }
};
