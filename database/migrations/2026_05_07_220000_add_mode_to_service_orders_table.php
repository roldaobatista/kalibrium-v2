<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table): void {
            $table->string('mode', 30)->default('bench')->after('status')
                ->comment('bench = bancada, field_vehicle = campo-veiculo, field_umc = campo-UMC');
            $table->index(['tenant_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'mode']);
            $table->dropColumn('mode');
        });
    }
};
