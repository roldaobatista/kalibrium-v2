<?php

use Symfony\Component\Process\Process;

function runTestScopeDryRun(array $arguments, array $environment = []): string
{
    $process = new Process(
        array_merge([PHP_BINARY, base_path('scripts/test-scope.php')], $arguments, ['--dry-run']),
        base_path(),
        array_merge(['RUN_REMOTE_SMOKE' => '0'], $environment),
    );

    $process->setTimeout(30);
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput());

    return $process->getOutput();
}

test('test scope fast excludes slow and external groups', function (): void {
    $output = runTestScopeDryRun(['fast']);

    expect($output)->toContain('--exclude-group=build');
    expect($output)->toContain('--exclude-group=tooling');
    expect($output)->toContain('--exclude-group=mutates-config');
    expect($output)->toContain('--exclude-group=smoke-remote');
    expect($output)->toContain('--exclude-group=integration');
})->group('fast');

test('test scope slice runs the selected Pest slice directory', function (): void {
    $output = runTestScopeDryRun(['slice', '006']);

    expect($output)->toContain('vendor/bin/pest');
    expect($output)->toContain('tests/slice-006');
})->group('fast');

test('test scope all keeps remote smoke tests opt-in', function (): void {
    $output = runTestScopeDryRun(['all']);

    expect($output)->toContain('--group=integration');
    expect($output)->toContain('--group=build');
    expect($output)->toContain('--group=tooling');
    expect($output)->not->toContain('--group=smoke-remote');
    expect($output)->not->toContain('tests/slice-001/ac-tests.sh');
})->group('fast');

test('test scope all keeps legacy shell tests opt-in', function (): void {
    $output = runTestScopeDryRun(['all'], ['RUN_LEGACY_AC_TESTS' => '1']);

    expect($output)->toContain('tests/slice-001/ac-tests.sh');
})->group('fast');
