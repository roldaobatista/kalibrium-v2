#!/usr/bin/env php
<?php

declare(strict_types=1);

$repoRoot = dirname(__DIR__);
chdir($repoRoot);

$arguments = $argv;
array_shift($arguments);

$mode = array_shift($arguments) ?? 'fast';
$dryRun = false;

$arguments = array_values(array_filter(
    $arguments,
    static function (string $argument) use (&$dryRun): bool {
        if ($argument === '--dry-run') {
            $dryRun = true;

            return false;
        }

        return true;
    },
));

function testScopePest(array $arguments): array
{
    return array_merge([PHP_BINARY, 'vendor/bin/pest'], $arguments);
}

function testScopeFastArguments(array $extraArguments): array
{
    return array_merge([
        '--exclude-group=build',
        '--exclude-group=tooling',
        '--exclude-group=mutates-config',
        '--exclude-group=smoke-remote',
        '--exclude-group=integration',
    ], $extraArguments);
}

function testScopeFormatCommand(array $command): string
{
    return implode(' ', array_map(
        static fn (string $part): string => str_replace('\\', '/', $part),
        $command,
    ));
}

function testScopeRun(array $command, bool $dryRun): int
{
    echo testScopeFormatCommand($command).PHP_EOL;

    if ($dryRun) {
        return 0;
    }

    $process = proc_open($command, [
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
    ], $pipes, getcwd() ?: null);

    if (! is_resource($process)) {
        fwrite(STDERR, 'Nao foi possivel iniciar comando: '.testScopeFormatCommand($command).PHP_EOL);

        return 1;
    }

    return proc_close($process);
}

function testScopeSliceCommand(string $slice, array $extraArguments): array
{
    if (! preg_match('/^[0-9]{3}$/', $slice)) {
        fwrite(STDERR, "Slice deve ter 3 digitos, recebido: {$slice}".PHP_EOL);
        exit(2);
    }

    if ($slice === '001' && file_exists('tests/slice-001/ac-tests.sh')) {
        return ['bash', 'tests/slice-001/ac-tests.sh'];
    }

    $path = "tests/slice-{$slice}";
    if (! is_dir($path)) {
        fwrite(STDERR, "Diretorio de testes do slice nao encontrado: {$path}".PHP_EOL);
        exit(2);
    }

    return testScopePest(array_merge([$path], $extraArguments));
}

$commands = [];

switch ($mode) {
    case 'fast':
        $commands[] = testScopePest(testScopeFastArguments($arguments));
        break;

    case 'integration':
        $commands[] = testScopePest(array_merge(['--group=integration'], $arguments));
        break;

    case 'build':
        $commands[] = testScopePest(array_merge(['--group=build'], $arguments));
        break;

    case 'tooling':
        $commands[] = testScopePest(array_merge(['--group=tooling'], $arguments));
        break;

    case 'mutates-config':
        $commands[] = testScopePest(array_merge(['--group=mutates-config'], $arguments));
        break;

    case 'remote':
        $commands[] = testScopePest(array_merge(['--group=smoke-remote'], $arguments));
        break;

    case 'legacy':
        $commands[] = ['bash', 'tests/slice-001/ac-tests.sh'];
        break;

    case 'slice':
        $slice = array_shift($arguments) ?? '';
        $commands[] = testScopeSliceCommand($slice, $arguments);
        break;

    case 'all':
        if (getenv('RUN_LEGACY_AC_TESTS') === '1' && file_exists('tests/slice-001/ac-tests.sh')) {
            $commands[] = ['bash', 'tests/slice-001/ac-tests.sh'];
        }

        foreach (['fast', 'integration', 'build', 'tooling', 'mutates-config'] as $group) {
            $commands[] = $group === 'fast'
                ? testScopePest(testScopeFastArguments($arguments))
                : testScopePest(array_merge(["--group={$group}"], $arguments));
        }

        if (getenv('RUN_REMOTE_SMOKE') === '1') {
            $commands[] = testScopePest(array_merge(['--group=smoke-remote'], $arguments));
        }
        break;

    default:
        fwrite(STDERR, "Modo desconhecido: {$mode}".PHP_EOL);
        fwrite(STDERR, 'Modos: fast, integration, build, tooling, mutates-config, remote, legacy, slice, all'.PHP_EOL);
        exit(2);
}

foreach ($commands as $command) {
    $exitCode = testScopeRun($command, $dryRun);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

exit(0);
