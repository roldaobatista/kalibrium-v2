<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// No PostgreSQL, colunas de chave primária não podem ser NULL.
// Usamos tenant_id = 0 como sentinel para tokens criados fora de contexto
// de tenant (ex: fluxo de reset via Fortify no web). O fluxo mobile sempre
// fornece um tenant_id real (> 0).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            // Default 0 = sem tenant (sentinel); registros mobile terão tenant_id > 0.
            $table->unsignedBigInteger('tenant_id')->default(0)->after('email');
            $table->dropPrimary();
            $table->primary(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->dropPrimary();
            $table->dropColumn('tenant_id');
            $table->primary('email');
        });
    }
};
