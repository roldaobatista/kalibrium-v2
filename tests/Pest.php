<?php

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
uses(TestCase::class)->in('slice-007');
uses(TestCase::class)->in('slice-008');
uses(TestCase::class, DatabaseTransactions::class)->in('slice-009');
uses(TestCase::class)->in('slice-010');

uses()->beforeEach(function (): void {
    TenantContext::reset();
})->in('slice-009');
// slice-012: uses TenantIsolationTestCase per-file (ClienteCreationTest, ClienteSoftDeleteTest, ClienteUniquenessTest)
// slice-013: uses TenantIsolationTestCase per-file
