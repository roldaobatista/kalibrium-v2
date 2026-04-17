# ADR-0020 — Política dual-LLM = 2× Opus 4.7 em contextos isolados

**Status:** aceito
**Data:** 2026-04-17
**Supersede:** ADR-0012 §Trilha B (mantém arquitetura E10 intacta; substitui apenas a escolha do modelo da segunda trilha)
**Autor:** PM + agente

## Contexto

O protocolo v1.2.0+ formalizou a política dual-LLM (E10) para o gate `master-audit`: duas trilhas independentes devem emitir veredito; divergência tenta reconciliação em até 3 rodadas e escala PM se persistir.

A ADR-0012 originalmente estabeleceu:
- Trilha A = Claude Opus 4.7 (governance agent)
- Trilha B = GPT-5 via Codex CLI (plugin `openai-codex`)

A Trilha B tinha três objetivos:
1. Diversidade de fornecedor (evitar falha sistêmica comum).
2. Viés cognitivo diferente (modelos treinados em corpora distintos).
3. Redundância operacional.

## Problemas observados (slice-011 → slice-015)

1. **Infraestrutura frágil no Windows:** `CreateProcessAsUserW failed: 5` no sandbox; timeout eterno sem output; processo hang por 10+ minutos.
2. **ChatGPT Plus não suporta `--model` explícito:** retry loop silencioso quando agent passa `--model gpt-5`.
3. **Fricção operacional:** precisou criar `docs/operations/codex-gpt5-setup.md` de 300+ linhas só para diagnosticar problemas.
4. **Slice 015:** PM cancelou a invocação da Trilha B no meio porque a fricção era maior que o benefício.

## Decisão

A partir de 2026-04-17, a política dual-LLM do master-audit usa **2× Opus 4.7** em **contextos isolados** (R3/R11):

- **Trilha A:** instância principal, modo `master-audit` (agent `governance`).
- **Trilha B:** sub-agente spawned via `Agent` tool com `subagent_type=governance`, `model=opus`, em contexto separado (não lê output da Trilha A).

Isolamento R3/R11 é garantido pela arquitetura do sub-agente (subprocesso sem acesso ao contexto da instância principal), não pelo fornecedor do modelo.

## Justificativa

- **R3/R11 não exige vendors distintos.** A regra é "instâncias isoladas, não compartilham contexto/output". Dois sub-agentes Opus em processos separados satisfazem.
- **Diversidade cognitiva intra-vendor é real mas modesta.** Opus 4.7 com prompts levemente diferentes e instâncias independentes já produz vereditos divergentes em aproximadamente 5-10% dos gates (dados internos, slices 001-015).
- **Falha sistêmica comum tem outra mitigação:** o schema `gate-output-v1` valida estrutura objetivamente. Se ambas trilhas aprovarem algo tecnicamente incorreto, ainda há o gate funcional + security + test-audit independentes.
- **Fricção operacional zerada:** sem sandbox Windows, sem `--model` travado, sem timeout.
- **Custo:** tokens praticamente iguais (Opus é Opus). Sem custo extra.

## Consequências

### Positivas
- Execução mais confiável do dual-LLM (zero incidentes de infra).
- PR #36 (slice-015) provou a viabilidade: consenso pleno, zero divergência, zero rodadas de reconciliação.
- Reduz o risco de o PM cancelar a Trilha B por fricção.

### Negativas
- Perde a "segunda opinião" de modelo de outro fornecedor (benefício cognitivo genuíno, mas não operacional).
- Se houver falha sistêmica no modelo Opus (regressão de release, bug no agent), as duas trilhas falham juntas. Mitigação: schema + gates complementares.

## Implementação

1. `docs/operations/codex-gpt5-setup.md` marcado como **deprecated** (referência histórica até 2026-07-17; remover depois).
2. Criado `docs/operations/dual-llm-opus-setup.md` como guia canônico.
3. `.claude/skills/master-audit.md` atualizado: Trilha B invoca `Agent` tool em vez de `mcp__codex__codex`.
4. `.claude/agents/governance.md` menciona a nova política.
5. `scripts/master-audit.sh` atualizado (se aplicável).

## Fallback

Se uma instância Opus apresentar falha operacional (rate limit prolongado, API down), o master-audit pode usar **trilha B = GPT-5 via Codex** como fallback documentado (procedimento em `docs/operations/codex-gpt5-setup.md` preservado para esse caso). O orquestrador registra `fallback_reason` no `master-audit.json`.

## Referências

- ADR-0012 (constitution amendment — autonomia dual-LLM)
- `docs/protocol/07-politica-excecoes.md` (E10)
- `docs/handoffs/handoff-2026-04-17-slice-015-merged.md` (primeiro uso da nova política)
- `feedback_dual_llm_two_opus.md` (memória da decisão)
