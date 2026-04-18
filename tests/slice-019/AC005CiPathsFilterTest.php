<?php

declare(strict_types=1);

/**
 * Slice 019 — AC-005: .github/workflows/ci.yml job tenant-isolation paths filter
 * atualizado: remove app/Livewire/**, adiciona app/Services/**, app/Domain/**,
 * database/migrations/**, tests/tenant-isolation/**.
 *
 * @covers AC-005
 *
 * RED enquanto ci.yml ainda tiver app/Livewire/** e não tiver Services/Domain/migrations.
 */

describe('AC-005: ci.yml paths filter do tenant-isolation atualizado', function () {

    beforeEach(function () {
        $this->repoRoot = realpath(__DIR__ . '/../..');
        $this->ciYml = $this->repoRoot . '/.github/workflows/ci.yml';
        $this->content = file_exists($this->ciYml) ? (string) file_get_contents($this->ciYml) : '';
    });

    test('AC-005: .github/workflows/ci.yml existe', function () {
        /** @covers AC-005 */
        expect(file_exists($this->ciYml))->toBeTrue('AC-005: ci.yml não encontrado.');
    });

    test('AC-005: ci.yml contém job tenant-isolation', function () {
        /** @covers AC-005 */
        expect($this->content)
            ->toContain('tenant-isolation:',
                'AC-005: job tenant-isolation não existe em ci.yml.');
    });

    test('AC-005: paths filter contém app/Models/**', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('app/Models/**',
            'AC-005: paths filter deve conter app/Models/**.');
    });

    test('AC-005: paths filter contém app/Http/**', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('app/Http/**',
            'AC-005: paths filter deve conter app/Http/**.');
    });

    test('AC-005: paths filter contém app/Services/** (nova camada sensível)', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('app/Services/**',
            'AC-005: paths filter deve incluir app/Services/** (ADR-0016 camada sensível). ' .
            'Implementer precisa adicionar ao bloco filters.run.paths.');
    });

    test('AC-005: paths filter contém app/Domain/** (nova camada sensível)', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('app/Domain/**',
            'AC-005: paths filter deve incluir app/Domain/** (ADR-0016 camada sensível).');
    });

    test('AC-005: paths filter contém app/Jobs/**', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('app/Jobs/**',
            'AC-005: paths filter deve conter app/Jobs/**.');
    });

    test('AC-005: paths filter contém database/migrations/** (nova camada sensível)', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('database/migrations/**',
            'AC-005: paths filter deve incluir database/migrations/** (migrations afetam tenant schema).');
    });

    test('AC-005: paths filter contém tests/slice-011/**', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('tests/slice-011/**',
            'AC-005: paths filter deve manter tests/slice-011/**.');
    });

    test('AC-005: paths filter contém tests/tenant-isolation/** (catch-all futuro)', function () {
        /** @covers AC-005 */
        expect($this->content)->toContain('tests/tenant-isolation/**',
            'AC-005: paths filter deve incluir tests/tenant-isolation/** para cobrir slices futuros.');
    });

    test('AC-005: paths filter NÃO contém app/Livewire/** (demolido no slice-016 / ADR-0015)', function () {
        /** @covers AC-005 */
        expect($this->content)->not->toContain('app/Livewire/**',
            'AC-005: app/Livewire/** deve ser REMOVIDO do paths filter — ' .
            'frontend Livewire foi demolido no slice-016 (ADR-0015). Área morta no filter.');
    });
});
