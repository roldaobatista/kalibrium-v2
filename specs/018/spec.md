# Slice 018 — Harness — CI regression + bias-free audit + schema uniformity

**Status:** draft
**Data de criação:** 2026-04-18
**Autor:** orchestrator (após retrospectiva slice-017)
**Depende de:** slice-017 merged (referência de evidência); B-036 + B-037 + B-038 + B-041 em `docs/guide-backlog.md`
**Lane:** L3 (harness — altera hooks, workflows e agent files; impacto cross-cutting)

---

## Contexto

A sessão do slice-017 (2026-04-17 / 2026-04-18) expôs 4 lacunas estruturais no harness — todas com evidência concreta:

1. **Regressão silenciosa cross-slice** — o slice 017 quebrou o teste `ac-001-dev-server` do slice 016 sem nenhum gate acusar. Só foi detectado por pedido manual do PM. Corrigido em `0aed77f`. Origem: `mechanical-gates.sh` só roda testes do slice ativo; não há CI que execute o conjunto completo em PR, nem smoke suite no pre-push.
2. **Bias em auditoria/re-auditoria** — o orchestrator repassou verdict pronto e lista de findings em retry após truncagem de sub-agent, contaminando o auditor com o resultado que deveria descobrir sozinho. R3/R11 está documentado mas não é enforçado mecanicamente.
3. **Writers de gate output divergentes** — durante `merge-slice.sh` tivemos que normalizar manualmente 3 JSONs (verify, security-review, functional-review): `$schema` como URL vez de literal, `slice` ausente, `gate` com valor errado. Sub-agents não estão emitindo schema uniforme.
4. **Sub-agents inferem paths erroneamente** — qa-expert buscou `frontend/` que não existe neste repo. Causou 2 retries improdutivos. Falta contrato explícito de estrutura do repositório nos agent files.

Este slice resolve os 4 antes de qualquer slice funcional (E15-S04 em diante). Decisão do PM registrada em `project-state.json` / `docs/handoffs/latest.md`.

## Jornada alvo

Jornada do orchestrator (agente, não usuário final): quando um slice novo é implementado, o pipeline de gates detecta automaticamente regressões em slices anteriores; auditores e re-auditores operam sem bias do resultado anterior; gates produzem JSON uniforme que `merge-slice.sh` aceita sem normalização manual; sub-agents conhecem a estrutura real do repo e não perdem ciclos procurando paths inexistentes.

Ao fim do slice-018, o orchestrator pode rodar um novo slice funcional sabendo que: (a) regressão cross-slice bloqueia merge, (b) re-auditoria é cega, (c) gates emitem schema pronto, (d) sub-agents vão direto aos paths certos.

## Artefatos a criar neste slice

Os ACs abaixo referenciam os seguintes artefatos novos — **todos são outputs deste slice** (não existem antes do merge):

- `.github/workflows/test-regression.yml` — workflow de CI
- `scripts/smoke-tests.sh` — runner de smoke suite local
- `scripts/detect-shared-file-change.sh` — detector de toque em arquivos compartilhados
- `scripts/check-forbidden-path.sh` — enforcer mecânico do contrato de paths
- `scripts/validate-audit-prompt.sh` — validator de prompt (1st-pass e re-audit)
- `scripts/validate-gate-output.sh` — validator de JSON de gate output
- `scripts/audit-set-difference.sh` — comparador semântico entre rodadas
- `docs/protocol/audit-prompt-template.md` — template obrigatório de prompt de auditoria
- `docs/protocol/blocked-tokens-re-audit.txt` — lista fechada de tokens proibidos em re-auditoria
- `docs/protocol/forbidden-paths.txt` — lista fechada de paths proibidos
- Seção `## Saída obrigatória` adicionada em 5 agent files (`qa-expert.md`, `architecture-expert.md`, `security-expert.md`, `product-expert.md`, `governance.md`)
- Seção `## Paths do repositório` adicionada em 12 agent files (todos em `.claude/agents/`)
- Fixtures de teste: 3 JSON inválidos (1 por tipo de violação de schema) + prompts de auditoria contaminados (para teste do validator)

Arquivos atualizados (não criados):

