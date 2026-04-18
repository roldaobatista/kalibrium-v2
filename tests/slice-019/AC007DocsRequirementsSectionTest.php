<?php

declare(strict_types=1);

/**
 * Slice 019 — AC-007: docs/documentation-requirements.md contém nova seção
 * "## Camadas sensíveis a tenant isolation" com 3 elementos:
 *   (a) declaração explícita sobre adicionar nova camada aos paths do CI
 *   (b) apontamento para scripts/check-tenant-filter-coverage.sh
 *   (c) referência cruzada a ADR-0016
 *
 * @covers AC-007
 *
 * RED enquanto a seção não existir.
 */

describe('AC-007: documentation-requirements.md seção de camadas sensíveis', function () {

    beforeEach(function () {
        $this->repoRoot = realpath(__DIR__ . '/../..');
        $this->docPath = $this->repoRoot . '/docs/documentation-requirements.md';
        $this->content = file_exists($this->docPath) ? (string) file_get_contents($this->docPath) : '';
    });

    test('AC-007: docs/documentation-requirements.md existe', function () {
        /** @covers AC-007 */
        expect(file_exists($this->docPath))
            ->toBeTrue('AC-007: docs/documentation-requirements.md não encontrado.');
    });

    test('AC-007: documento contém seção "## Camadas sensíveis a tenant isolation"', function () {
        /** @covers AC-007 */
        expect($this->content)
            ->toMatch('/^##\s+Camadas sens[ií]veis a tenant isolation/m',
                'AC-007: documento deve conter heading "## Camadas sensíveis a tenant isolation". ' .
                'Implementer deve adicionar nova seção conforme D-06 do plan.');
    });

    test('AC-007.a: seção declara que nova camada deve ser adicionada ao paths filter do tenant-isolation', function () {
        /** @covers AC-007 */
        // Verifica presença de string-chave que indica a declaração
        // (tolerante a variação de wording mas mantendo assertividade)
        $lower = mb_strtolower($this->content);

        expect($lower)
            ->toContain('tenant-isolation',
                'AC-007.a: seção deve mencionar job tenant-isolation do CI.');

        expect($lower)
            ->toContain('paths',
                'AC-007.a: seção deve mencionar "paths" (referência ao paths filter do ci.yml).');

        expect($lower)
            ->toContain('ci.yml',
                'AC-007.a: seção deve referenciar .github/workflows/ci.yml explicitamente.');
    });

    test('AC-007.b: seção aponta para scripts/check-tenant-filter-coverage.sh', function () {
        /** @covers AC-007 */
        expect($this->content)
            ->toContain('check-tenant-filter-coverage.sh',
                'AC-007.b: seção deve apontar para scripts/check-tenant-filter-coverage.sh ' .
                'como ferramenta de verificação.');
    });

    test('AC-007.c: seção referencia ADR-0016 (isolamento multi-tenant)', function () {
        /** @covers AC-007 */
        expect($this->content)
            ->toContain('ADR-0016',
                'AC-007.c: seção deve ter referência cruzada literal a "ADR-0016".');
    });
});
