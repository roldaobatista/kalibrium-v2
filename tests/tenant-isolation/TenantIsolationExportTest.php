<?php

declare(strict_types=1);

/**
 * Suite de isolamento — Exports / Relatórios (AC-004, AC-013)
 *
 * Inspeciona payload de exports gerados no contexto do tenant A.
 * Asserta ausência de IDs e valores do tenant B.
 * Cobre soft-deleted cross-tenant (AC-013).
 */

use App\Models\ConsentSubject;
use Illuminate\Support\Facades\DB;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-004: Export gerado no tenant A não contém dados do tenant B
// ---------------------------------------------------------------------------

/**
 * @ac AC-004
 */
dataset('export_routes', function () {
    return [
        'PlansPage list'           => ['/plans', 'GET'],
        'Consent subjects list'    => ['/settings/privacy/consent-subjects', 'GET'],
        'LGPD categories list'     => ['/settings/privacy/lgpd-categories', 'GET'],
    ];
});

test('AC-004: payload de export/relatório autenticado no tenant A não contém IDs ou valores do tenant B', function (string $route, string $method) {
    /** @ac AC-004 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $this->ensureExportFixture($tenantA, $tenantB);

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->call($method, $route);

    $statusCode = $response->getStatusCode();

    if ($statusCode === 404) {
        $this->markTestIncomplete(
            "AC-004: Rota {$method} {$route} retornou 404. ".
            'Implemente a rota antes de rodar este AC.'
        );
    }

    expect($statusCode)
        ->toBe(200, "Rota {$method} {$route} retornou HTTP {$statusCode} — esperado 200.");

    $content = $response->getContent() ?? '';

    expect($content)
        ->not->toContain((string) $tenantB->id,
            "AC-004: Export {$route} contém o ID do tenant B ({$tenantB->id})."
        );

    if (! empty($tenantB->name)) {
        expect($content)
            ->not->toContain((string) $tenantB->name,
                "AC-004: Export {$route} contém o nome do tenant B ('{$tenantB->name}')."
            );
    }

    $userBEmail = $this->userB()->email ?? '';
    if (! empty($userBEmail)) {
        expect($content)
            ->not->toContain($userBEmail,
                "AC-004: Export {$route} contém o email do usuário B ('{$userBEmail}')."
            );
    }
})->with('export_routes');

// ---------------------------------------------------------------------------
// AC-013: Export com withTrashed() não inclui soft-deleted do tenant B
// ---------------------------------------------------------------------------

test('AC-013: ConsentSubject::withTrashed() no contexto do tenant A não retorna registros soft-deleted do tenant B', function () {
    /** @ac AC-013 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Insere soft-deleted no tenant B diretamente
    DB::table('consent_subjects')->insert([
        'tenant_id'  => $tenantB->id,
        'email'      => 'trashed-b-'.uniqid().'@tenant-isolation.test',
        'name'       => 'Trashed Tenant B Subject',
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => now(),
    ]);

    tenancy()->initialize($tenantA);

    try {
        $results = ConsentSubject::withTrashed()->get();
        $leaked = $results->filter(fn ($r) => $r->tenant_id === $tenantB->id);

        expect($leaked->count())
            ->toBe(0,
                "AC-013: ConsentSubject::withTrashed() retornou {$leaked->count()} registro(s) ".
                'soft-deleted do tenant B no contexto do tenant A. '.
                'O global scope deve filtrar por tenant_id mesmo com withTrashed().'
            );
    } finally {
        tenancy()->end();
    }
});

test('AC-013: endpoint de export com include_deleted=true não expõe soft-deleted do tenant B', function () {
    /** @ac AC-013 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $trashedId = DB::table('consent_subjects')->insertGetId([
        'tenant_id'  => $tenantB->id,
        'email'      => 'endpoint-trashed-'.uniqid().'@tenant-isolation.test',
        'name'       => 'Endpoint Trashed B',
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => now(),
    ]);

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/settings/privacy/consent-subjects?include_deleted=true');

    if ($response->getStatusCode() === 404) {
        $this->markTestIncomplete(
            'AC-013: Endpoint /consent-subjects?include_deleted=true não existe. '.
            'Implemente antes deste AC.'
        );
    }

    $content = $response->getContent() ?? '';

    expect($content)
        ->not->toContain((string) $trashedId,
            "AC-013: Export com include_deleted=true expôs ID {$trashedId} do soft-deleted do tenant B."
        );

    expect($content)
        ->not->toContain('Endpoint Trashed B',
            'AC-013: Export expôs nome do soft-deleted do tenant B.'
        );
});
