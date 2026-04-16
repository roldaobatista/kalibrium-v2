<?php

declare(strict_types=1);
use App\Rules\CnpjFormat;
use Tests\TestCase;

/**
 * Slice 012 — E03-S01a: Testes unitarios de validacao CNPJ no contexto de clientes (AC-001, AC-003)
 *
 * Reutiliza App\Rules\Cnpj existente (algoritmo de digitos).
 * Red natural: rota/controller/FormRequest nao existem.
 */
uses(TestCase::class)->group('slice-012', 'cnpj-cliente-validation');

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
    $rule = new CnpjFormat;

    $failed = false;
    $rule->validate('cnpj', '11.222.333/0001-81', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse('CNPJ 11.222.333/0001-81 deveria passar na validacao algoritmica.');
});

// ---------------------------------------------------------------------------
// AC-003: CNPJs invalidos sao rejeitados pelo algoritmo
// ---------------------------------------------------------------------------

test('AC-003: CNPJ com todos digitos iguais 11.111.111/1111-11 e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new CnpjFormat;

    $failed = false;
    $rule->validate('cnpj', '11.111.111/1111-11', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ 11.111.111/1111-11 (sequencia repetida) deveria ser rejeitado.');
});

test('AC-003: CNPJ com digitos verificadores errados e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new CnpjFormat;

    $failed = false;
    $rule->validate('cnpj', '12.345.678/0001-99', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ 12.345.678/0001-99 (digitos invalidos) deveria ser rejeitado.');
});

// ---------------------------------------------------------------------------
// TEST-001: CNPJ all-zeros deve ser rejeitado
// ---------------------------------------------------------------------------

test('AC-003: CNPJ com todos zeros 00.000.000/0000-00 e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new CnpjFormat;

    $failed = false;
    $rule->validate('cnpj', '00.000.000/0000-00', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ 00.000.000/0000-00 (todos zeros) deveria ser rejeitado.');
});

// ---------------------------------------------------------------------------
// TEST-002: CNPJ com tamanho errado deve ser rejeitado
// ---------------------------------------------------------------------------

test('AC-003: CNPJ curto (menos de 14 digitos) e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new CnpjFormat;

    $failed = false;
    $rule->validate('cnpj', '11.222.333/000', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ curto deveria ser rejeitado.');
});

test('AC-003: CNPJ longo (mais de 14 digitos) e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new CnpjFormat;

    $failed = false;
    $rule->validate('cnpj', '11.222.333/0001-819', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ longo deveria ser rejeitado.');
});

// ---------------------------------------------------------------------------
// TEST-003: CNPJ string vazia deve ser rejeitado
// ---------------------------------------------------------------------------

test('AC-003: CNPJ string vazia e rejeitado', function () {
    /** @ac AC-003 */
    $rule = new CnpjFormat;

    $failed = false;
    $rule->validate('cnpj', '', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CNPJ vazio deveria ser rejeitado.');
});
