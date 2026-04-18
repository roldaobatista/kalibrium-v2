<?php

declare(strict_types=1);

/**
 * Slice 019 — AC-001: scripts/install-git-hooks.sh é idempotente.
 *
 * @covers AC-001
 *
 * Valida:
 *   (a) .git/hooks/pre-push é criado, executável, referencia scripts/hooks/pre-push-native.sh
 *   (b) Execução dupla produz mesmo SHA-256 (idempotência)
 *   (c) 1ª execução imprime "installed:", 2ª imprime "already-current:"
 *
 * RED enquanto scripts/install-git-hooks.sh não existir.
 */

describe('AC-001: install-git-hooks.sh idempotente', function () {

    beforeEach(function () {
        $this->repoRoot = realpath(__DIR__ . '/../..');
        $this->installer = $this->repoRoot . '/scripts/install-git-hooks.sh';
        $this->hookPath = $this->repoRoot . '/.git/hooks/pre-push';

        // Backup de hook existente (se houver) para não poluir ambiente real
        $this->backupPath = $this->hookPath . '.bak-slice-019-test';
        if (file_exists($this->hookPath)) {
            @copy($this->hookPath, $this->backupPath);
            @unlink($this->hookPath);
        }
    });

    afterEach(function () {
        // Restaura backup
        if (file_exists($this->backupPath)) {
            @copy($this->backupPath, $this->hookPath);
            @unlink($this->backupPath);
        } elseif (file_exists($this->hookPath)) {
            @unlink($this->hookPath);
        }
    });

    test('AC-001: scripts/install-git-hooks.sh existe e é executável', function () {
        /** @covers AC-001 */
        expect(file_exists($this->installer))
            ->toBeTrue(
                "AC-001: scripts/install-git-hooks.sh não existe em {$this->installer}. " .
                'Implementer deve criar o instalador idempotente.'
            );
    });

    test('AC-001.a: primeira execução cria .git/hooks/pre-push referenciando pre-push-native.sh', function () {
        /** @covers AC-001 */
        if (! file_exists($this->installer)) {
            $this->fail('AC-001: installer não existe — teste anterior bloqueia este.');
        }

        $output = shell_exec('bash ' . escapeshellarg($this->installer) . ' 2>&1');

        expect(file_exists($this->hookPath))
            ->toBeTrue('AC-001.a: .git/hooks/pre-push não foi criado pelo installer.');

        $hookContent = (string) @file_get_contents($this->hookPath);

        expect($hookContent)
            ->toContain('pre-push-native.sh',
                'AC-001.a: .git/hooks/pre-push não invoca scripts/hooks/pre-push-native.sh.');

        // Executabilidade: perms 0755 ou pelo menos owner-exec
        $perms = fileperms($this->hookPath) & 0o777;
        expect($perms & 0o100)
            ->toBeGreaterThan(0,
                "AC-001.a: .git/hooks/pre-push não está executável (perms=" . decoct($perms) . ').');
    });

    test('AC-001.b: execução dupla produz SHA-256 idêntico (idempotência)', function () {
        /** @covers AC-001 */
        if (! file_exists($this->installer)) {
            $this->fail('AC-001: installer não existe — teste anterior bloqueia este.');
        }

        // Execução 1
        shell_exec('bash ' . escapeshellarg($this->installer) . ' 2>&1');
        $sha1 = file_exists($this->hookPath) ? hash_file('sha256', $this->hookPath) : null;

        // Execução 2
        shell_exec('bash ' . escapeshellarg($this->installer) . ' 2>&1');
        $sha2 = file_exists($this->hookPath) ? hash_file('sha256', $this->hookPath) : null;

        expect($sha1)->not->toBeNull('AC-001.b: hook ausente após execução 1.');
        expect($sha2)->not->toBeNull('AC-001.b: hook ausente após execução 2.');
        expect($sha1)->toBe($sha2,
            "AC-001.b: hook mudou entre execuções (sha1={$sha1}, sha2={$sha2}). Instalador não é idempotente.");
    });

    test('AC-001.c: 1ª execução imprime "installed:", 2ª imprime "already-current:"', function () {
        /** @covers AC-001 */
        if (! file_exists($this->installer)) {
            $this->fail('AC-001: installer não existe — teste anterior bloqueia este.');
        }

        $out1 = (string) shell_exec('bash ' . escapeshellarg($this->installer) . ' 2>&1');
        $out2 = (string) shell_exec('bash ' . escapeshellarg($this->installer) . ' 2>&1');

        expect($out1)
            ->toContain('installed:',
                "AC-001.c: 1ª execução não imprimiu 'installed:'. Output: {$out1}");
        expect($out1)
            ->toContain('.git/hooks/pre-push',
                "AC-001.c: 1ª execução não mencionou o caminho do hook. Output: {$out1}");

        expect($out2)
            ->toContain('already-current:',
                "AC-001.c: 2ª execução não imprimiu 'already-current:'. Output: {$out2}");
    });
});
