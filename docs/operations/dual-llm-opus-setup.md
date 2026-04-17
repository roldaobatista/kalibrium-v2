# Dual-LLM master-audit com 2× Opus 4.7 — guia operacional

Versão: 1.0 — 2026-04-17
Autoridade: ADR-0020 (supersede ADR-0012 §Trilha B)

## Política

Gate `master-audit` exige dois vereditos independentes (protocolo v1.2.4 §9.4 + política E10).

A partir de 2026-04-17, ambas as trilhas usam **Claude Opus 4.7** em **contextos isolados** (R3/R11):

- **Trilha A** — instância principal do orquestrador, agente `governance` em modo `master-audit`.
- **Trilha B** — sub-agente `governance` spawned via `Agent` tool com `subagent_type=governance` e `model=opus`, executando em contexto separado.

**Não há mais uso de GPT-5 via Codex CLI para Trilha B** (problema de fricção operacional no Windows + ChatGPT Plus, detalhado em ADR-0020 §Problemas observados).

## Como invocar Trilha B

No orquestrador, após Trilha A emitir `specs/NNN/master-audit.json`:

```
Agent(
  description: "Master-audit Trilha B slice NNN",
  subagent_type: "governance",
  model: "opus",
  prompt: "<prompt consolidado — ver abaixo>"
)
```

### Prompt canônico da Trilha B

```text
Você é a Trilha B (Opus 4.7, instância isolada) do master-audit dual-LLM do slice NNN.

POLÍTICA (ADR-0020): dual-LLM = 2× Opus 4.7 em contextos isolados. Você é uma instância separada da Trilha A — não leia `specs/NNN/master-audit.json` (output da Trilha A). Produza veredito INDEPENDENTE.

REPO/CWD: C:\PROJETOS\saas\kalibrium-v2 (main working tree)
PROTOCOLO: v1.2.4, schema gate-output-v1 em docs/protocol/schemas/gate-output.schema.json
ZERO-TOLERANCE: approved exige blocking_findings_count == 0 (S1-S3 zerados). S4/S5 registram.

TAREFA:
1. NÃO leia specs/NNN/master-audit.json. Apague specs/NNN/master-audit-trail-b.json se existir.
2. Leia: spec.md, plan.md, tasks.md (se existir), os 8 JSONs de gate exceto master-audit.json,
   artefatos entregáveis, docs/protocol/schemas/gate-output.schema.json.
3. Verifique independentemente: re-rode Pest (`./vendor/bin/pest tests/slice-NNN/`),
   valide ACs↔testes↔artefatos, zero-tolerance upstream, escopo disciplinado.
4. Escreva specs/NNN/master-audit-trail-b.json conforme schema v1.2.4:
   - agent: "opus-4-7"
   - isolation_context: "slice-NNN-master-audit-opus-trailB-instance-01"
   - mode: "master-audit"
   - (todos os demais campos obrigatórios do schema)
5. Valide JSON com `python -m json.tool`.
6. NÃO modifique nenhum outro arquivo. Não toque em arquivos selados.

RETORNO (≤200 palavras): veredito, blocking_findings_count, findings por severidade,
se concorda com Trilha A (inferida), path exato do arquivo, divergências se houver.
```

## Consolidação dual-LLM

Após Trilha B retornar, o orquestrador atualiza `specs/NNN/master-audit.json` com:

```json
"evidence": {
  "dual_llm": {
    "trail_primary": { "model": "claude-opus-4-7", "verdict": "approved", ... },
    "trail_secondary": {
      "model": "claude-opus-4-7",
      "verdict": "approved",
      "output_path": "specs/NNN/master-audit-trail-b.json",
      "isolation_context": "slice-NNN-master-audit-opus-trailB-instance-01"
    },
    "reconciliation_rounds": 0,
    "consensus": true,
    "consensus_note": "Consenso dual-LLM pleno: ambas trilhas Opus approved, zero divergência."
  }
}
```

## Reconciliação divergente

Se Trilha A e Trilha B discordarem (um approved + um rejected, ou blocking_findings_count diferente):

1. **Rodada 1:** mostra output de cada uma para um terceiro sub-agente (Opus, modo `master-audit` reconciler). Ele produz diagnóstico.
2. **Rodada 2:** cada trilha re-avalia com o diagnóstico disponível. Se convergirem, parar.
3. **Rodada 3:** nova tentativa com input ampliado. Se ainda divergir → E10 escalação PM via `/explain-slice NNN`.

Máximo 3 rodadas. Persistindo, PM decide.

## Fallback para GPT-5

**Casos em que usar GPT-5 como Trilha B** (exceção documentada):

- Opus API down ou rate limit prolongado (> 15 min).
- Investigação explícita de viés intra-vendor (requer ADR específico de auditoria).

Procedimento de fallback: ver `docs/operations/codex-gpt5-setup.md` (deprecated, mas preservado para este caso). Registrar `fallback_reason` no `master-audit.json`.

## Referências

- ADR-0020 (esta política)
- ADR-0012 (arquitetura dual-LLM original; §Trilha B superseded)
- `docs/protocol/07-politica-excecoes.md` (E10)
- `.claude/skills/master-audit.md`
- `.claude/agents/governance.md`
