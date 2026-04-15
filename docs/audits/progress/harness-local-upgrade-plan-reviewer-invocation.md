# Invocação do plan-reviewer — harness-local-upgrade-action-plan (draft-v3)

**Objetivo:** satisfazer Fase 0.4 do plano (R11 — dual-verifier). O `plan-reviewer` é invocado em sessão **completamente nova**, sem memória desta conversa, para auditar o plano-v3.

**Quando invocar:** depois que o arquivo do plano estiver commitado no git (critério 0 de §10) e antes do PM assinar a decisão em `docs/decisions/`.

---

## 1. Preparação (PM, 2 min)

1. Confirmar que `docs/audits/progress/harness-local-upgrade-action-plan.md` está commitado:
   ```bash
   git log --oneline -- docs/audits/progress/harness-local-upgrade-action-plan.md
   ```
2. Abrir **nova sessão do Claude Code** (CLI nova ou `/clear`). A sessão **não** pode ter visto esta conversa — é pré-requisito do plan-reviewer (`context: isolated`).
3. Colar o prompt abaixo como primeira mensagem.

---

## 2. Prompt literal para colar na nova sessão

```
Invoque o sub-agent `plan-reviewer` em contexto isolado para auditar um plano de upgrade do harness local (NÃO é um plano de slice — é adaptação da função do agent para auditoria de plano operacional).

## Contexto da invocação

- **Alvo da auditoria:** `docs/audits/progress/harness-local-upgrade-action-plan.md` (draft-v3)
- **Não há spec.md** — este plano não corresponde a um slice. Os "ACs" e "decisões arquiteturais" vivem dentro do próprio plano (§5 e §3 respectivamente).
- **Origem:** plano escrito por Claude Opus 4.6 após auditoria do ambiente. Passou por 2 rodadas de revisão externa (ver §13 e §14 do próprio plano). Esta é a terceira auditoria, a primeira por sub-agent formal em contexto isolado.

## Inputs permitidos para esta invocação

- `docs/audits/progress/harness-local-upgrade-action-plan.md` — plano a auditar (alvo)
- `docs/constitution.md` — princípios P1-P9 e regras R1-R14
- `docs/governance/harness-evolution.md` — processo de evolução do harness
- `docs/harness-limitations.md` — limitações conhecidas (especialmente §Edição externa de hooks)
- `docs/TECHNICAL-DECISIONS.md` — índice de ADRs
- `docs/adr/0001-stack-choice.md` — ADR de stack (para validar versões propostas no plano)
- `docs/adr/*.md` — demais ADRs vigentes
- `.claude/settings.json` — estado atual do harness (para confirmar diagnóstico do plano)
- `composer.json`, `phpunit.xml`, `.github/workflows/ci.yml`, `package.json` — para validar viabilidade técnica das entregas propostas
- `.claude/agents/plan-reviewer.md` — spec do próprio agent (auto-referência da diretiva)

## Inputs proibidos

- Código de produção (app/, tests/) — fora do escopo desta auditoria
- `git log`, `git blame`
- Outputs de outros agentes já existentes
- Qualquer arquivo referenciado em conversas anteriores que não esteja na lista permitida acima

## Adaptações do checklist do plan-reviewer para este caso

O checklist padrão do `plan-reviewer.md` assume slice. Adaptar:

1. **Cobertura de ACs** → validar que cada AC (AC-1 a AC-10) em §5 do plano tem:
   - Entrega concreta em §4 (Fase correspondente)
   - Método de validação executável
   - Entrada em `verification.json` definida em §5.1

2. **Decisões arquiteturais** → validar D1-D5 em §3 quanto a:
   - Mínimo 2 alternativas listadas
   - Razão justificada (não apenas "é melhor")
   - Reversibilidade declarada
   - Nenhuma contradição com constitution (P1-P9, R1-R14)
   - Nenhuma contradiz ADRs vigentes
   - Confirmar especificamente: versões no plano (Redis 8, Node 20, PostgreSQL 18, PHP 8.4) batem com ADR-0001

3. **Viabilidade técnica** → confirmar que:
   - Pacotes propostos existem (Lefthook via npm, não Composer — ver §3 D4 nota v2→v3)
   - Comandos propostos são sintaticamente válidos
   - Caminhos de arquivos referenciados fazem sentido (`scripts/lefthook/pest-related.sh` é path novo aceitável, `scripts/hooks/*` NÃO é tocado)
   - Suites de teste usadas têm nomenclatura correta (Slice003, não Slice-003)

4. **Riscos e mitigações** → validar §6 do plano:
   - Nenhum risco sem mitigação
   - Mitigações concretas (não "monitorar")
   - Plano de rollback em §11 é executável

5. **Segurança** → validar que:
   - Deny list proposta em Fase 1.2 não introduz vetor (ex: allow amplo em git* sobrepondo deny específico — o plano menciona isso como risco a validar empiricamente na Fase 0.1)
   - Nenhum hook em `scripts/hooks/*` é criado/alterado (arquivos selados)
   - Relock manual do PM é o único caminho para editar `.claude/settings.json`

6. **Simplicidade** → validar que:
   - Taskfile delega para composer scripts (não duplica lógica de `test-scope.php`)
   - Fase 2.5 foi rebaixada a spike (pacote watch abandoned)
   - Nenhuma abstração prematura introduzida

## Output esperado

Adaptar o path do output (o default `specs/NNN/plan-review.json` não se aplica):

**Escrever em:** `docs/audits/internal/harness-local-upgrade-plan-review-2026-04-14.json`

Estrutura do JSON segue o schema definido em `.claude/agents/plan-reviewer.md §Output`, com campos adaptados:
- `slice_id` → `"harness-local-upgrade"` (texto, não numérico)
- Adicionar campo `plan_version: "draft-v3"`
- Adicionar campo `adapted_invocation: true` com nota "Auditoria de plano operacional, não de slice — checklist adaptado no prompt de invocação"

## Diretiva adversarial (reforçar)

Sua função é **encontrar problemas**, não aprovar. O plano passou por 2 rodadas anteriores de revisão externa e está em draft-v3 — a tentação é assumir que está pronto. Resista. Qualquer finding de qualquer severidade resulta em `verdict: rejected`. Se não encontrar nada, diga explicitamente que não encontrou, mas só depois de rodar o checklist completo acima.

Pontos de atenção específicos deste plano (áreas onde v1 e v2 falharam):

- Package existence: valide que `lefthook` (npm) existe, `petecoop/pest-plugin-watch` existe, PostgreSQL 18 image oficial existe, Redis 8 image oficial existe
- Version alignment: compare versões no plano contra ADR-0001 + CI + package-lock
- Path sealing: confirme que nenhum arquivo em `scripts/hooks/` está sendo criado/alterado (só settings.json via relock PM)
- Glob matching: avalie se `Bash(git restore -- *)` em settings.json realmente bloqueia `git restore -- file.php` mas não `git restore --staged file.php`
- AC executabilidade: cada um dos 10 ACs tem comando shell concreto que retorna exit 0/1? Se algum AC exige julgamento humano, é finding.

## Handoff

1. Escrever `docs/audits/internal/harness-local-upgrade-plan-review-2026-04-14.json`.
2. Parar. Não corrigir o plano — apenas auditar.
3. O PM lerá o JSON e decidirá: approved → assinar decisão; rejected → voltar ao autor para draft-v4.
```

---

## 3. Esperado após execução

Arquivo criado: `docs/audits/internal/harness-local-upgrade-plan-review-2026-04-14.json`

Cenários:
- **verdict: approved** → PM prossegue para assinar `docs/decisions/pm-decision-harness-local-upgrade-2026-04-14.md` e autorizar Fase 1.
- **verdict: rejected com findings minor apenas** → autor do plano (Claude Opus 4.6 nesta sessão ou outra) aplica correções, gera draft-v4, re-invoca plan-reviewer.
- **verdict: rejected com findings major/critical** → revisar premissas do plano; possivelmente reescrever seções afetadas do zero.

## 4. Interpretação de resultado

Findings comuns que **não** invalidam o plano:
- Sugestão de reordenar subseções
- Terminologia inconsistente com glossário

Findings que **exigem** draft-v4:
- Decisão arquitetural sem alternativa listada
- AC sem método de validação executável
- Contradição com ADR vigente
- Pacote/versão inexistente

## 5. Registro de telemetria

Após execução, registrar em `.claude/telemetry/harness-evolution.jsonl` (via PM em relock ou arquivo de incidente):

```json
{"date": "2026-04-14", "event": "plan-reviewer-invoked", "plan": "harness-local-upgrade", "version": "draft-v3", "verdict": "<resultado>", "findings_count": <n>}
```

---

**Fim do prompt de invocação.**
