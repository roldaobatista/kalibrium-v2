# Decisão do PM — External Guides Action Plan (Blocos 8 e 9)

**Data da decisão:** 2026-04-11
**Decisor:** Product Manager (único humano ativo do projeto — CLAUDE.md §3.1)
**Formato da resposta:** "a" (aceito integralmente como extensão oficial)
**Registro:** esta decisão é o equivalente, para os Blocos 8 e 9, do que `pm-decision-meta-audit-2026-04-10.md` é para os Blocos 0-7.

---

## 1. O que foi apresentado ao PM

Plano completo em `docs/audits/progress/external-guides-action-plan.md`, criado na sessão de 2026-04-11 a partir da análise integral de `C:\PROJETOS\saas\Harness + Spec-Driven Development.md` (785 linhas, 6 perspectivas consolidadas de LLMs externas sobre harness engineering e Spec-Driven Development).

O plano classificou cada recomendação externa em 4 categorias contra o harness V2 atual:

- ✅ **Já temos** — convergência confirma que escolhas passadas continuam corretas.
- ❌ **Gap real** — 8 itens endereçáveis, consolidados no **Bloco 8**.
- ⚠️ **Merece auditoria** — 3 itens que dependem de sessão fresh, consolidados no **Bloco 9**.
- 🚫 **Conflita** — rejeitados conscientemente (`.cursorrules`, multi-harness, "humano = arquiteto técnico").

## 2. Decisão

**Aceito integralmente.** Blocos 8 e 9 passam a ser extensão oficial do plano de ação da meta-auditoria.

### 2.1 Bloco 8 — 8 itens aceitos

| # | Item | Quando começa | SELADO? |
|---|---|---|---|
| 8.1 | Skill `/clarify-slice` | pós-Bloco 2 | não |
| 8.2 | Eval suite + benchmark de regressão | pós-Bloco 3 | **sim** (pre-commit-gate) |
| 8.3 | Observabilidade estruturada dos sub-agents | pós-Bloco 5 | **sim** (verify-slice.sh, review-slice.sh, novo hook trace-lock.sh) |
| 8.4 | Cleanup workflow recorrente | pós-Bloco 5 | parcial (GitHub Action, não selado local) |
| 8.5 | Feedback loop <30s como KPI | pós-Bloco 3 | depende de auditoria do schema |
| 8.6 | `docs/environment-setup.md` | pós-Bloco 2 | não |
| 8.7 | `observability/` versionado | pós-primeiro deploy | não |
| 8.8 | Feature flags | pós-produção | não |

### 2.2 Bloco 9 — 3 auditorias aceitas

Todas em **sessão nova**, conforme regra `memory/feedback_meta_audit_isolation.md`.

| # | Pergunta a responder | Entregável | Dependência |
|---|---|---|---|
| 9.1 | `validate-review.sh` e `validate-verification.sh` fazem mechanical diff check semântico ou só validam schema JSON? | `docs/audits/internal/validate-scripts-audit-YYYY-MM-DD.md` | nenhuma — pode começar imediatamente |
| 9.2 | ADR-0002 MCP Policy segue code-exec pattern ou é tool-spam disfarçado? | `docs/audits/internal/mcp-policy-audit-YYYY-MM-DD.md` | depende de 8.3.4 (query-traces) para ter dado real |
| 9.3 | `CLAUDE.md` cresceu demais vs "curto + docs/"? | `docs/audits/internal/claude-md-sizing-audit-YYYY-MM-DD.md` | nenhuma — pode começar imediatamente |

### 2.3 Rejeições explícitas mantidas

Confirmadas pelo PM nesta decisão (já estavam implícitas no plano):

1. **`.cursorrules`** — proibido por **R1**. Nossa fonte única é `CLAUDE.md` + `docs/constitution.md`.
2. **`AGENTS.md` simultâneo** — mesma razão, **R1**.
3. **Multi-harness (Cursor/Copilot/Gemini em paralelo)** — proibido por **R2**.
4. **Modelo "humano = arquiteto técnico"** — viola `CLAUDE.md §3.1` (humano = PM). Toda recomendação externa que presumia isso passou pelo tradutor R12 antes de virar item do Bloco 8.

