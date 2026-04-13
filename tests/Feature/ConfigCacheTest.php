<?php

// AC-004: config:cache executa sem erros
test('config:cache completes without errors', function () {
    $this->artisan('config:cache')
        ->assertExitCode(0);

    // Clean up cached config to not affect other tests
    $this->artisan('config:clear');
})->group('mutates-config');
