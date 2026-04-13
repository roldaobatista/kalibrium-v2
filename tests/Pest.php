<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Pest bootstrap file. Binds the Laravel TestCase for Feature tests.
*/

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('slice-003');
uses(TestCase::class)->in('slice-004');
uses(TestCase::class)->in('slice-005');
uses(TestCase::class)->in('slice-006');
