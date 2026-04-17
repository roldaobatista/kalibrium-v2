<?php

declare(strict_types=1);

/**
 * Slice 015 — Spike INF-007: Auditoria de reaproveitamento E01/E02/E03 e validação de stack.
 *
 * Este slice é um spike de investigação — não gera código de produção. Os ACs verificam
 * existência de artefatos documentais e conteúdo semântico, não comportamento de runtime.
 *
 * Os testes aqui exercitam o filesystem (arquivos .md + PoC) como entregável do spike.
 * Todos nascem RED (arquivos ainda não existem); implementer fará-os green ao produzir
 * os artefatos do spike conforme specs/015/plan.md §4.
 *
 * Rastreabilidade AC-ID (ADR-0017 Mudanca 1):
 *   - Cada test() carrega AC-NNN no nome
 *   - Cada test() tem docblock @covers AC-NNN
 *   - Cada test() recebe ->group('slice-015', 'ac-NNN')
 *
 * Referências:
 *   - Spec: specs/015/spec.md
 *   - Plan: specs/015/plan.md (seção 7 — Critérios de "done" mapeia 1:1 AC → verificação)
 *   - Story: epics/E15/stories/E15-S01.md
 *   - ADRs: ADR-0015 (stack offline-first), ADR-0016 (multi-tenancy)
 */

// ---------------------------------------------------------------------------
// Helpers de caminho — todos os caminhos são absolutos a partir da raiz do repo.
// O __DIR__ aqui é tests/slice-015, então ../.. chega na raiz do projeto.
// ---------------------------------------------------------------------------

$repoRoot = static fn (): string => realpath(__DIR__.'/../..') ?: dirname(__DIR__, 2);

// ===========================================================================
// AC-001 — Documento de endpoints mapeados existe e está completo
// ===========================================================================
// "o arquivo lista todos os endpoints dos épicos E01/E02/E03 com: URL, método HTTP,
// headers de autenticação, formato de payload e resposta de exemplo — sem lacunas para
// endpoints de auth, tenants e healthcheck"
// ---------------------------------------------------------------------------

/**
 * @covers AC-001
 */
test('AC-001: docs/frontend/api-endpoints.md existe', function () use ($repoRoot): void {
    $path = $repoRoot().'/docs/frontend/api-endpoints.md';

    expect(file_exists($path))->toBeTrue(
        "AC-001: docs/frontend/api-endpoints.md deve existir como saida do spike (recebido: {$path})."
    );
})->group('slice-015', 'ac-001');

/**
 * @covers AC-001
 */
test('AC-001: api-endpoints.md mapeia auth, tenants e healthcheck', function () use ($repoRoot): void {
    $path = $repoRoot().'/docs/frontend/api-endpoints.md';
    expect(file_exists($path))->toBeTrue("AC-001 pre-req: arquivo {$path} deve existir.");

    $content = (string) file_get_contents($path);

    // AC-001 do spec exige "sem lacunas para endpoints de auth, tenants e healthcheck".
    // Aceitamos referência case-insensitive a cada grupo.
    expect($content)->toMatch('/\b(auth|login|sanctum)\b/i',
        'AC-001: documento deve referenciar endpoint(s) de auth (E02).'
    );
    expect($content)->toMatch('/\btenants?\b/i',
        'AC-001: documento deve referenciar endpoint(s) de tenants (E01).'
    );
    expect($content)->toMatch('/\bhealth(check)?\b/i',
        'AC-001: documento deve referenciar endpoint de healthcheck.'
    );

    // Cada endpoint deve ter método HTTP + URL (AC-001: "URL, método HTTP").
    // Presença de ao menos um método HTTP em tabela/linha.
    expect($content)->toMatch('/\b(GET|POST|PUT|PATCH|DELETE)\b/',
        'AC-001: documento deve declarar metodo HTTP para cada endpoint.'
    );

    // Headers de autenticação devem ser mencionados.
    expect($content)->toMatch('/Authorization|Bearer|Sanctum|X-API-Token/i',
        'AC-001: documento deve declarar headers de autenticacao.'
    );
})->group('slice-015', 'ac-001');

