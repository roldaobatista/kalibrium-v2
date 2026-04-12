<?php

// AC-002: db:check retorna exit 0 com JSON correto
test('db:check returns connected status for db and redis', function () {
    $this->artisan('db:check')
        ->expectsOutput('{"db":"connected","redis":"connected"}')
        ->assertExitCode(0);
});
