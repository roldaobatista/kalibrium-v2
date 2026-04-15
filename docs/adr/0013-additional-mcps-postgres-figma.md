# ADR-0013 — Adicionar MCPs de PostgreSQL e Figma à allowlist

**Status:** accepted
**Data:** 2026-04-15
**Autor:** Product Manager + Co-Authored-By Claude Opus 4.6
**Aceitação:** 2026-04-15 — PM confirmou "pode fazer" em resposta à recomendação da Opção B.
**Amenda:** ADR-0002 (MCP policy) — esta ADR adiciona duas entradas à tabela do ADR-0002 §"Allowlist inicial".

---

## Contexto

O harness atual tem 4 MCPs autorizados (`context-mode`, `context7`, `github`, `codex`). Durante execução dos slices de E02 (LGPD/RLS) e planejamento de E03 (UI/frontend), identificamos duas áreas onde sub-agents dependem de `Bash` + ferramentas externas para tarefas que ganhariam muito com MCP dedicado:

### 1. Introspecção de PostgreSQL

Sub-agents afetados: `data-modeler`, `verifier`, `security-reviewer`, `implementer`.

Operações hoje feitas via `Bash` + `psql`:
- Listar schemas, tabelas, colunas, índices.
- Inspecionar policies de Row-Level Security (crítico para épicos de LGPD).
- Validar que migrations aplicadas batem com ERDs em `docs/architecture/data-models/`.
- Conferir permissões de roles por tabela.

Problemas com a abordagem atual:
- Output de `psql` é verboso e inflaciona contexto (`psql \d+ tabela` em tabela com ~30 colunas já passa de 50 linhas).
- Parsing é frágil (cada agente re-implementa extração de policies, colunas, etc.).
- Não há check consistente de "RLS está ativa nesta tabela?" — cada slice reinventa.

### 2. Leitura de designs do Figma

Sub-agent afetado: `ux-designer`.

Hoje: PM descreve telas em português livre no `/intake`. `ux-designer` traduz para wireframes Markdown em `docs/design/`. Esse ciclo funciona quando a referência visual é texto, mas o PM já sinalizou que pretende usar Figma para fluxos de E03+ (dashboards, settings, onboarding).

Sem MCP, o fluxo seria:
- PM exporta screenshots → cola no chat → agente interpreta pixel-por-pixel.
- Erros de tradução frequentes (cores, espaçamentos, componentes).
- Impossível re-sincronizar quando o design muda no Figma.

Com MCP: `ux-designer` lê nós específicos do Figma por URL/ID, extrai tokens e estrutura, e mantém `docs/design/` em sincronia rastreável.

### Por que decidir agora

- E02-S07 (slice atual, LGPD) já bate contra o limite de parsing manual de RLS policies.
- E03 começa com docs de UI, onde o Figma MCP será ativo desde o primeiro slice — melhor oficializar antes de ter slice parado esperando.
- ADR-0002 §"Allowlist inicial" exige ADR explícito para cada adição. Este é esse ADR.

---

## Opções consideradas

### Opção A: Adicionar os dois MCPs agora

- **Descrição:** `@modelcontextprotocol/server-postgres` (oficial Anthropic) + `figma-developer-mcp` (comunidade, amplamente usado).
- **Prós:**
  - Resolve dois gargalos concretos e já visíveis.
  - Reduz consumo de tokens em gates de dados (menos parsing de `psql`).
  - Alinha fluxo de UI com ferramenta que o PM já usa.
- **Contras:**
  - Dois vetores novos de injeção/contaminação para auditar.
  - `figma-developer-mcp` é comunitário (não é Anthropic oficial) — precisa de confiança do projeto, mesmo que código seja inspecionável.
  - Exige token Figma com permissão de leitura (um segredo novo a gerenciar).
- **Custo de reverter:** baixo (remover linhas de `allowed-mcps.txt` + settings.json, rodar relock).

### Opção B: Adicionar só Postgres MCP, adiar Figma

- **Descrição:** Oficializar apenas o MCP oficial (Anthropic). Reavaliar Figma após primeiro slice de UI de E03 rodar sem ele, com dados empíricos sobre a dor real.
- **Prós:**
  - Menor superfície nova agora.
  - MCP Postgres é oficial — confiança alta.
  - Deixa o bar empírico para Figma: se o ciclo atual (screenshots + texto) funcionar em E03-S01, evita MCP desnecessário.
- **Contras:**
  - Se E03-S01 sofrer por falta de Figma MCP, re-abrimos ADR — 2 rodadas de fricção em vez de 1.
  - PM já sinalizou intenção de usar Figma, evidência antecipada do gap.
- **Custo de reverter:** baixo.

### Opção C: Não adicionar nada, investir em wrappers shell

- **Descrição:** Criar `scripts/db-inspect.sh` com subcomandos (`policies`, `schema`, `rls-status`) que devolvem JSON estruturado. Figma fica como manual.
- **Prós:**
  - Zero novos MCPs a auditar.
  - Scripts shell ficam selados pelo `hooks-lock` (se colocados em `scripts/hooks/`) — mesma garantia de integridade.
  - Controle total sobre output (sem depender de evolução de MCP externo).
- **Contras:**
  - Re-implementação do que o MCP já resolve, gasto de dev interno.
  - Não ataca o problema do Figma (ainda teríamos screenshots).
  - Wrappers shell continuam sendo `Bash` do ponto de vista do agente — output volumoso, a contramedida `context-mode` precisa intervir. MCP Postgres entrega dados diretamente no contexto do agente em formato controlado.