// ===========================================================================
// AC-002 — Versões de pacotes validadas e registradas
// ===========================================================================
// "o arquivo declara as versões exatas de React, TypeScript, Ionic, Capacitor,
// @capacitor-community/sqlite, SQLCipher, Vite e confirma que a combinação não
// tem conflitos de peer dependencies — evidenciado por `npm install` sem erros"
// ---------------------------------------------------------------------------

/**
 * @covers AC-002
 */
test('AC-002: docs/frontend/stack-versions.md existe e declara versoes exatas', function () use ($repoRoot): void {
    $path = $repoRoot().'/docs/frontend/stack-versions.md';
    expect(file_exists($path))->toBeTrue(
        "AC-002: {$path} deve existir como saida do spike."
    );

    $content = (string) file_get_contents($path);

    // Cada pacote da ADR-0015 precisa ser declarado explicitamente.
    $pacotesObrigatorios = [
        'React' => '/\bReact\b/i',
        'TypeScript' => '/\bTypeScript\b/i',
        'Ionic' => '/\bIonic\b/i',
        'Capacitor' => '/\bCapacitor\b/i',
        '@capacitor-community/sqlite' => '/@capacitor-community\/sqlite/i',
        'SQLCipher' => '/\bSQLCipher\b/i',
        'Vite' => '/\bVite\b/i',
    ];

    foreach ($pacotesObrigatorios as $nome => $pattern) {
        expect($content)->toMatch($pattern,
            "AC-002: stack-versions.md deve declarar o pacote {$nome}."
        );
    }

    // Deve haver ao menos uma versao semver declarada (ex: 18.x, 5.0.0, ^8.1).
    expect($content)->toMatch('/\b\d+\.\d+(\.\d+)?(\.x|[-+][\w.]+)?\b/',
        'AC-002: stack-versions.md deve conter ao menos uma versao semver declarada.'
    );
})->group('slice-015', 'ac-002');

/**
 * @covers AC-002
 */
test('AC-002: PoC spike-inf007 possui evidencia de npm install sem erros', function () use ($repoRoot): void {
    // Conforme plan.md §4, o PoC pode viver em spike-inf007/ OU ter evidencia
    // transcrita inline em stack-versions.md seção "Evidência `npm install`".
    // Este teste aceita qualquer das duas formas (OR logico).
    $logPath = $repoRoot().'/spike-inf007/npm-install.log';
    $stackVersionsPath = $repoRoot().'/docs/frontend/stack-versions.md';

    $logExists = file_exists($logPath);
    $stackHasEvidence = false;

    if (file_exists($stackVersionsPath)) {
        $stackContent = (string) file_get_contents($stackVersionsPath);
        $stackHasEvidence = (bool) preg_match('/Evid[eê]ncia.*npm install/i', $stackContent);
    }

    expect($logExists || $stackHasEvidence)->toBeTrue(
        'AC-002: deve existir spike-inf007/npm-install.log OU secao "Evidência `npm install`" em stack-versions.md.'
    );

    // Se o log existir, ele nao pode conter "ERR!" nem "ERROR" (plan.md §7 AC-002).
    if ($logExists) {
        $logContent = (string) file_get_contents($logPath);
        expect($logContent)->not->toMatch('/\bnpm ERR!|\bERROR\b/i',
            'AC-002: npm-install.log nao pode conter "npm ERR!" nem "ERROR".'
        );
    }
})->group('slice-015', 'ac-002');

// ===========================================================================
// AC-003 — Issues de SQLCipher em iOS 17+ / Android 14+ documentados
// ===========================================================================
// "o documento lista pelo menos os issues relevantes abertos no repositório
// @capacitor-community/sqlite com data e status, e declara explicitamente:
// (a) sem bloqueador — seguir com SQLCipher, ou (b) bloqueador identificado — plano B é X"
// ---------------------------------------------------------------------------

