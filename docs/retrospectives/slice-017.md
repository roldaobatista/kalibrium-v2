# Retrospectiva slice-017 — E15-S03 PWA Shell

**Data:** 2026-04-18
**Duração:** ~2 sessões (2026-04-17 dia inteiro + 2026-04-18 madrugada)
**Resultado:** approved (6/6 gates + master-audit dual-LLM consenso pleno) + MERGED em main via PR #49 (`f472326`)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Commits no slice | 25 |
| ACs totais | 14 (AC-001..AC-008 + 6 edges -A) |
| ACs verdes plenos | 11 |
| ACs parciais S4 ambiental | 3 (Chromium headless) |
| Gates approved na 1ª rodada | 5/6 (verify + security + test-audit + functional + master-audit) |
| Gates que entraram em loop fixer | 1 (code-review: 2 S3 → 1 fix → approved) |
| Rodadas de fixer | 1 |
| Reconciliação dual-LLM | 0 (consenso pleno) |
| Débito técnico novo | 0 |
| Findings S4/S5 aceitos como dívida documentada | 9 (3 ambientais + 6 de escopo) |

Telemetria detalhada: `docs/retrospectives/slice-017-report.md` (vazia — telemetria não foi gravada durante este slice; conhecido — alimentado pelo backlog B-033).

## O que funcionou

- **Pipeline pré-impl 5/5 sem fixer loop** — audit-spec, draft-plan, review-plan, draft-tests, audit-tests-draft todos approved na 1ª rodada. Contrato ADR-0017 de rastreabilidade AC (14/14) funcionou.
- **Isolamento R3/R11 dos 4 gates em paralelo** — cada sub-agent formou juízo independente. Code-review detectou 2 S3 que os outros não tinham motivo para pegar (escopo próprio). Isolamento funcionou exatamente como protocolo promete.
- **Dual-LLM (2× Opus 4.7)** — consenso pleno sem reconciliação. A política pós-descontinuação do Codex/GPT-5 (2026-04-17) segue válida: 2 instâncias Opus em contextos isolados atendem R11 com qualidade.
- **Fixer-loop atômico** — builder:fixer com escopo estrito ("só F-001 + F-002, nada além") produziu correção cirúrgica em 1 commit (`ea8e056`, -48/+10 linhas em serve-https.mjs + 2 tags em index.html).
- **AC-007 defesa em profundidade** — isolamento `/api/*` implementado em 3 camadas independentes (navigateFallbackDenylist + guards pathname.startsWith + truque String.fromCharCode para zerar literal do bundle). Execução acima do exigido; security-gate e master-audit registraram como ponto forte.

## O que não funcionou

- **Regressão silenciosa entre slices** — commit que introduziu SW registration (slice 017) quebrou `ac-001-dev-server` (slice 016) sem qualquer gate local acusar. Só foi detectado quando o PM pediu validação manual. Fix em `0aed77f`. Evidência concreta do gap estrutural que o B-036 endereça.
- **Orchestrator vazou bias em retry** — na primeira rodada de re-auditoria quando um sub-agent truncou, o orchestrator chegou a repassar verdict pronto do verify ao invés de deixar o auditor investigar do zero. Confirmou hipótese do PM sobre B-037.
- **qa-expert falhou 2× antes de escrever test-audit.json** — 1ª tentativa tentou path `frontend/` que não existe; 2ª tentativa teve API error. Sub-agents precisam de prompt defensivo quanto a paths.
- **Chromium headless limita PWA real testing** — `beforeinstallprompt`, `matchMedia('(display-mode: standalone)')` e cold-cache Playwright efêmero não funcionam em headless. 3 testes ficam S4 ambiental. Não é defeito, mas não temos cobertura real do caminho de instalação.
- **Schemas dos gate outputs divergem entre agentes** — tivemos que normalizar `$schema`, `slice` e `gate` names (`security` vs `security-gate`, `functional` vs `functional-gate`) para o merge-slice.sh aceitar. Writers dos sub-agents não estão uniformes.

## Gates que dispararam em falso

- Nenhum disparo falso registrado nesta sessão. Os 2 S3 do code-review foram legítimos (theme-color ausente é defeito real; código morto em serve-https.mjs era real).

## Gates que deveriam ter disparado e não dispararam

- **Gate de regressão cross-slice** — 5 gates individuais + master-audit NÃO pegaram a quebra de `ac-001-dev-server`. Verify rodou apenas os testes do slice atual. Precisa virar gate formal (B-036).
- **Gate de schema uniformity dos JSONs de gate** — merge-slice flagou divergência post-facto. Poderia virar pre-check no próprio gate ou hook de validação JSON por agent.

## Mudanças propostas

Já registradas em `docs/guide-backlog.md`:

- [x] **B-036** — Regressão gate (CI full PR + smoke pre-push + política arquivos compartilhados) — **prio alta, slice-018**
- [x] **B-037** — Auditoria/re-auditoria sem bias (perímetro livre 1a vez, zero histórico 2a, set-difference no orchestrator) — **prio alta, slice-018**

Adicionais detectados nesta retrospectiva:

- [ ] **B-038** — Writers uniformes de gate output (todos os sub-agents emitindo `$schema: "gate-output-v1"` literal + `slice` + gate names do protocolo) — prio média, slice-018 ou skill separada.
- [ ] **B-039** — Telemetria de slice precisa ser gravada automaticamente por hook (slice-017.jsonl ficou vazio). Prio média.
- [ ] **B-040** — Concentração de S4 ambientais com mesma justificativa precisa de limite estrutural (política "no mais de N S4 ambientais da mesma raiz por slice") — registrar como S5 advisory no harness-learner.
- [ ] **B-041** — Sub-agents precisam receber "contrato de paths" explícito (este repo não tem `frontend/`) — guardrail pré-explore.

## Lições para o guia

- **Regressão silenciosa é real.** Gates locais não cobrem cross-slice. Precisa de CI full em PR como safety net formal.
- **Bias em retry é real.** Qualquer re-invocação de auditor deve ser "do zero", perímetro livre, zero histórico — nunca compartilhar verdict anterior.
- **Fixer-loop atômico funciona.** Escopo estrito + 1 commit + re-gate. Evitar "enquanto estou aqui, também arrumo X".
- **Dual-LLM 2× Opus é suficiente.** Não precisa mais GPT-5/Codex para cobrir R11 — consenso consistente entre 2 instâncias Opus em contextos isolados.
- **Escalação AC-007 (defesa em profundidade)** vale documentar como padrão para multi-tenancy — 3 camadas independentes é o mínimo para algo que impacta tenant isolation.

## Próximo slice

**slice-018** dedicado a B-036 + B-037 — harness fixes prio alta. Sem isso, qualquer slice funcional próximo (E15-S04) herda os mesmos riscos observados aqui.
