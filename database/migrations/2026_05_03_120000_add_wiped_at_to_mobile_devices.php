<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_devices', function (Blueprint $table): void {
            $table->timestamp('wiped_at')->nullable()->after('revoked_at');
            $table->foreignId('wiped_by_user_id')
                ->nullable()
                ->after('wiped_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('wipe_acknowledged_at')->nullable()->after('wiped_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_devices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('wiped_by_user_id');
            $table->dropColumn(['wiped_at', 'wipe_acknowledged_at']);
        });
    }
};