/**
 * @covers AC-003
 */
test('AC-003: stack-versions.md tem secao "Riscos de plataforma" com verdict explicito', function () use ($repoRoot): void {
    $path = $repoRoot().'/docs/frontend/stack-versions.md';
    expect(file_exists($path))->toBeTrue("AC-003 pre-req: {$path} deve existir.");

    $content = (string) file_get_contents($path);

    // Seção "Riscos de plataforma" obrigatoria.
    expect($content)->toMatch('/##\s*Riscos de plataforma/i',
        'AC-003: stack-versions.md deve ter secao "Riscos de plataforma".'
    );

    // Verdict binario: (a) sem bloqueador OU (b) bloqueador identificado.
    // Aceitamos qualquer declaracao explicita de verdict.
    $hasVerdictA = (bool) preg_match('/\(a\)\s*sem bloqueador/i', $content);
    $hasVerdictB = (bool) preg_match('/\(b\)\s*bloqueador/i', $content);

    expect($hasVerdictA || $hasVerdictB)->toBeTrue(
        'AC-003: documento deve declarar explicitamente "(a) sem bloqueador" ou "(b) bloqueador identificado".'
    );

    // Issues relevantes devem ter referencia a iOS 17+ ou Android 14+.
    expect($content)->toMatch('/iOS\s*17|iOS\s*18|Android\s*14|Android\s*15/i',
        'AC-003: documento deve mencionar pelo menos uma plataforma-alvo (iOS 17+ ou Android 14+).'
    );
})->group('slice-015', 'ac-003');

// ===========================================================================
// AC-004 — Tabelas para espelho local mapeadas
// ===========================================================================
// "o documento lista as tabelas do backend que precisarão de espelho local em SQLite
// (E15-S06), com coluna `tenant_id` identificada em cada uma, conforme ADR-0016"
// ---------------------------------------------------------------------------

/**
 * @covers AC-004
 */
test('AC-004: api-endpoints.md tem secao "Schema local" com tenant_id declarado', function () use ($repoRoot): void {
    $path = $repoRoot().'/docs/frontend/api-endpoints.md';
    expect(file_exists($path))->toBeTrue("AC-004 pre-req: {$path} deve existir.");

    $content = (string) file_get_contents($path);

    // Seção "Schema local" obrigatoria.
    expect($content)->toMatch('/##\s*Schema local/i',
        'AC-004: api-endpoints.md deve ter secao "Schema local".'
    );

    // tenant_id deve aparecer pelo menos uma vez (ADR-0016).
    expect($content)->toMatch('/\btenant_id\b/',
        'AC-004: documento deve declarar coluna tenant_id por tabela (ADR-0016).'
    );

    // Deve haver ao menos uma tabela candidata a mirror (ex: clientes, contatos, users, tenants).
    expect($content)->toMatch('/\b(clientes?|contatos?|users?|tenants?)\b/i',
        'AC-004: documento deve listar ao menos uma tabela candidata a espelho local.'
    );
})->group('slice-015', 'ac-004');

// ===========================================================================
// AC-005 — Frontend antigo descartado formalmente
// ===========================================================================
// "os arquivos Livewire/Blade e JS legado do frontend antigo estão listados em
// docs/frontend/stack-versions.md seção 'Descarte' — e o spike confirma que nenhum
// deles será reaproveitado no novo frontend"
// ---------------------------------------------------------------------------

/**
 * @covers AC-005
 */
