<?php

declare(strict_types=1);

/**
 * Slice 019 — AC-004: Push real em main via CLI direta (fora do Claude Code) é bloqueado
 * pelo .git/hooks/pre-push nativo com exit != 0 e mensagem de violação.
 *
 * @covers AC-004
 *
 * Usa repo git temporário isolado com remote simulado para não poluir ambiente real.
 *
 * RED enquanto install-git-hooks.sh + pre-push-native.sh não existirem.
 */

describe('AC-004: push nativo em main é bloqueado fora do Claude Code', function () {

    beforeEach(function () {
        $this->repoRoot = realpath(__DIR__ . '/../..');
        $this->installer = $this->repoRoot . '/scripts/install-git-hooks.sh';
        $this->nativeHook = $this->repoRoot . '/scripts/pre-push-native.sh';
    });

    test('AC-004: dependências existem (install-git-hooks.sh + pre-push-native.sh)', function () {
        /** @covers AC-004 */
        expect(file_exists($this->installer))
            ->toBeTrue('AC-004: scripts/install-git-hooks.sh não existe.');
        expect(file_exists($this->nativeHook))
            ->toBeTrue('AC-004: scripts/pre-push-native.sh não existe.');
    });

    test('AC-004: push em main via git push direto é bloqueado pelo hook nativo', function () {
        /** @covers AC-004 */
        if (! file_exists($this->installer) || ! file_exists($this->nativeHook)) {
            $this->fail('AC-004: scripts do slice não existem — teste anterior bloqueia este.');
        }

        // Setup repo git temporário com remote bare simulado
        $tempBase = sys_get_temp_dir() . '/slice-019-ac004-' . uniqid();
        $workRepo = $tempBase . '/work';
        $bareRepo = $tempBase . '/remote.git';
        @mkdir($workRepo, 0o755, true);
        @mkdir($bareRepo, 0o755, true);

        try {
            // Remote bare (simula GitHub)
            shell_exec('cd ' . escapeshellarg($bareRepo) . ' && git init --bare --quiet 2>&1');

            // Worktree com commit inicial em main
            $setup = 'cd ' . escapeshellarg($workRepo) . ' && ' .
                     'git init --quiet -b main && ' .
                     'git config user.email test@example.com && ' .
                     'git config user.name "Test" && ' .
                     'git remote add origin ' . escapeshellarg($bareRepo) . ' && ' .
                     'echo "initial" > file.txt && ' .
                     'git add file.txt && ' .
                     'git commit -q -m "initial" 2>&1';
            shell_exec($setup);

            // Instala hook nativo apontando para o pre-push-native.sh do projeto
            // (copia manual para simular o install-git-hooks.sh sem depender de paths absolutos)
            $hookTarget = $workRepo . '/.git/hooks/pre-push';
            $hookContent = "#!/usr/bin/env bash\nexec bash " . escapeshellarg($this->nativeHook) . " \"\$@\"\n";
            file_put_contents($hookTarget, $hookContent);
            chmod($hookTarget, 0o755);

            // Tenta push em main — deve ser bloqueado
            $push = 'cd ' . escapeshellarg($workRepo) .
                    ' && git push origin main 2>&1; echo "__EXIT=$?"';
            $out = (string) shell_exec($push);

            expect($out)
                ->toMatch('/__EXIT=[1-9]/',
                    "AC-004: push em main NÃO foi bloqueado pelo hook nativo. Output: {$out}");

            // Verifica que commit não chegou no remote
            $remoteLog = (string) shell_exec(
                'cd ' . escapeshellarg($bareRepo) . ' && git log --oneline -1 2>&1'
            );

            expect($remoteLog)
                ->not->toContain('initial',
                    "AC-004: commit vazou para o remote mesmo com hook ativo. Output remote: {$remoteLog}");
        } finally {
            // Cleanup
            if (is_dir($tempBase)) {
                if (PHP_OS_FAMILY === 'Windows') {
                    shell_exec('rmdir /S /Q ' . escapeshellarg(str_replace('/', '\\', $tempBase)));
                } else {
                    shell_exec('rm -rf ' . escapeshellarg($tempBase));
                }
            }
        }
    });
});