## 3. Restrições operacionais reafirmadas

A aceitação não relaxa nenhuma trava do harness. Continuam valendo, sem exceção:

1. **Sealed files** — nenhum item do Bloco 8 que seja SELADO (8.2, 8.3, partes de 8.5) pode ser executado sem passar por `scripts/relock-harness.sh` em terminal externo, conforme `CLAUDE.md §9`.
2. **Isolamento de sessão** — Bloco 9 não pode rodar na mesma sessão em que este plano foi criado (2026-04-11). Cada auditoria abre sua própria sessão fresh.
3. **Zero bypass de gate (R9)** — aplicado a todos os gates novos introduzidos pelos Blocos 8/9.
4. **Tradutor R12** — toda saída de decisão/recomendação para o PM passa por `/explain-slice` ou equivalente.
5. **Ordem dos blocos é obrigatória** — nada do Bloco 8 começa antes dos Blocos 2-6 do tracker principal estarem nos estados requeridos.
6. **Admin bypass** — continua congelado em 4/5, restam 1 bypass, só em P0 assinado.

## 4. Próxima ação imediata

Esta sessão (2026-04-11) encerra o trabalho de planejamento do Bloco 8/9.

**Próxima sessão Claude Code (nova):** abre em estado limpo, lê este arquivo + `external-guides-action-plan.md`, e **inicia em paralelo os itens 9.1 e 9.3** (as duas auditorias que não têm dependências).

- 9.1 e 9.3 são **read-only** sobre o código existente — não tocam selados.
- Cada auditoria produz seu entregável em `docs/audits/internal/`.
- O resultado de 9.3 pode disparar uma sub-decisão do PM sobre refactor do `CLAUDE.md` (que, por sua vez, pode exigir relock se `CLAUDE.md` estiver na lista de selados — ponto a auditar dentro da própria 9.3).

**9.2 fica em espera** até o item 8.3.4 (`query-traces.sh`) existir, que por sua vez depende do Bloco 5 estar operacional.

**Itens do Bloco 8** ficam em espera até as dependências do tracker principal serem cumpridas (Bloco 2 destrava 8.1 e 8.6; Bloco 3 destrava 8.5; Bloco 5 destrava 8.2, 8.3, 8.4).

## 5. Ações manuais do PM desbloqueadas por esta decisão

Nenhuma imediata. A aceitação é um passo **de planejamento**, não de execução. As ações manuais abertas pela sessão 01 da meta-auditoria #2 (C4, A3, A4, DPO — detalhadas em `docs/reports/pm-manual-actions-2026-04-10.md`) continuam válidas e prioritárias sobre qualquer item do Bloco 8/9.

## 6. Rastreabilidade

- **Plano detalhado:** `docs/audits/progress/external-guides-action-plan.md`
- **Tracker principal atualizado:** `docs/audits/progress/meta-audit-tracker.md` §Blocos 8 e 9
- **Documento externo de origem:** `C:\PROJETOS\saas\Harness + Spec-Driven Development.md`
- **Sessão de análise e decisão:** 2026-04-11
- **Memória (extensão):** `memory/project_meta_audit_action_plan.md`

## 7. Assinatura lógica

Esta decisão foi gravada pelo agente Claude Code na sessão 2026-04-11 após o PM responder literalmente `a` à pergunta "(a) aceitar integralmente / (b) fatiar-priorizar / (c) rejeitar itens específicos". A resposta curta é juridicamente válida porque o universo de respostas foi explicitamente limitado aos três valores na pergunta original, registrada no histórico da mesma sessão.

Se o PM quiser reverter ou restringir a aceitação, isto se faz criando um novo arquivo `docs/decisions/pm-decision-external-guides-REVISION-YYYY-MM-DD.md` — nunca editando este arquivo in-place.
