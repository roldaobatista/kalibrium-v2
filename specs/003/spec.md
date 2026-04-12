# Slice 003 — Pipeline CI (PHPStan + Pest + Pint + Rector + SBOM)

**Story:** E01-S03
**Status:** draft

---

## Contexto

O projeto Kalibrium V2 já tem o scaffold Laravel 13 (slice 001) e a infraestrutura de dados PostgreSQL + Redis (slice 002). Esta slice configura o pipeline de integração contínua no GitHub Actions para garantir que nenhum código chega ao staging sem passar por análise estática, testes, formatação, refactoring check e geração de SBOM.

## Jornada alvo

Desenvolvedor faz push para branch de feature → GitHub Actions executa automaticamente os 5 jobs (lint, static-analysis, rector, tests, sbom) → PR para main mostra check verde apenas se todos passarem → artefatos de coverage e SBOM ficam disponíveis na aba Actions.

## Acceptance Criteria

- **AC-001:** Push para branch de feature com código formatado incorretamente faz o job `lint` falhar (exit 1 verificável nos logs do Actions).
- **AC-002:** Push para branch de feature com erro de tipo PHPStan nível 8 faz o job `static-analysis` falhar.
- **AC-003:** Push para branch de feature com teste falhando faz o job `tests` falhar.
- **AC-004:** Pull request para `main` com todos os jobs verdes exibe check verde no GitHub (status `success` via GitHub API).
- **AC-005:** Artefato `sbom-php.xml` é gerado e disponibilizado na aba "Artifacts" do workflow após execução bem-sucedida.

## Fora de escopo

- Deploy (E01-S04)
- Scan de vulnerabilidades CVE (Snyk/Dependabot)
- Matrix multi-versão de PHP
- Notificações de falha de CI por Slack/e-mail
- Self-hosted runners

## Arquivos/módulos impactados

- `.github/workflows/ci.yml`
- `phpunit.xml`
- `phpstan.neon` (ajuste fino se necessário)
- `rector.php` (ajuste fino se necessário)
- `README.md` (badge de CI)

## Riscos

- Minutos do GitHub Actions limitados em plano gratuito para repo privado — mitigação: runners Linux padrão, jobs paralelos, cache agressivo.
- `cyclonedx/cyclonedx-php` pode não suportar Composer 2 completamente — mitigação: verificar versão compatível; alternativa `cyclonedx-cli` via download direto.
- Rector em `--dry-run` pode gerar falsos positivos em código gerado pelo Laravel — mitigação: excluir `storage/`, `bootstrap/cache/`, `vendor/` no `rector.php`.

## Dependências

- Slice 001 mergeada (scaffold com `phpstan.neon`, `pint.json`, `rector.php`)
- Repositório GitHub com branch `main`