test('AC-005: stack-versions.md tem secao "Descarte" listando frontend antigo', function () use ($repoRoot): void {
    $path = $repoRoot().'/docs/frontend/stack-versions.md';
    expect(file_exists($path))->toBeTrue("AC-005 pre-req: {$path} deve existir.");

    $content = (string) file_get_contents($path);

    // Seção "Descarte" obrigatoria.
    expect($content)->toMatch('/##\s*Descarte/i',
        'AC-005: stack-versions.md deve ter secao "Descarte".'
    );

    // Deve mencionar os diretorios do frontend antigo varridos no passo 7.
    expect($content)->toMatch('/resources\/views|\.blade\.php|Livewire/i',
        'AC-005: secao Descarte deve listar arquivos Blade/Livewire (resources/views).'
    );
    expect($content)->toMatch('/resources\/js/i',
        'AC-005: secao Descarte deve listar resources/js (JS/TS legado).'
    );

    // Confirmacao explicita de nao-reaproveitamento.
    expect($content)->toMatch('/n[aã]o\s+(ser[aã]o?\s+)?reaproveit|descart[aá][rv]?(ado|ados|ar)?\b/iu',
        'AC-005: documento deve confirmar explicitamente que o frontend antigo nao sera reaproveitado.'
    );
})->group('slice-015', 'ac-005');

// ===========================================================================
// AC-006 — Checklist de pré-condições para E15-S02 produzida
// ===========================================================================
// "o documento contém checklist com todos os itens marcados como verificados ou com
// pendência explícita, cobrindo: versões de pacotes, endpoint de auth funcional,
// plano para SQLCipher, plano de descarte do frontend antigo"
// ---------------------------------------------------------------------------

/**
 * @covers AC-006
 */
test('AC-006: stack-versions.md tem checklist "Pre-condicoes E15-S02" com 6 itens', function () use ($repoRoot): void {
    $path = $repoRoot().'/docs/frontend/stack-versions.md';
    expect(file_exists($path))->toBeTrue("AC-006 pre-req: {$path} deve existir.");

    $content = (string) file_get_contents($path);

    // Seção "Pré-condições E15-S02" obrigatoria.
    expect($content)->toMatch('/##\s*Pr[ée]-condi[cç][õo]es\s*E15-S02/iu',
        'AC-006: stack-versions.md deve ter secao "Pre-condicoes E15-S02".'
    );

    // Extrair o bloco da seção para contar checkboxes apenas dentro dela.
    $matched = preg_match(
        '/##\s*Pr[ée]-condi[cç][õo]es\s*E15-S02(.*?)(?=^##\s|\z)/smu',
        $content,
        $m
    );
    expect((bool) $matched)->toBeTrue(
        'AC-006: nao foi possivel extrair o bloco da secao "Pre-condicoes E15-S02".'
    );

    $bloco = $m[1] ?? '';

    // Cada checklist item: [x], [X], ou [ ] (com pendencia).
    // Plan.md §2 passo 8 exige 6 itens: (a) versoes, (b) npm install limpo, (c) SQLCipher verdict,
    // (d) auth E02, (e) tabelas mirror, (f) descarte frontend.
    preg_match_all('/^\s*[-*]\s*\[[ xX]\]/m', $bloco, $matches);
    $checklistCount = count($matches[0]);

    expect($checklistCount)->toBeGreaterThanOrEqual(6,
        "AC-006: secao 'Pre-condicoes E15-S02' deve ter >= 6 itens de checklist, encontrados: {$checklistCount}."
    );

    // Cada tema obrigatorio do AC-006 deve aparecer no bloco.
    expect($bloco)->toMatch('/vers[õo]es|pacotes/iu',
        'AC-006: checklist deve cobrir versoes de pacotes.'
    );
    expect($bloco)->toMatch('/auth|login/i',
        'AC-006: checklist deve cobrir endpoint de auth funcional.'
    );
    expect($bloco)->toMatch('/SQLCipher/i',
        'AC-006: checklist deve cobrir plano para SQLCipher.'
    );
    expect($bloco)->toMatch('/descarte|legado|antigo/i',
        'AC-006: checklist deve cobrir plano de descarte do frontend antigo.'
    );
})->group('slice-015', 'ac-006');
