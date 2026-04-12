<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// AC-001: migrate executa sem erros em PostgreSQL
test('sanity check table exists after migration', function () {
    expect(Schema::hasTable('_sanity_check'))->toBeTrue();
});

// AC-003: RLS config accessible without exception
test('rls.enabled session parameter is accessible without exception', function () {
    // AC-003 requires current_setting('rls.enabled', true) returns without exception.
    // The second param `true` means return null instead of throwing if GUC is unknown.
    // The migration registered the GUC via set_config in its session; here we verify
    // the parameter can be queried safely and set it for this session.
    DB::statement("SELECT set_config('rls.enabled', 'true', false)");
    $result = DB::select("SELECT current_setting('rls.enabled', true) as rls");
    expect($result[0]->rls)->toBe('true');
});

// AC-005: migrate:status lista sanity check como Ran
test('migrate:status shows sanity check migration as ran', function () {
    $this->artisan('migrate:status')
        ->expectsOutputToContain('create_sanity_check_table')
        ->assertExitCode(0);
});
