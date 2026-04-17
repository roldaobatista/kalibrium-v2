# spike-inf007 — PoC descartável (Slice 015, Spike INF-007)

Este diretório é um **Proof-of-Concept descartável** criado para o Spike INF-007 (Slice 015). Sua única finalidade é provar que as versões declaradas em ADR-0015 instalam sem conflitos de peer dependencies.

## Status

- **Tipo:** PoC descartável pós-spike.
- **Criado em:** 2026-04-16 (branch `work/offline-discovery-2026-04-16`).
- **Vai virar scaffold real?** NÃO. E15-S02 criará um projeto novo em diretório separado (ex: `client/` ou `apps/mobile/`). Este `spike-inf007/` é removido ou mantido como arquivo histórico conforme decisão do PM.

## O que está aqui

- `package.json` — declara as versões exatas de ADR-0015.
- `.gitignore` — ignora `node_modules/`.
- `npm-install.log` — log capturado em execução local do `npm install`. Se o arquivo contém apenas a justificativa de ambiente sem execução, E15-S02 deve substituí-lo por um log real.
- Este `README.md`.

## Como reproduzir o `npm install` (em E15-S02)

Pré-requisito: **Node.js LTS 20.x**.

```bash
cd spike-inf007
npm install 2>&1 | tee npm-install.log

# Verificar ausência de "npm ERR!"
grep -E "npm ERR!|ERROR" npm-install.log || echo "npm install limpo."
```

### Fallback se peer deps conflitarem

Ordem permitida (proibido `--force`):

1. Aceitar warning documentado (preferido).
2. Adicionar `overrides` em `package.json` com rationale.
3. `npm install --legacy-peer-deps` com rationale explícito no log.
4. Recomendar emenda à ADR-0015 se incompatibilidade for real de runtime.

## Proibições

- NÃO adicionar código de produção aqui. Este diretório **não evolui** para `client/`.
- NÃO commitar `node_modules/`.
- NÃO commitar tokens, credenciais, dumps de banco. Este PoC é 100% sintético.

## Referências

- `specs/015/spec.md`
- `specs/015/plan.md`
- `docs/frontend/api-endpoints.md`
- `docs/frontend/stack-versions.md`
- ADR-0015 (stack offline-first)
- ADR-0016 (multi-tenancy)
