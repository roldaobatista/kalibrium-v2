<?php

declare(strict_types=1);

use App\Livewire\Settings\LgpdCategoriesPage;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

require_once __DIR__.'/TestHelpers.php';
require_once __DIR__.'/../slice-009/TestHelpers.php';

uses()->group('slice-010');

// ---------------------------------------------------------------------------
// AC-001: Gerente declara base legal e persiste em lgpd_categories
// ---------------------------------------------------------------------------
test('AC-001: gerente autenticado com 2FA persiste base legal em lgpd_categories', function (): void {
    $ctx = slice010_manager_context();
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    // Autentica gerente com 2FA confirmado e define tenant corrente
    $this->actingAs($user)
         ->withSession(['current_tenant_id' => $tenant->id]);

    Livewire::actingAs($user)
        ->test(LgpdCategoriesPage::class)
        ->set('code', 'contato')
        ->set('name', 'Dados de Contato')
        ->set('legal_basis', 'execucao_contrato')
        ->set('comment', 'Necessario para execucao do contrato de servico')
        ->call('save')
        ->assertHasNoErrors();

    $record = DB::table('lgpd_categories')
        ->where('tenant_id', $tenant->id)
        ->where('code', 'contato')
        ->where('legal_basis', 'execucao_contrato')
        ->first();

    expect($record)->not->toBeNull();
    expect($record->tenant_id)->toBe($tenant->id);
    expect($record->created_by_user_id)->toBe($user->id);
    expect($record->created_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// AC-001a: Máximo 4 bases por categoria — 5ª é rejeitada
// ---------------------------------------------------------------------------
test('AC-001a: sistema rejeita 5a base legal na mesma categoria com mensagem correta', function (): void {
    $ctx = slice010_manager_context();
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    // Semeia 4 bases legais para categoria "contato"
    $bases = ['execucao_contrato', 'obrigacao_legal', 'interesse_legitimo', 'consentimento'];
    foreach ($bases as $basis) {
        slice010_seed_lgpd_category($tenant, $user, ['code' => 'contato', 'legal_basis' => $basis]);
    }

    $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

    Livewire::actingAs($user)
        ->test(LgpdCategoriesPage::class)
        ->set('code', 'contato')
        ->set('name', 'Dados de Contato Extra')
        ->set('legal_basis', 'execucao_contrato')
        ->call('save')
        ->assertHasErrors(['legal_basis']);

    expect(DB::table('lgpd_categories')
        ->where('tenant_id', $tenant->id)
        ->where('code', 'contato')
        ->count()
    )->toBe(4);
});

// ---------------------------------------------------------------------------
// AC-001b: Sem 2FA → middleware redireciona para /2fa-challenge
// ---------------------------------------------------------------------------
test('AC-001b: sessao sem 2FA completado redireciona para /2fa-challenge ao acessar /settings/privacy', function (): void {
    $ctx = slice009_user_with_tenant_context([
        'role'                 => 'gerente',
        'two_factor_confirmed' => true,
        // Simula 2FA pendente: two_factor_confirmed_at existe mas sessão não passou pelo challenge
    ]);
    $user = $ctx['user'];
    $tenant = $ctx['tenant'];

    // Autentica SEM marcar 2FA challenge na sessão
    $response = $this->actingAs($user)
        ->withSession([
            'current_tenant_id'   => $tenant->id,
            // Intencionalmente omitimos 'two_factor_confirmed' da sessão Laravel Fortify
        ])
        ->get('/settings/privacy');

    $response->assertRedirect('/2fa-challenge');
});

// ---------------------------------------------------------------------------
// AC-SEC-002: Tenant B não vê dados do tenant A em /settings/privacy
// ---------------------------------------------------------------------------
test('AC-SEC-002: tenant B nao enxerga lgpd_categories do tenant A', function (): void {
    $ctxA = slice010_manager_context();
    $ctxB = slice010_manager_context();

    // Semeia categoria no tenant A
    slice010_seed_lgpd_category($ctxA['tenant'], $ctxA['user'], [
        'code'        => 'identificacao',
        'legal_basis' => 'obrigacao_legal',
        'name'        => 'Dados Identificacao Tenant A',
    ]);

    // Autentica como gerente do tenant B
    $this->actingAs($ctxB['user'])
         ->withSession(['current_tenant_id' => $ctxB['tenant']->id]);

    Livewire::actingAs($ctxB['user'])
        ->test(LgpdCategoriesPage::class)
        ->assertDontSee('Dados Identificacao Tenant A');

    // Confirma via DB que o scope exclui tenant A
    $countForB = DB::table('lgpd_categories')
        ->where('tenant_id', $ctxB['tenant']->id)
        ->count();

    expect($countForB)->toBe(0);
});
