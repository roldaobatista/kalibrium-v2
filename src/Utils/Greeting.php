<?php

namespace App\Utils;

class Greeting
{
    public function greet(string $name): string
    {
        if ($name === '') {
            return 'Hello, World!';
        }

        return "Hello, {$name}!";
    }
}
