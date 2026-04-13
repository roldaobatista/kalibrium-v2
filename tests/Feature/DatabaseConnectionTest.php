<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// AC-001: migrate executa sem erros em PostgreSQL
test('sanity check table exists after migration', function () {
    expect(Schema::hasTable('_sanity_check'))->toBeTrue();
})->group('integration');

// AC-003: current_setting('rls.enabled', true) returns without exception
test('rls.enabled session parameter is queryable without exception', function () {
    // AC-003: the query must not throw. The second param `true` makes PostgreSQL
    // return null instead of throwing if the GUC doesn't exist as a persisted setting.
    // This validates the infrastructure is ready for RLS (E02 will create policies).
    $result = DB::select("SELECT current_setting('rls.enabled', true) as rls");
    expect($result)->toHaveCount(1);
})->group('integration');

// AC-005: migrate:status lista sanity check como Ran
test('migrate:status shows sanity check migration as ran', function () {
    $this->artisan('migrate:status')
        ->expectsOutputToContain('create_sanity_check_table')
        ->assertExitCode(0);
})->group('integration');
