# Slice 019 — Harness — Hook git nativo + paths filter tenant atualizado

**Status:** draft
**Data de criação:** 2026-04-18
**Autor:** orchestrator (após análise de fragilidade do harness 2026-04-18)
**Depende de:** slice-018 merged (referência de evidência); B-042 + B-043 em `docs/guide-backlog.md`
**Lane:** L2 (harness tático — altera hooks git nativo e workflow CI; escopo contido)

---

## Contexto

A análise de fragilidade do harness em 2026-04-18 (após merge do slice-018) identificou 6 pontos frágeis reais. Dois têm retorno alto e esforço baixo, e são pré-requisitos razoáveis antes do próximo slice funcional (E15-S04):

1. **Hook git nativo ausente (B-042 / ponto #6).** `scripts/hooks/pre-push-gate.sh` é hook `PreToolUse Bash(git push*)` do Claude Code — só dispara quando o **agente** roda `git push`. Evidência mecânica: `ls .git/hooks/pre-push` retorna "No such file or directory". Pushes fora do Claude Code (PM via `.bat`, GitHub Actions workflow_dispatch, outra sessão Claude sem esse harness) escapam toda a rede. Atualmente funciona porque PM não pusha direto — frágil por dependência de ator.

2. **Paths filter do tenant-isolation defasado + estático (B-043 / ponto #7).** `.github/workflows/ci.yml` linhas 456-460 listam: `app/Models/**`, `app/Http/**`, `app/Livewire/**`, `app/Jobs/**`, `tests/slice-011/**`. Duplo problema:
   - `app/Livewire/**` foi demolido no slice-016 (ADR-0015, frontend Livewire removido) — lista área morta.
   - Se surgir `app/Services/Tenant/` ou qualquer nova camada sensível ao isolamento, ninguém é lembrado de atualizar o filter. Área nova escapa do gate silenciosamente.

Os outros 4 pontos (#3 AC coverage histórico, #4 visual regression, #5 mutation testing, #2 estresse do 018) vão para o slice-020 que exige ADRs separados (trade-offs de CI time, storage de snapshots, mutation budget).

## Jornada alvo

Jornada do orchestrator + do PM (agentes e humano): quando um `git push` é disparado — independentemente de ser o Claude Code, um `.bat` do PM, um workflow dispatch do GitHub ou qualquer outro caminho — a rede de proteção pre-push executa e aplica as mesmas regras (bloquear push direto em `main`, bloquear `--force` em `main/master`, etc.). Quando um PR altera código de produção sensível ao isolamento multi-tenant (qualquer camada: models, services, domain, migrations), o job `tenant-isolation` do CI dispara e valida o isolamento; áreas novas não escapam silenciosamente porque o filter é amplo o bastante e há um checker que reporta diretórios fora do filter.

Ao fim do slice-019, o orchestrator pode rodar E15-S04 (ou qualquer slice funcional) sabendo que: (a) hook git dispara para qualquer pusher, não só pro agente; (b) paths filter cobre todas as camadas de produção sensíveis hoje; (c) há warning observável quando surge camada nova fora do filter.

## Artefatos a criar neste slice

Os ACs abaixo referenciam os seguintes artefatos novos — **todos são outputs deste slice** (não existem antes do merge):

- `scripts/install-git-hooks.sh` — instalador idempotente de hooks git nativos
- `scripts/hooks/pre-push-native.sh` — wrapper nativo que invoca o gate pre-push (não é `PreToolUse` do Claude, é hook git de verdade)
- `scripts/check-tenant-filter-coverage.sh` — checker que compara diretórios de `app/` com o filter estático do workflow e warna sobre potenciais escapes
- `tests/slice-019/` — testes automatizados de cada AC (convenção ADR-0017 `@covers AC-NNN`)

Arquivos atualizados (não criados):

- `.github/workflows/ci.yml` — paths filter do job `tenant-isolation` atualizado (remove `app/Livewire/**`; adiciona `app/Services/**`, `app/Domain/**`, `database/migrations/**`)
- `scripts/hooks/session-start.sh` — passa a invocar `install-git-hooks.sh --silent` quando `.git/hooks/pre-push` não aponta para `pre-push-native.sh`
- `docs/documentation-requirements.md` — adiciona seção "Novas camadas sensíveis a tenant isolation devem atualizar paths filter"

## Acceptance Criteria

**Regra:** cada AC vira **pelo menos um** teste automatizado (P2). Todos os testes residem em `tests/slice-019/` com nomenclatura `ac-NNN-<slug>.test.*` + tag `@covers AC-NNN` no docblock (ADR-0017).

### B-042 — Hook git nativo (pre-push universal)

- **AC-001:** Dado o repositório em estado limpo, quando rodo `bash scripts/install-git-hooks.sh`, então:
  - (a) `.git/hooks/pre-push` é criado, executável (`chmod +x`), e contém comando que invoca `scripts/hooks/pre-push-native.sh` a partir da raiz do repo;
  - (b) o script é idempotente: executar duas vezes seguidas produz exatamente o mesmo `.git/hooks/pre-push` (hash SHA-256 idêntico entre execuções 1 e 2);
  - (c) `scripts/install-git-hooks.sh` imprime em stdout `installed: .git/hooks/pre-push` na 1ª execução e `already-current: .git/hooks/pre-push` na 2ª.

- **AC-002:** Dado `.git/hooks/pre-push` instalado, quando tento `git push` com commits que violam a regra R2 (push direto em `main` sem estar em PR mergeado), então o hook `pre-push-native.sh` é executado, bloqueia o push com exit ≠ 0 e imprime mensagem explicando qual regra violou. Equivalência funcional mínima com `scripts/hooks/pre-push-gate.sh` (PreToolUse do Claude): ambos bloqueiam os mesmos cenários críticos (push em main/master; `--force` em main/master).

- **AC-003:** Dado `.git/hooks/pre-push` **não** existe (deletado manualmente), quando `scripts/hooks/session-start.sh` executa (SessionStart hook), então o hook nativo é reinstalado automaticamente em modo `--silent` (sem prompt, sem bloqueio da sessão). Output esperado em stderr/stdout da sessão: uma linha `[session-start] reinstalled git hook: .git/hooks/pre-push`. A sessão prossegue normalmente após a reinstalação.

- **AC-004:** Dado um commit local em branch `main` (simulado: checkout main local + novo commit sem PR), quando tento `git push origin main` via linha de comando direta (simulação de push fora do Claude Code, via `bash -c "git push origin main"` ou equivalente), então o `.git/hooks/pre-push` nativo dispara, bloqueia o push com exit ≠ 0, e a mensagem de erro indica a violação. O teste reverte o commit após verificação (não polui main).

### B-043 — Paths filter tenant-isolation atualizado + checker

- **AC-005:** Dado `.github/workflows/ci.yml` atual (estado antes do slice-019), quando aplicado o diff deste slice, então a lista `paths` do job `tenant-isolation` (step `Check changed paths`, bloco `filters > run > paths`) contém **pelo menos**:
  - `app/Models/**`
  - `app/Http/**`
  - `app/Services/**`
  - `app/Domain/**`
  - `app/Jobs/**`
  - `database/migrations/**`
  - `tests/slice-011/**`
  - `tests/tenant-isolation/**` (qualquer novo path agregado em slices futuros)
  
  E **não contém**: `app/Livewire/**` (demolido no slice-016 via ADR-0015). Teste: grep assertivo em `.github/workflows/ci.yml` confirma presença dos paths obrigatórios e ausência de `Livewire`.

- **AC-006:** Dado o repositório em estado atual, quando rodo `bash scripts/check-tenant-filter-coverage.sh`, então:
  - (a) o script lê a lista `paths` do job `tenant-isolation` em `.github/workflows/ci.yml` (parser simples de YAML via grep/sed — não exige pyyaml);
  - (b) compara com `ls app/` (subdiretórios de 1 nível);
  - (c) emite em stdout lista dos subdiretórios de `app/` que **não** estão cobertos pelo filter, no formato `uncovered: app/<dir>/`;
  - (d) exit code é **sempre 0** nesta primeira versão (warning-only). Um slice futuro pode promover para exit ≠ 0 em modo `--strict`;
  - (e) se houver `app/<dir>/` novo não coberto que contém arquivos `.php` com string `tenant` (grep case-insensitive), o script marca com prefixo `[SUSPECT]` na saída.

- **AC-007:** Dado `docs/documentation-requirements.md` no estado atual, quando aplicado o diff deste slice, então o documento contém uma nova seção `## Camadas sensíveis a tenant isolation` com:
  - (a) declaração explícita: "Toda nova camada de código de produção (subpasta de `app/` ou `database/migrations/`) que lide com dados com `tenant_id` deve ser adicionada aos paths do job `tenant-isolation` em `.github/workflows/ci.yml`";
  - (b) apontamento para `scripts/check-tenant-filter-coverage.sh` como ferramenta de verificação;
  - (c) referência cruzada a ADR-0016 (isolamento multi-tenant).
  Teste: grep de string literal valida presença de cada um dos 3 elementos.

## Fora de escopo

- **Não implementar** hook `.git/hooks/pre-commit` nativo neste slice. Pre-push é suficiente para fechar o gap mais crítico (bloqueio de main); pre-commit envolve latência local mais sensível e pode entrar em slice futuro se tracking mostrar necessidade.
- **Não promover** `check-tenant-filter-coverage.sh` para exit ≠ 0 / bloqueante em CI. Primeira versão é warning-only para capturar dados empíricos antes de virar gate duro. Promoção futura exige retrospectiva.
- **Não resolver** pontos #3 (AC coverage histórico), #4 (visual regression), #5 (mutation testing). Esses exigem ADRs próprios (budget de CI, storage de snapshots) e ficam no slice-020.
- **Não tocar** em `scripts/hooks/pre-push-gate.sh` (PreToolUse do Claude). O hook nativo é criado em PARALELO para cobrir pushers externos — não substitui o PreToolUse do Claude que continua ativo quando o agente orquestra. Duplicação de cobertura é intencional (defesa em profundidade R3/R11).

## Gates aplicáveis

Pipeline padrão de slice L2:

1. `/audit-spec 019` — qa-expert (modo: audit-spec)
2. `/draft-plan 019` — architecture-expert (modo: plan)
3. `/review-plan 019` — architecture-expert (modo: plan-review, instância isolada)
4. `/draft-tests 019` — builder (modo: test-writer) — 7 testes red (um por AC), todos com `@covers AC-NNN`
5. `/audit-tests-draft 019` — qa-expert (modo: audit-tests-draft, instância isolada) — zero findings S1-S3 para prosseguir
6. Implementer — builder (modo: implementer)
7. `/verify-slice 019` — qa-expert (modo: verify)
8. `/review-pr 019` — architecture-expert (modo: code-review, instância isolada)
9. `/security-review 019` — security-expert (modo: security-gate)
10. `/test-audit 019` — qa-expert (modo: audit-tests)
11. `/functional-review 019` — product-expert (modo: functional-gate)
12. `/master-audit 019` — governance (dual-LLM 2× Opus 4.7, instâncias isoladas)
13. `/merge-slice 019` — após master-audit approved

Gates condicionais: **não aplica** data-gate, observability-gate nem integration-gate (slice não mexe em dados nem integrações externas).

## Referências

- `docs/guide-backlog.md` — B-042, B-043 (com contexto completo)
- `.github/workflows/ci.yml` §tenant-isolation (estado atual — linhas 410-500)
- `scripts/hooks/pre-push-gate.sh` (referência PreToolUse do Claude)
- `scripts/hooks/session-start.sh` (ponto de instalação automática)
- ADR-0015 (demolição frontend Livewire)
- ADR-0016 (isolamento multi-tenant)
- ADR-0017 (rastreabilidade AC→teste)
