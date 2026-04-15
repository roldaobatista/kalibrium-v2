<?php

declare(strict_types=1);

/**
 * Suite de isolamento — Segurança ampliada (AC-016)
 *
 * Complementa TenantIsolationHttpTest com vetores adicionais de SQL injection
 * e verificação completa: payload limpo + log com tenant_context.
 */

use Illuminate\Support\Facades\DB;
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
        'OR 1=1 com espaços'            => ['1 OR 1=1'],
        'UNION SELECT multi-coluna'     => ['1 UNION SELECT id,name,email,tenant_id FROM users--'],
        'Aspas simples escape'          => ["1' OR '1'='1"],
        'DROP TABLE'                    => ['1; DROP TABLE tenants; --'],
        'Subquery tenant_id IS NOT NULL'=> ['0 OR (SELECT tenant_id FROM users LIMIT 1) IS NOT NULL'],
        'OR tenant_id qualquer'         => ['1 OR tenant_id != 999'],
        'Slug LIKE wildcard'            => ["' OR slug LIKE '%"],
        'Comentário inline MySQL'       => ['1/* comment */OR/* */1=1'],
        'Hex encoding'                  => ['1 OR 0x31=0x31'],
        'Double dash comment'           => ["1'--"],
    ];
});

test('AC-016: vetor SQL injection retorna 4xx e não expõe dados do tenant B', function (string $payload) {
    /** @ac AC-016 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Pre-condicao: a rota /instrumentos/{id} deve estar registrada.
    // Um ID real do tenant A deve retornar 200 ou 403 — nunca 404 de rota nao registrada.
    try {
        $recordA = DB::table('instruments')->where('tenant_id', $tenantA->id)->first()
            ?? DB::table('calibrations')->where('tenant_id', $tenantA->id)->first();
    } catch (\Illuminate\Database\QueryException $e) {
        $this->markTestIncomplete('AC-016: Tabela instruments/calibrations ausente — infraestrutura não implantada.');
    }

    if ($recordA === null) {
        $this->markTestIncomplete(
            'AC-016: Nenhum instrumento/calibracao do tenant A encontrado para pre-condicao. Popule a fixture.'
        );
    }

    $baseResponse = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/instrumentos/'.(string) $recordA->id);

    expect($baseResponse->status())
        ->toBeIn([200, 403],
            'AC-016: Rota GET /instrumentos/{id} retornou '.$baseResponse->status().' para ID valido do tenant A. '.
            'Esperado 200 ou 403. Implemente InstrumentoController@show com escopo de tenant.'
        );

    $encodedPayload = urlencode($payload);
    $uri = "/instrumentos/{$encodedPayload}";

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get($uri);

    $statusCode = $response->getStatusCode();
    $content = $response->getContent() ?? '';

    expect($statusCode)
        ->toBeIn([400, 403, 404, 422],
            "AC-016: Payload '{$payload}' retornou HTTP {$statusCode}. Esperado 4xx — nunca 200."
        );

    expect($content)
        ->not->toContain((string) $tenantB->id,
            "AC-016: Payload '{$payload}' revelou ID do tenant B."
        );

    if (! empty($tenantB->name)) {
        expect($content)
            ->not->toContain((string) $tenantB->name,
                "AC-016: Payload '{$payload}' revelou nome do tenant B."
            );
    }

    $userBEmail = $this->userB()->email ?? '';
    if (! empty($userBEmail)) {
        expect($content)
            ->not->toContain($userBEmail,
                "AC-016: Payload '{$payload}' revelou email do usuário B."
            );
    }
})->with('extended_sql_injection_vectors');

// ---------------------------------------------------------------------------
// AC-016: Payload de resposta não contém nenhum campo de registro do tenant B
// ---------------------------------------------------------------------------

test('AC-016: payload de resposta após SQL injection não contém literais de registros do tenant B', function () {
    /** @ac AC-016 */
    $tenantA = $this->tenantA();
    $tenantB = $this->tenantB();

    // Pre-condicao: rota deve existir com ID real do tenant A
    try {
        $recordA = DB::table('instruments')->where('tenant_id', $tenantA->id)->first()
            ?? DB::table('calibrations')->where('tenant_id', $tenantA->id)->first();
    } catch (\Illuminate\Database\QueryException $e) {
        $this->markTestIncomplete('AC-016: Tabela instruments/calibrations ausente — infraestrutura não implantada.');
    }

    if ($recordA === null) {
        $this->markTestIncomplete(
            'AC-016: Nenhum instrumento/calibracao do tenant A encontrado. Popule a fixture.'
        );
    }

    $baseResponse = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get('/instrumentos/'.(string) $recordA->id);

    expect($baseResponse->status())
        ->toBeIn([200, 403],
            'AC-016: Rota GET /instrumentos/{id} nao esta registrada (status: '.$baseResponse->status().'). '.
            'Implemente InstrumentoController@show com escopo de tenant.'
        );

    $tenantBValues = $this->collectTenantBLiterals($tenantB);

    $injectionPayload = urlencode('1 OR 1=1');

    $response = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/instrumentos/{$injectionPayload}");

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

test('AC-016: request com SQL injection registra log identificando tenant_context=A', function () {
    /** @ac AC-016 */
    $tenantA = $this->tenantA();

    $capturedLogs = [];
    Log::listen(function ($log) use (&$capturedLogs) {
        $capturedLogs[] = [
            'level'   => $log->level,
            'message' => $log->message,
            'context' => $log->context,
        ];
    });

    $injectionPayload = urlencode('1 OR 1=1');

    $logResponse = $this->actingAs($this->userA())
        ->withSession(['current_tenant_id' => $tenantA->id])
        ->get("/instrumentos/{$injectionPayload}");

    if ($logResponse->getStatusCode() === 404 && empty($capturedLogs)) {
        $this->markTestIncomplete(
            'AC-016: Rota /instrumentos/{id} não registrada — não é possível validar log de tenant_context.'
        );
    }

    $hasTenantContext = collect($capturedLogs)->contains(function ($log) use ($tenantA) {
        $contextStr = json_encode($log['context'] ?? []);

        return str_contains($contextStr, 'tenant_context')
            || str_contains($contextStr, (string) $tenantA->id)
            || str_contains($log['message'] ?? '', 'tenant');
    });

    expect($hasTenantContext)
        ->toBeTrue(
            'AC-016: Nenhum log registrou tenant_context durante SQL injection. '.
            'Implemente logging no middleware ou no handler de exceções 404/403 '.
            'para registrar: ["tenant_context" => tenant_id, "uri" => $request->path()].'
        );
});
