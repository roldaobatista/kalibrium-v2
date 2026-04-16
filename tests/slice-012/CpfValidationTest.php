<?php

declare(strict_types=1);
use App\Rules\Cpf;
use Tests\TestCase;

/**
 * Slice 012 — E03-S01a: Testes unitarios da Rule Cpf (AC-002, AC-004)
 *
 * Red natural: classe App\Rules\Cpf nao existe.
 */
uses(TestCase::class)->group('slice-012', 'cpf-validation');

// ---------------------------------------------------------------------------
// AC-002: CPFs validos passam na validacao
// ---------------------------------------------------------------------------

test('AC-002: Rule Cpf aceita CPF valido 529.982.247-25', function () {
    /** @ac AC-002 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '529.982.247-25', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse('CPF 529.982.247-25 deveria ser aceito pela Rule Cpf.');
});

test('AC-002: Rule Cpf aceita CPF valido somente digitos', function () {
    /** @ac AC-002 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '52998224725', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse('CPF 52998224725 (sem mascara) deveria ser aceito pela Rule Cpf.');
});

test('AC-002: Rule Cpf aceita outro CPF valido 347.066.120-04', function () {
    /** @ac AC-002 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '347.066.120-04', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse('CPF 347.066.120-04 deveria ser aceito pela Rule Cpf.');
});

// ---------------------------------------------------------------------------
// AC-004: CPFs invalidos sao rejeitados
// ---------------------------------------------------------------------------

test('AC-004: Rule Cpf rejeita CPF com todos digitos iguais 111.111.111-11', function () {
    /** @ac AC-004 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '111.111.111-11', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CPF 111.111.111-11 (sequencia repetida) deveria ser rejeitado pela Rule Cpf.');
});

test('AC-004: Rule Cpf rejeita CPF com digitos verificadores invalidos', function () {
    /** @ac AC-004 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '123.456.789-00', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CPF 123.456.789-00 (digitos invalidos) deveria ser rejeitado pela Rule Cpf.');
});

test('AC-004: Rule Cpf rejeita CPF com menos de 11 digitos', function () {
    /** @ac AC-004 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '123.456.789', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CPF com menos de 11 digitos deveria ser rejeitado pela Rule Cpf.');
});

test('AC-004: Rule Cpf rejeita string vazia', function () {
    /** @ac AC-004 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CPF vazio deveria ser rejeitado pela Rule Cpf.');
});

test('AC-004: Rule Cpf rejeita CPF 000.000.000-00', function () {
    /** @ac AC-004 */
    $rule = new Cpf;

    $failed = false;
    $rule->validate('cpf', '000.000.000-00', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue('CPF 000.000.000-00 deveria ser rejeitado pela Rule Cpf.');
});
