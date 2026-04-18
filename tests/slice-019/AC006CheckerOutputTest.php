<?php

declare(strict_types=1);

/**
 * Slice 019 — AC-006: scripts/check-tenant-filter-coverage.sh compara subdirs de app/
 * com paths filter do job tenant-isolation em ci.yml e emite:
 *   - "uncovered: app/<dir>/" para subdirs não cobertos
 *   - "[SUSPECT] uncovered: app/<dir>/" quando subdir tem .php com "tenant"
 * Exit code sempre 0 nesta versão (warning-only — AC-006.d).
 *
 * @covers AC-006
 *
 * RED enquanto scripts/check-tenant-filter-coverage.sh não existir.
 */

describe('AC-006: check-tenant-filter-coverage.sh parser + heurística [SUSPECT]', function () {

    beforeEach(function () {
        $this->repoRoot = realpath(__DIR__ . '/../..');
        $this->checker = $this->repoRoot . '/scripts/check-tenant-filter-coverage.sh';
    });

    test('AC-006: scripts/check-tenant-filter-coverage.sh existe e é executável', function () {
        /** @covers AC-006 */
        expect(file_exists($this->checker))
            ->toBeTrue(
                "AC-006: scripts/check-tenant-filter-coverage.sh não existe em {$this->checker}. " .
                'Implementer deve criar o checker conforme D-03 do plan.'
            );

        if (file_exists($this->checker)) {
            $perms = fileperms($this->checker) & 0o777;
            expect($perms & 0o100)
                ->toBeGreaterThan(0,
                    'AC-006: checker não é executável (perms=' . decoct($perms) . ').');
        }
    });

    test('AC-006.d: exit code é SEMPRE 0 (warning-only nesta versão)', function () {
        /** @covers AC-006 */
        if (! file_exists($this->checker)) {
            $this->fail('AC-006: checker não existe.');
        }

        $cmd = 'cd ' . escapeshellarg($this->repoRoot) . ' && ' .
               'bash ' . escapeshellarg($this->checker) . ' 2>&1; echo "__EXIT=$?"';
        $out = (string) shell_exec($cmd);

        expect($out)
            ->toContain('__EXIT=0',
                "AC-006.d: checker deve ter exit 0 (warning-only nesta versão). Output: {$out}");
    });

    test('AC-006.c: output usa prefixo "uncovered:" para subdirs não cobertos', function () {
        /** @covers AC-006 */
        if (! file_exists($this->checker)) {
            $this->fail('AC-006: checker não existe.');
        }

        $cmd = 'cd ' . escapeshellarg($this->repoRoot) . ' && ' .
               'bash ' . escapeshellarg($this->checker) . ' 2>&1';
        $out = (string) shell_exec($cmd);

        // app/ tem vários subdirs (Console, Providers, etc.) que nunca serão cobertos pelo
        // filter (não são sensíveis a tenant). Deve emitir uncovered: para algum deles.
        expect($out)
            ->toMatch('/uncovered:\s+app\//',
                "AC-006.c: checker não emitiu linha 'uncovered: app/<dir>/'. Output: {$out}");
    });

    test('AC-006.a+b: checker parseia lista paths do ci.yml e compara com ls app/', function () {
        /** @covers AC-006 */
        if (! file_exists($this->checker)) {
            $this->fail('AC-006: checker não existe.');
        }

        // Invariante: subdirs declarados no filter NÃO aparecem como "uncovered:"
        // Ex.: app/Models/** está no filter → "uncovered: app/Models/" NÃO deve aparecer.
        $cmd = 'cd ' . escapeshellarg($this->repoRoot) . ' && ' .
               'bash ' . escapeshellarg($this->checker) . ' 2>&1';
        $out = (string) shell_exec($cmd);

        // app/Models existe e está no filter (AC-005 garante)
        expect($out)
            ->not->toMatch('/uncovered:\s+app\/Models\//',
                "AC-006.a+b: app/Models aparece como 'uncovered' mas está no filter. " .
                "Parser do ci.yml quebrado. Output: {$out}");

        expect($out)
            ->not->toMatch('/uncovered:\s+app\/Http\//',
                "AC-006.a+b: app/Http aparece como 'uncovered' mas está no filter. Output: {$out}");
    });

    test('AC-006.e: subdir com .php contendo "tenant" (case-insensitive) marca [SUSPECT]', function () {
        /** @covers AC-006 */
        if (! file_exists($this->checker)) {
            $this->fail('AC-006: checker não existe.');
        }

        // Cria app/TestSuspectSliceOneNine_tmp/ com arquivo .php contendo "tenant"
        // para exercitar a heurística sem poluir o repo permanentemente.
        $fakeDir = $this->repoRoot . '/app/TestSuspectSliceOneNine_tmp';
        @mkdir($fakeDir, 0o755, true);
        $fakeFile = $fakeDir . '/FakeService.php';
        file_put_contents($fakeFile,
            "<?php\n// Simulates sensitive layer mentioning tenant_id\nfunction stub() { return 'tenant'; }\n");

        try {
            $cmd = 'cd ' . escapeshellarg($this->repoRoot) . ' && ' .
                   'bash ' . escapeshellarg($this->checker) . ' 2>&1';
            $out = (string) shell_exec($cmd);

            expect($out)
                ->toMatch('/\[SUSPECT\]\s*uncovered:\s+app\/TestSuspectSliceOneNine_tmp\//',
                    'AC-006.e: subdir uncovered com .php contendo "tenant" deve ser marcado [SUSPECT]. ' .
                    "Output: {$out}");
        } finally {
            // Cleanup
            @unlink($fakeFile);
            @rmdir($fakeDir);
        }
    });
});
