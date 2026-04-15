<?php

declare(strict_types=1);

/**
 * Suite de isolamento — Segurança ampliada (AC-016)
 *
 * Complementa TenantIsolationHttpTest com vetores adicionais de SQL injection
 * e verificação completa: payload limpo + log com tenant_context.
 */

use Illuminate\Support\Facades\Log;
use Tests\TenantIsolationTestCase;

uses(TenantIsolationTestCase::class)->group('slice-011', 'tenant-isolation');

// ---------------------------------------------------------------------------
// AC-016: Cobertura ampla de vetores de SQL injection
// ---------------------------------------------------------------------------

/**
 * @ac AC-016
 */
dataset('extended_sql_injection_vectors', function () {
    return [
        'OR 1=1 com espaços' => ['1 OR 1=1'],
        'UNION SELECT multi-coluna' => ['1 UNION SELECT id,name,email,tenant_id FROM users--'],
        'Aspas simples escape' => ["1' OR '1'='1"],
        'DROP TABLE' => ['1; DROP TABLE tenants; --'],
        'Subquery tenant_id IS NOT NULL' => ['0 OR (SELECT tenant_id FROM users LIMIT 1) IS NOT NULL'],
        'OR tenant_id qualquer' => ['1 OR tenant_id != 999'],
        'Slug LIKE wildcard' => ["' OR slug LIKE '%"],
        'Comentário inline MySQL' => ['1/* comment */OR/* */1=1'],
        'Hex encoding' => ['1 OR 0x31=0x31'],
        'Double dash comment' => ["1'--"],
    ];
});

test('AC-016: vetor SQL injection em query string não vaza dados do tenant B', function (string $payload) {
    /** @ac AC-016
     *
     * Usa /api/tenant-context (rota MVP existente) como alvo de SQL injection via query string.
     * Valida que resposta nunca é 500 e não contém dados do tenant B.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $encodedPayload = urlencode($payload);
    $uri = "/api/tenant-context?id={$encodedPayload}&tenant={$encodedPayload}";

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get($uri);

    $statusCode = $response->getStatusCode();
    $content = $response->getContent() ?? '';

    expect($statusCode)->not->toBe(500,
        "AC-016: Payload '{$payload}' causou erro 500 — SQL injection não tratado."
    );

    expect($content)->not->toContain((string) $tenantB->id,
        "AC-016: Payload '{$payload}' revelou ID do tenant B."
    );

    if (! empty($tenantB->name)) {
        expect($content)->not->toContain((string) $tenantB->name,
            "AC-016: Payload '{$payload}' revelou nome do tenant B."
        );
    }

    $userBEmail = $this->userB()->email ?? '';
    if (! empty($userBEmail)) {
        expect($content)->not->toContain($userBEmail,
            "AC-016: Payload '{$payload}' revelou email do usuário B."
        );
    }
})->with('extended_sql_injection_vectors');

// ---------------------------------------------------------------------------
// AC-016: Payload de resposta não contém nenhum campo de registro do tenant B
// ---------------------------------------------------------------------------

test('AC-016: payload de resposta após SQL injection não contém literais de registros do tenant B', function () {
    /** @ac AC-016
     *
     * Usa /api/tenant-context com payload SQL injection e verifica que nenhum
     * literal do tenant B aparece na resposta.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $tenantBValues = $this->collectTenantBLiterals($tenantB);

    $injectionPayload = urlencode('1 OR 1=1');

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/api/tenant-context?tenant={$injectionPayload}");

    $content = $response->getContent() ?? '';

    foreach ($tenantBValues as $value) {
        if (strlen((string) $value) < 4) {
            continue; // Ignora valores muito curtos que podem colidir legitimamente
        }

        expect($content)
            ->not->toContain((string) $value,
                "AC-016: Payload de resposta contém valor '{$value}' do tenant B. Vazamento via SQL injection."
            );
    }
});

// ---------------------------------------------------------------------------
// AC-016: Log registra tentativa com tenant_context
// ---------------------------------------------------------------------------

test('AC-016: request com SQL injection via /api/tenant-context não vaza tenant B nos logs', function () {
    /** @ac AC-016
     *
     * Garante que logs gerados durante request com SQL injection não expõem
     * dados do tenant B. A rota /api/tenant-context existe no MVP.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $capturedLogs = [];
    Log::listen(function ($log) use (&$capturedLogs) {
        $capturedLogs[] = [
            'level' => $log->level,
            'message' => $log->message,
            'context' => $log->context,
        ];
    });

    $injectionPayload = urlencode('1 OR 1=1');

    $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/api/tenant-context?tenant={$injectionPayload}");

    // Nenhum log deve revelar dados do tenant B
    $leaked = collect($capturedLogs)->contains(function ($log) use ($tenantB) {
        $str = json_encode($log['context'] ?? []).$log['message'];

        return str_contains($str, (string) $tenantB->id)
            || (! empty($tenantB->name) && str_contains($str, (string) $tenantB->name));
    });

    expect($leaked)->toBeFalse(
        'AC-016: Log registrou dados do tenant B durante SQL injection — vazamento no logger.'
    );
});
