<?php

declare(strict_types=1);

use App\Livewire\Settings\ConsentSubjectsPage;
use Livewire\Livewire;

require_once __DIR__.'/TestHelpers.php';
require_once __DIR__.'/../slice-009/TestHelpers.php';

uses()->group('slice-010');

// ---------------------------------------------------------------------------
// AC-006: Gerente vê tabela paginada de consent_subjects (50/página, filtro status)
// ---------------------------------------------------------------------------
test('AC-006: pagina consentimentos exibe tabela paginada com 50 linhas por pagina', function (): void {
    $ctx = slice010_manager_context();
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    // Cria 55 subjects com registros de consentimento
    for ($i = 0; $i < 55; $i++) {
        $subjectId = slice010_seed_consent_subject($tenant, [
            'email' => "titular{$i}@example.com",
        ]);
        slice010_seed_consent_record($tenant, $subjectId, [
            'channel' => 'email',
            'status' => $i % 2 === 0 ? 'ativo' : 'revogado',
        ]);
    }

    $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

    Livewire::actingAs($user)
        ->test(ConsentSubjectsPage::class)
        ->assertSet('perPage', 50)
        ->assertCount('subjects', 50); // primeira página = 50 itens
});

test('AC-006: filtro por status=ativo exibe apenas subjects com consentimento ativo', function (): void {
    $ctx = slice010_manager_context();
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    // 3 ativos
    for ($i = 0; $i < 3; $i++) {
        $subjectId = slice010_seed_consent_subject($tenant, [
            'email' => "ativo{$i}@example.com",
        ]);
        slice010_seed_consent_record($tenant, $subjectId, [
            'channel' => 'email',
            'status' => 'ativo',
        ]);
    }

    // 2 revogados
    for ($i = 0; $i < 2; $i++) {
        $subjectId = slice010_seed_consent_subject($tenant, [
            'email' => "revogado{$i}@example.com",
        ]);
        slice010_seed_consent_record($tenant, $subjectId, [
            'channel' => 'email',
            'status' => 'revogado',
        ]);
    }

    $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

    Livewire::actingAs($user)
        ->test(ConsentSubjectsPage::class)
        ->set('statusFilter', 'ativo')
        ->assertCount('subjects', 3);
});

test('AC-006: listagem nao exibe PII raw — apenas identificador opaco, canal, status e data', function (): void {
    $ctx = slice010_manager_context();
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    $email = 'pii-subject@example.com';
    $subjectId = slice010_seed_consent_subject($tenant, ['email' => $email]);
    slice010_seed_consent_record($tenant, $subjectId, [
        'channel' => 'email',
        'status' => 'ativo',
    ]);

    $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

    // A página deve exibir o UUID opaco, não o e-mail em claro
    $response = $this->actingAs($user)
        ->withSession(['current_tenant_id' => $tenant->id])
        ->get('/settings/privacy/consentimentos');

    $body = (string) $response->getContent();
    // E-mail raw não deve aparecer na listagem (apenas identificador opaco)
    expect($body)->not->toContain($email);
    // UUID opaco deve aparecer (truncado ou completo — qualquer representação sem e-mail)
    expect($body)->toContain(substr((string) $subjectId, 0, 8));
});