- `scripts/pre-push` hook — passa a invocar `detect-shared-file-change.sh` + `smoke-tests.sh`
- `scripts/merge-slice.sh` — passa a (a) invocar `validate-gate-output.sh` como pré-check; (b) alinhar `required_gates` ao enum canônico do schema (linha 65-78 hoje usa `"code-review"`, deve migrar para `"review"` conforme `docs/protocol/schemas/gate-output.schema.json`); (c) manter compat com JSONs históricos de slices 001-017 que usam valores legacy via aliases (mapeamento `{"code-review": "review", "security": "security-gate", "functional": "functional-gate"}`).
- `docs/protocol/06-estrategia-evidencias.md` — adiciona seção "Auditoria sem bias"

## Acceptance Criteria

**Regra:** cada AC vira **pelo menos um** teste automatizado (P2).

### B-036 — Regressão cross-slice automática

- **AC-001:** Dado um PR aberto com mudança em `src/` ou `tests/`, quando o push dispara CI, então o workflow `.github/workflows/test-regression.yml` executa `npm run test:scaffold` + `npx playwright test` (todos os projects: dev-chromium + chromium preview) e o status do PR vira `failure` se qualquer teste quebrar, bloqueando merge via ruleset.
- **AC-001-A:** Dado um PR que quebra um teste de slice anterior (ex.: slice 016 após mudança de slice 017), quando CI roda, então o job falha explicitando o AC quebrado (`ac-001-dev-server`) e o log de CI aponta o arquivo violado.
- **AC-002:** Dado que um commit toca um arquivo compartilhado (lista fechada em `scripts/detect-shared-file-change.sh`: `src/main.tsx`, `vite.config.ts`, `package.json`, `capacitor.config.ts`, `playwright.config.ts`, `.claude/settings*.json`), quando `pre-push` hook roda, então executa `scripts/detect-shared-file-change.sh` (compara `git diff --name-only @{push}..HEAD` contra a lista fixa; exit 0 = tocou, exit 1 = não tocou) e, se exit 0, dispara `scripts/smoke-tests.sh` que executa a tag `@smoke` (10-15 testes críticos cobrindo: auth, scaffold render, PWA offline) e bloqueia push se qualquer teste falhar.
- **AC-002-A:** Dado um commit que NÃO toca arquivo compartilhado (`detect-shared-file-change.sh` retorna exit 1), quando `pre-push` roda, então o smoke não é executado — push prossegue direto. Teste: commit de 1 arquivo de `docs/` verifica que push passa sem disparar smoke.

### B-037 — Auditoria sem bias (re-audit cego)

- **AC-003:** Dado que o orchestrator vai invocar um auditor/gate pela 1ª vez no slice, quando o prompt é gerado, então o conteúdo é construído a partir do **template obrigatório `docs/protocol/audit-prompt-template.md`** (criado neste slice) contendo exclusivamente os seguintes campos obrigatórios e nada além:
  - `story_id` (formato `E??-S??`)
  - `slice_id` (formato `NNN`)
  - `mode` (modo canônico do gate: `audit-spec` | `verify` | `code-review` | `security-gate` | `audit-tests` | `functional-gate` | `master-audit`)
  - `perimeter_files` (lista de paths-raiz autorizados para leitura; ex.: `["specs/018/spec.md", "docs/constitution.md"]`)
  - `criteria_checklist` (lista numerada dos critérios do gate, copiada literal do agent file do modo)
  - `output_contract` (bloco JSON schema literal esperado de volta)
  - NÃO inclui: veredito/findings de rodadas anteriores, hashes de fix commits, lista de arquivos tocados pelo fixer, IDs de findings prévios, commit hash de fix. O template é validado por `scripts/validate-audit-prompt.sh --mode=1st-pass <prompt-file>` que verifica presença dos 6 campos + ausência de seções proibidas.
