<?php

declare(strict_types=1);

/**
 * Slice 012 — E03-S01a: Testes unitarios de validacao CNPJ no contexto de clientes (AC-001, AC-003)
 *
 * Reutiliza App\Rules\Cnpj existente (algoritmo de digitos).
 * Red natural: rota/controller/FormRequest nao existem.
 */

uses(\Tests\TestCase::class)->group('slice-012', 'cnpj-cliente-validation');

// ---------------------------------------------------------------------------
// AC-001: CNPJs validos passam no algoritmo
// ---------------------------------------------------------------------------

test('AC-001: Cnpj::normalize remove mascara e retorna somente digitos', function () {
    /** @ac AC-001 */
    $normalized = \App\Rules\Cnpj::normalize('11.222.333/0001-81');

    expect($normalized)->toBe('11222333000181');
});

test('AC-001: CNPJ valido 11.222.333/0001-81 passa na validacao algoritmica', function () {
    /** @ac AC-001 */
    $rule = new \App\Rules\Cnpj;

    $failed = false;
    $rule->validate('cnpj', '11.222.333/0001-81', function () use (&$failed) {
        $failed = true;
    });

    // A rule Cnpj existente valida digitos E unicidade em tenants.
    // Para o contexto de clientes, o algoritmo de digitos deve passar.
    // Se falhar, pode ser por unicidade em tenants — o que e esperado no
    // contexto da rule original. O FormRequest de clientes usa Rule::unique separada.
    // Este teste confirma que o algoritmo de digitos aceita o CNPJ.
    expect($failed)->toBeFalse('CNPJ 11.222.333/0001-81 deveria passar na validacao algoritmica.');
});

// ---------------------------------------------------------------------------
// AC-003: CNPJs invalidos sao rejeitados pelo algoritmo
// ---------------------------------------------------------------------------

test('AC-003: CNPJ com todos digitos iguais 11.111.111/1111-11 e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new \App\Rules\Cnpj;

    $failed = false;
    $rule->validate('cnpj', '11.111.111/1111-11', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ 11.111.111/1111-11 (sequencia repetida) deveria ser rejeitado.');
});

test('AC-003: CNPJ com digitos verificadores errados e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new \App\Rules\Cnpj;

    $failed = false;
    $rule->validate('cnpj', '12.345.678/0001-99', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ 12.345.678/0001-99 (digitos invalidos) deveria ser rejeitado.');
});
