<?php

declare(strict_types=1);

/**
 * Slice 019 — AC-002: scripts/hooks/pre-push-native.sh bloqueia push em main/master
 * e --force em main/master, equivalência funcional com pre-push-gate.sh (PreToolUse).
 *
 * @covers AC-002
 *
 * RED enquanto scripts/hooks/pre-push-native.sh não existir.
 */

describe('AC-002: pre-push-native.sh bloqueia cenários críticos', function () {

    beforeEach(function () {
        $this->repoRoot = realpath(__DIR__ . '/../..');
        $this->nativeHook = $this->repoRoot . '/scripts/hooks/pre-push-native.sh';
    });

    test('AC-002: scripts/hooks/pre-push-native.sh existe e é executável', function () {
        /** @covers AC-002 */
        expect(file_exists($this->nativeHook))
            ->toBeTrue(
                "AC-002: scripts/hooks/pre-push-native.sh não existe em {$this->nativeHook}. " .
                'Implementer deve criar o wrapper nativo.'
            );

        if (file_exists($this->nativeHook)) {
            $perms = fileperms($this->nativeHook) & 0o777;
            expect($perms & 0o100)
                ->toBeGreaterThan(0,
                    'AC-002: pre-push-native.sh não está executável (perms=' . decoct($perms) . ').');
        }
    });

    test('AC-002: pre-push-native.sh bloqueia push em refs/heads/main (exit != 0)', function () {
        /** @covers AC-002 */
        if (! file_exists($this->nativeHook)) {
            $this->fail('AC-002: pre-push-native.sh não existe.');
        }

        // Contrato git hook: stdin recebe "<local_ref> <local_sha> <remote_ref> <remote_sha>"
        // args: $1=remote_name, $2=remote_url
        $stdin = "refs/heads/main abc1234567890abcdef1234567890abcdef12345 refs/heads/main fed9876543210fedcba9876543210fedcba987654\n";

        $cmd = 'echo ' . escapeshellarg($stdin) .
               ' | bash ' . escapeshellarg($this->nativeHook) .
               ' origin https://github.com/example/repo.git 2>&1';

        $output = (string) shell_exec($cmd . '; echo "__EXIT=$?"');

        expect($output)
            ->toMatch('/__EXIT=[1-9]/',
                "AC-002: pre-push-native.sh não bloqueou push em main (deveria exit != 0). Output: {$output}");

        expect(strtolower($output))
            ->toContain('main',
                "AC-002: mensagem de erro não menciona 'main'. Output: {$output}");
    });

    test('AC-002: pre-push-native.sh bloqueia push em refs/heads/master (exit != 0)', function () {
        /** @covers AC-002 */
        if (! file_exists($this->nativeHook)) {
            $this->fail('AC-002: pre-push-native.sh não existe.');
        }

        $stdin = "refs/heads/master abc1234567890abcdef1234567890abcdef12345 refs/heads/master fed9876543210fedcba9876543210fedcba987654\n";

        $cmd = 'echo ' . escapeshellarg($stdin) .
               ' | bash ' . escapeshellarg($this->nativeHook) .
               ' origin https://github.com/example/repo.git 2>&1';

        $output = (string) shell_exec($cmd . '; echo "__EXIT=$?"');

        expect($output)
            ->toMatch('/__EXIT=[1-9]/',
                "AC-002: pre-push-native.sh não bloqueou push em master. Output: {$output}");
    });

    test('AC-002: pre-push-native.sh permite push em branch feature (exit 0)', function () {
        /** @covers AC-002 */
        if (! file_exists($this->nativeHook)) {
            $this->fail('AC-002: pre-push-native.sh não existe.');
        }

        $stdin = "refs/heads/feat/my-branch abc1234567890abcdef1234567890abcdef12345 refs/heads/feat/my-branch fed9876543210fedcba9876543210fedcba987654\n";

        $cmd = 'echo ' . escapeshellarg($stdin) .
               ' | bash ' . escapeshellarg($this->nativeHook) .
               ' origin https://github.com/example/repo.git 2>&1';

        $output = (string) shell_exec($cmd . '; echo "__EXIT=$?"');

        expect($output)
            ->toContain('__EXIT=0',
                "AC-002: pre-push-native.sh bloqueou push em branch feature indevidamente. Output: {$output}");
    });
});
