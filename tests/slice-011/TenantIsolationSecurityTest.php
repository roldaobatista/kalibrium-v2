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
dataset('extended_sql_injection_vectors', fn () => require __DIR__.'/Datasets/SqlInjectionPayloads.php');

test('AC-016: vetor SQL injection em path param não vaza dados do tenant B', function (string $payload) {
    /** @ac AC-016
     *
     * Ângulo diferente de TenantIsolationHttpTest: payload em path param em vez de query string.
     * Usa /api/tenant-context/{param} como alvo — verifica que o servidor não expõe dados do tenant B
     * nem retorna 500 mesmo com payload malicioso no segmento de URL.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $encodedPayload = urlencode($payload);
    $uri = "/api/tenant-context/{$encodedPayload}";

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get($uri);

    $statusCode = $response->getStatusCode();
    $content = $response->getContent() ?? '';

    expect($statusCode)->not->toBe(500,
        "AC-016: Payload '{$payload}' em path param causou erro 500 — SQL injection não tratado."
    );

    expect($content)->not->toContain((string) $tenantB->id,
        "AC-016: Payload '{$payload}' em path param revelou ID do tenant B."
    );

    if (! empty($tenantB->name)) {
        expect($content)->not->toContain((string) $tenantB->name,
            "AC-016: Payload '{$payload}' em path param revelou nome do tenant B."
        );
    }

    $userBEmail = $this->userB()->email ?? '';
    if (! empty($userBEmail)) {
        expect($content)->not->toContain($userBEmail,
            "AC-016: Payload '{$payload}' em path param revelou email do usuário B."
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
     * Garante que logs gerados durante request com SQL injection registram tenant_context=A
     * (assert positivo) e não expõem dados do tenant B (assert negativo).
     * A rota /api/tenant-context existe no MVP em ambientes local/testing.
     */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    $logSpy = Log::spy();

    $injectionPayload = urlencode('1 OR 1=1');

    $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/api/tenant-context?tenant={$injectionPayload}");

    // Assert POSITIVO: log emitido com tenant_context=A (AC-016 §2)
    $logSpy->shouldHaveReceived('info')
        ->withArgs(fn ($message, $context = []) => isset($context['tenant_context'])
            && (string) $context['tenant_context'] === (string) $tenantA->id)
        ->atLeast()->once();

    // Assert NEGATIVO: nenhum log revela dados do tenant B
    $logSpy->shouldNotHaveReceived('info', function ($message, $context = []) use ($tenantB) {
        $str = json_encode($context).$message;

        return str_contains($str, (string) $tenantB->id)
            || (! empty($tenantB->name) && str_contains($str, (string) $tenantB->name));
    });
});