- **Custo de reverter:** médio (manter 2 scripts internos é dívida pequena mas persistente).

---

## Decisão

**Escolhida:** Opção B — adicionar apenas Postgres MCP agora, adiar Figma.

**Razão:** o MCP oficial Anthropic tem confiança alta, a dor é concreta hoje (E02 está ativo) e o retorno é imediato. O Figma MCP, apesar de desejável, é:
- Comunitário (exige avaliação de código do projeto mantenedor).
- Resolve uma dor **futura** (E03 ainda não começou).
- Fácil de adicionar em nova ADR quando a dor for empírica.

O modelo é "provar necessidade antes de expandir superfície de ataque", consistente com ADR-0002 (allowlist rígida, fricção deliberada).

**Reversibilidade:** fácil — remover uma linha de `.claude/allowed-mcps.txt`, uma entrada de `.claude/settings.json`, rodar relock.

---

## Consequências

### Positivas
- `data-modeler`, `verifier`, `security-reviewer` passam a ter API estruturada para schema/RLS.
- Reduz parsing manual de output de `psql` (≈50-80 linhas por inspeção típica).
- Cria base para futuros sub-agents de análise de dados sem reinventar wrappers.

### Negativas
- Um vetor adicional de potencial injeção via MCP (mitigado: MCP oficial Anthropic, código auditável).
- Segredo novo no ambiente (`DATABASE_URL` exposta ao MCP) — já existe localmente, mas agora acessível ao agente via MCP também.

### Riscos
- **Vazamento de dados sensíveis em contexto.** Se o MCP Postgres for usado pra `SELECT *` em tabela com PII (users, consent_records), dados vão pro contexto do agente. **Mitigação:** convenção operacional em `.claude/agents/data-modeler.md` e `security-reviewer.md` — usar `\d+` equivalentes (schema only), evitar `SELECT` em tabelas PII sem justificativa no output. Validar em `/guide-check` periódico.
- **MCP desatualizado.** Se `@modelcontextprotocol/server-postgres` parar de receber updates, dependência morta. **Mitigação:** é projeto oficial Anthropic, ativa; ainda assim, `/mcp-check` detecta drift da versão instalada.

### Impacto em outros artefatos

- **ADR-0002:** esta ADR amenda a tabela §"Allowlist inicial". Após aprovação, bumpar `Versão` em ADR-0002 para v3 e adicionar linha na tabela (conforme processo descrito na própria ADR-0002).
- **`.claude/allowed-mcps.txt`:** adicionar linha `plugin:postgres:postgres` (nome exato depende do naming do plugin instalado — PM confirma em tempo de install).
- **`.claude/settings.json`:** registrar MCP + connection string via variável de ambiente (nunca literal) seguindo procedimento de relock (§9 do CLAUDE.md).
- **Sub-agents `.claude/agents/data-modeler.md`, `security-reviewer.md`:** adicionar seção "Uso de Postgres MCP" com regras de output (schema-first, não dumpar PII).
- **Skill `/mcp-check`:** nenhuma mudança de código — já lê `allowed-mcps.txt` dinamicamente.
- **Hooks:** nenhum hook novo. O existente `session-start.sh` já chama `mcp-check` quando configurado.

---

## Plano de adoção

Após aceitação do PM:

1. **Em terminal externo (PM):**
   - Instalar MCP: `claude mcp add postgres @modelcontextprotocol/server-postgres` (ou equivalente via `claude mcp add-json`).
   - Configurar connection string via env var (não hardcoded).
   - Testar: `claude mcp list` mostra `postgres` ativo.

2. **Atualizar harness (PM, ainda em terminal externo):**
   - Editar `.claude/allowed-mcps.txt` adicionando a entrada.
   - Bumpar `Versão` de `docs/adr/0002-mcp-policy.md` de 2 → 3 + adicionar linha à tabela citando ADR-0013.
   - `KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh` (se settings.json foi tocado).
   - Commit: `chore(harness): adiciona MCP postgres (ref: ADR-0013)`.

3. **Validar em sessão Claude Code:**
   - Rodar `/mcp-check` — deve retornar limpo.
   - Rodar `/sealed-diff` — deve retornar SELOS OK.
   - Pequeno smoke test: sub-agent `data-modeler` lista schemas via MCP.

4. **Reavaliar Figma MCP:**
   - Após 1º slice de UI de E03 rodar sem Figma MCP, PM decide se a dor justifica nova ADR.

---

## Referências

- `ADR-0002` — política de MCPs (esta ADR amenda).
- `CLAUDE.md` §9 — procedimento de alteração de `.claude/settings.json`.
- `docs/constitution.md` §4 R10 — stack só via ADR.
- `.claude/skills/mcp-check.md` — validação periódica.
- `.claude/skills/sealed-diff.md` — check sob demanda pós-relock.
- MCP oficial: https://github.com/modelcontextprotocol/servers (pacote `postgres`).

---

## Checklist de aceitação (revisor)

- [x] Pelo menos 2 opções reais consideradas (A, B, C).
- [x] Decisão justificada sem "porque sim" — amarrada a ADR-0002 + política de menor superfície.
- [x] Reversibilidade declarada (fácil).
- [x] Consequências negativas listadas (vazamento PII, dependência externa).
- [x] Não contradiz ADR anterior — amenda ADR-0002 seguindo o processo documentado nele.
- [x] Impacto em hooks/agents/constitution endereçado (sub-agents data-modeler e security-reviewer precisam de seção nova; nenhum hook novo).
