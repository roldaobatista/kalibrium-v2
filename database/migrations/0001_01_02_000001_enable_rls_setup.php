<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('setup/enable-rls.sql');

        if (! file_exists($path)) {
            throw new RuntimeException("RLS setup script not found: {$path}");
        }

        $sql = file_get_contents($path);
        DB::unprepared($sql);
    }

    public function down(): void
    {
        DB::statement("SELECT set_config('rls.enabled', 'false', false)");
    }
};