- **AC-003-A:** Dado que o orchestrator vai invocar RE-auditoria (rodada ≥ 2 do mesmo gate), quando o prompt é gerado, então:
  - (a) o prompt passa pelo validator mecânico `scripts/validate-audit-prompt.sh --mode=re-audit <prompt-file>`;
  - (b) o validator rejeita se encontrar qualquer token da **lista fechada de tokens proibidos** (registrada em `docs/protocol/blocked-tokens-re-audit.txt` versionada):
    - `finding anterior`, `findings anteriores`, `previously found`, `previous finding`
    - `foi corrigido`, `já corrigido`, `fix applied`, `fixer tocou`, `fixer corrigiu`
    - `verifique se X foi`, `confirme que X foi`, `re-check`, `re-audit`, `rodada anterior`, `rodada 1`, `rodada 2` (qualquer `rodada [0-9]+`)
    - IDs de findings prévios no formato `[A-Z]{1,4}-[0-9]{3}-[0-9]{3}` que referenciem o mesmo slice (regex configurável);
    - hash de commit de fix (qualquer `[a-f0-9]{7,40}` referenciado adjacente a palavras `fix`, `correção`, `corrigir`);
    - caminhos de arquivo listados como "tocados pelo fixer" (linha com prefixo `tocado:`, `changed:`, `fixer modificou:`);
  - (c) exit code 0 = prompt limpo, exit code 1 = contaminação detectada com linha+token reportados.
- **AC-004:** Dado que um auditor/gate sub-agent recebe um prompt que passa por `validate-audit-prompt.sh` mas que um humano/LLM ainda consegue perceber como contaminado, quando ele processa o prompt, então o agent file de cada modo de auditoria instrui recusa: retorna `verdict: contaminated_prompt` + `contamination_evidence: "<token ou passagem>"` antes de investigar os artefatos.
- **AC-004-A:** Dado que o orchestrator comparou findings de 2 rodadas, quando aplica set-difference, então produz 3 listas nomeadas (`resolved = prévios \ atuais`, `unresolved = prévios ∩ atuais`, `new = atuais \ prévios`) por assinatura semântica (`categoria + descrição_normalizada + path_sem_linha`), resiliente a movimentação de código.

### B-038 — Schema uniforme de gate output

- **AC-005:** Dado que um gate sub-agent emite seu JSON final, quando `scripts/validate-gate-output.sh specs/NNN/<arquivo>.json` é executado, então exige literal `"$schema": "gate-output-v1"`, `"slice": "NNN"`, `"gate": "<nome canônico>"` (`verify` | `review` | `security-gate` | `audit-tests` | `functional-gate` | `master-audit` — conforme enum do schema normativo em `docs/protocol/schemas/gate-output.schema.json`). JSON fora desse contrato é rejeitado com mensagem clara.
- **AC-005-A:** Dado que este slice adiciona/atualiza agent files dos 5 modos de gate (`qa-expert.md`, `architecture-expert.md`, `security-expert.md`, `product-expert.md`, `governance.md`), quando o slice é mergeado, então cada um possui seção obrigatória `## Saída obrigatória` contendo os valores literais do schema (`$schema: "gate-output-v1"`, `gate: <nome canônico>`, `slice: "<NNN>"`) e um exemplo JSON inline válido. Teste verifica: (a) presença da seção em cada agent file, (b) presença dos 3 literais, (c) exemplo JSON parseable e conforme `docs/protocol/schemas/gate-output.schema.json`.
- **AC-006:** Dado que `scripts/merge-slice.sh` é invocado no slice 018 ou posterior, quando os 5 gates obrigatórios foram emitidos pelos sub-agents atualizados, então o script aceita todos sem necessidade de normalização manual (zero edits entre emissão e merge), e `git status` entre emissão dos JSONs e execução do merge-slice mostra apenas os arquivos emitidos (não há Edit posterior ao conteúdo dos JSONs).
- **AC-006-A:** Dado que um sub-agent emite um JSON não-conforme (ex.: `$schema` como URL ao invés do literal `"gate-output-v1"`, ou `gate` com valor fora da lista canônica, ou `slice` ausente), quando `scripts/validate-gate-output.sh <arquivo>.json` é executado, então exit code = 1 com mensagem apontando linha+campo violador (ex.: `"specs/018/security-review.json:5 — gate='security' esperado 'security-gate'"`). Este comportamento é testado por teste automatizado com 3 JSONs fixture propositalmente inválidos (1 por tipo de violação: `$schema` errado, `gate` errado, `slice` ausente).

