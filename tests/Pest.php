<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Pest bootstrap file. Binds the Laravel TestCase for Feature tests.
*/

uses(TestCase::class)->in('Feature');
