<?php

declare(strict_types=1);

/**
 * Slice 003 — AC-005
 *
 * Verifica que o step de geração do SBOM no ci.yml não usa `|| true`
 * (que mascara falha silenciosa) e que o upload-artifact está configurado
 * para expor o artefato sbom-php.xml na aba Artifacts.
 *
 * Estado ATUAL (scaffold slice 001) — falha silenciosa, AC-005 não verificável:
 *   run: composer global require cyclonedx/cyclonedx-php-composer:^5 || true
 *        ~/.composer/.../cyclonedx-php-composer ... || echo "::warning::..."
 *
 * Estado exigido após slice 003 — obrigatório, sem fallback:
 *   run: composer global require cyclonedx/cyclonedx-php-composer:^5 --no-interaction
 *   run: ~/.composer/vendor/bin/cyclonedx-php-composer --output-format XML --output-file sbom-php.xml
 *   (sem || true em nenhum dos steps)
 */

// AC-005: step CycloneDX de geração do SBOM não usa || true
test('AC-005: geração do sbom-php.xml não usa || true (falha não pode ser silenciada)', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    // Extrai apenas o bloco do job security para análise isolada
    // (evita falso negativo caso || true apareça em outro job por razão legítima)
    if (preg_match('/security:\s.*?(?=\n  \w+:|\z)/s', $content, $matches)) {
        $securityBlock = $matches[0];
    } else {
        $securityBlock = $content;
    }

    // O bloco não pode conter || true em qualquer step que gere ou instale CycloneDX
    $hasSilentFallback = str_contains($securityBlock, 'cyclonedx') &&
        str_contains($securityBlock, '|| true');

    expect($hasSilentFallback)->toBeFalse(
        'AC-005 exige que a geração do SBOM falhe explicitamente (exit 1) se o CycloneDX falhar. '
        .'O scaffold atual usa "|| true" que mascara falhas — sbom-php.xml pode não existir e o job não falharia.'
    );
})->group('slice-003', 'ac-005');

// AC-005: upload-artifact está configurado para o sbom
test('AC-005: upload-artifact está configurado para expor sbom na aba Artifacts', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    expect(str_contains($content, 'upload-artifact'))->toBeTrue(
        'AC-005 requer actions/upload-artifact para disponibilizar sbom-php.xml na aba Artifacts.'
    );

    expect(str_contains($content, 'sbom'))->toBeTrue(
        'AC-005 requer que o upload-artifact referencie o artefato sbom (sbom-php.xml ou sbom-*.xml).'
    );
})->group('slice-003', 'ac-005');

// AC-005: o artefato gerado usa o nome específico do pacote PHP.
test('AC-005: o step CycloneDX gera sbom-php.xml como arquivo de saída', function (): void {
    $ciYmlPath = base_path('.github/workflows/ci.yml');
    expect(file_exists($ciYmlPath))->toBeTrue("ci.yml não encontrado em {$ciYmlPath}");

    $content = file_get_contents($ciYmlPath);

    // O step de geração deve especificar --output-file sbom-php.xml
    // ou --output-file=sbom-php.xml
    $hasOutputFile = str_contains($content, 'sbom-php.xml');

    expect($hasOutputFile)->toBeTrue(
        'AC-005 requer que o CycloneDX gere sbom-php.xml explicitamente. '
        .'O upload-artifact referencia sbom-php.xml — se o step não o gerar com esse nome, '
        .'o artefato não será publicado na aba Artifacts.'
    );

    // Confirma que o step de instalação do CycloneDX não usa || true
    $installLineWithSilentFallback = (bool) preg_match(
        '/composer global require cyclonedx.*\|\| true/',
        $content,
    );

    expect($installLineWithSilentFallback)->toBeFalse(
        'AC-005: a instalação do cyclonedx-php-composer não pode usar || true. '
        .'Se a instalação falhar silenciosamente, o sbom-php.xml não será gerado.'
    );
})->group('slice-003', 'ac-005');