### B-041 — Contrato de paths do repositório

- **AC-007:** Dado que um sub-agent consulta a estrutura do repositório, quando lê seu agent file, então encontra seção "Paths do repositório" com:
  - lista exata de dirs raiz (`src/`, `tests/`, `specs/`, `docs/`, `scripts/`, `public/`, `epics/`, `.claude/`, `.github/`);
  - guardrail explícito "NÃO existe subpasta `frontend/` neste repo";
  - instrução "se em dúvida sobre path, usar Glob antes de Read".
- **AC-007-A:** Dado um path qualquer passado como argumento, quando `scripts/check-forbidden-path.sh <path>` é invocado, então (a) retorna exit 1 com mensagem canônica `ContractViolation: path "<x>" proibido — ver docs/protocol/forbidden-paths.txt` se o path começa com qualquer prefixo da lista fechada (`frontend/`, `backend/`, `mobile/`, `apps/`); (b) retorna exit 0 se o path não casa com nenhum prefixo proibido. Teste mecânico: casos fixos (`frontend/foo.ts` → exit 1; `src/main.tsx` → exit 0; `backend/` → exit 1). Agent files instruem que o sub-agent invoque este script antes de Read em paths suspeitos e pare na 1ª violação sem retry.

## Fora de escopo

- **B-039 (telemetria de slice)** — fica no backlog. Nada neste slice mexe em `.claude/telemetry/`.
- **B-040 (limite S4 mesmo cluster)** — política para harness-learner, não implementada aqui.
- **Criar novos sub-agents** — apenas atualizar agent files existentes.
- **Refatorar protocolo v1.2.4** — apenas adicionar nova seção em `06-estrategia-evidencias.md` para B-037; não reescrever nenhum doc.
- **Rodar os gates manualmente contra slice 017 retroativamente** — só aplica do slice 019 em diante.
- **Hook `auditor-input-lint.sh` mecânico** (opção 4 do B-037) — ficará registrado como S5 advisory para slice futuro; o AC-004 cobre a recusa pelo próprio agente.

## Dependências externas

- GitHub Actions (CI workflows) — já ativo, não requer setup.
- Ruleset do repo (`main`) já bloqueia merge com check failures — configurado.
- Workflow atual `.github/workflows/` tem referência para padrão — inspecionar e estender.
- ADRs relevantes: nenhum novo. Este slice não muda decisão arquitetural, apenas operacionaliza princípios já em `docs/constitution.md` (R3, R11).

## Riscos conhecidos

- **R1: smoke suite pode ficar lenta** → mitigação: cap em 15 testes tagueados `@smoke`, execução paralela, target <30s. Se exceder, `pre-push` vira `async` (grava resultado mas não bloqueia) + CI mantém bloqueio.
- **R2: set-difference por assinatura semântica pode dar falso match** (AC-004-A) → mitigação: normalização padrão (lowercase, trim, remove trailing linenum), mas aceitar 5% de falso positivo como dívida conhecida; documentar em `docs/protocol/06-estrategia-evidencias.md`.
- **R3: agentes legados (agents v2) ainda referenciados no verifier-sandbox.sh selado** — se as mudanças de agent file impactarem allowlist, abortar e ajustar via relock-harness.sh manual pelo PM.
- **R4: workflow CI pode duplicar com workflows existentes** → auditar `.github/workflows/` antes de adicionar; merge onde possível.
- **R5: contrato de paths pode ficar desatualizado** quando a estrutura mudar (ex.: futura introdução de `backend/` ou `mobile/`) → adicionar lembrete no CLAUDE.md §9 para atualizar agent files ao mudar layout raiz.

## Notas do PM (humano)

- Decisão estratégica: resolver os 4 antes de avançar E15-S04. Registrado em `project-state.json`.
- Prioridade interna sugerida: B-036 (mais alto impacto) → B-037 (confiança no processo) → B-038 (fricção mecânica) → B-041 (quality-of-life).
- Abrir 1 PR único para o slice inteiro ou 1 por débito? Recomendação: 1 PR por débito para facilitar review, mas aceitar 1 PR agregado se PM preferir (registrar no plan).
