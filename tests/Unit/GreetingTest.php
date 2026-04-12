<?php

use App\Utils\Greeting;

// AC-001: greet('Alice') retorna "Hello, Alice!"
test('greet returns Hello name', function () {
    $greeting = new Greeting();
    expect($greeting->greet('Alice'))->toBe('Hello, Alice!');
});

// AC-001: greet com outro nome
test('greet returns Hello with any name', function () {
    $greeting = new Greeting();
    expect($greeting->greet('Bob'))->toBe('Hello, Bob!');
});

// AC-002: greet com string vazia retorna "Hello, World!"
test('greet with empty string returns Hello World', function () {
    $greeting = new Greeting();
    expect($greeting->greet(''))->toBe('Hello, World!');
});
