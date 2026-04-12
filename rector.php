<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
    ])
    ->withSkip([
        __DIR__.'/storage',
        __DIR__.'/bootstrap/cache',
        __DIR__.'/vendor',
        __DIR__.'/database/migrations',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_84,
    ]);
