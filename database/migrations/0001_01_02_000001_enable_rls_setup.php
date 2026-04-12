<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('setup/enable-rls.sql'));
        DB::unprepared($sql);
    }

    public function down(): void
    {
        DB::statement("SELECT set_config('rls.enabled', 'false', false)");
    }
};
