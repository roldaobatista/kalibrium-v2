# ADR-0002 — Política de MCP servers autorizados

**Status:** accepted
**Data:** 2026-04-10
**Autor:** humano + Co-Authored-By Claude Opus 4.6

---

## Contexto

MCP (Model Context Protocol) servers injetam ferramentas, prompts e recursos no contexto do Claude Code. Um MCP malicioso ou mal-configurado pode:

- Executar comandos no ambiente sem aparecer nos hooks locais (depende do tipo).
- Injetar instruções via `server_info`/system prompts.
- Expor dados de um projeto para outro (stateful servers).
- Aumentar drasticamente o consumo de tokens sem aparecer em `.claude/telemetry/`.

No V1, o ambiente acumulou MCPs sem auditoria — contaminação silenciosa do harness. Para o V2, precisamos de:

1. Lista explícita dos MCPs permitidos (allowlist).
2. Justificativa por entrada.
3. Processo claro de adição/remoção.
4. Validação periódica (skill `/mcp-check`).

Esta decisão é complementar a **R1** (proíbe fontes de instrução fora do whitelist de arquivos) — MCPs são o vetor equivalente para injeção via servidor, e merecem o mesmo tratamento.

---

## Opções consideradas

### Opção A: Sem política (deixar o usuário escolher)
- **Descrição:** qualquer MCP pode ser adicionado livremente.
- **Prós:** zero fricção, flexibilidade máxima.
- **Contras:** exatamente o que deu errado no V1. Impossível auditar. Cada nova máquina vira uma configuração diferente. Drift silencioso garantido.
- **Custo de reverter:** alto (quando se descobre o problema, o drift já aconteceu).

### Opção B: Allowlist rígida com zero exceções
- **Descrição:** só MCPs pré-aprovados rodam. Qualquer outro detectado = `session-start` falha.
- **Prós:** previsível, auditável, alinhado com R1.
- **Contras:** fricção quando um MCP novo é genuinamente útil (precisa de PR + ADR amendment).
- **Custo de reverter:** baixo (só atualizar allowlist).

### Opção C: Allowlist com "modo experimental"
- **Descrição:** allowlist padrão + bucket "experimental" que permite MCPs marcados por 7 dias, então vira erro.
- **Prós:** balanço entre rigor e experimentação.
- **Contras:** complexidade de estado (quem marcou? quando? quem expira?). Estado efêmero vira outro vetor de drift.
- **Custo de reverter:** médio.

---

## Decisão

**Escolhida:** Opção B — allowlist rígida.

**Razão:** a hipótese central do V2 é **enforcement por arquitetura, não por prompt**. Uma allowlist com exceções temporárias reintroduz a classe de erros do V1 (drift silencioso, estado que ninguém lembra de limpar). O custo de fricção (abrir ADR para adicionar MCP) é aceitável porque a frequência é baixa (poucos MCPs por ano) e o ato de abrir ADR força documentação do "porquê", que é exatamente o que faltou no V1.

**Reversibilidade:** fácil (trocar modelo exige apenas novo ADR).

---

## Consequências

### Positivas
- Ambiente reprodutível entre máquinas e sessões.
- Qualquer MCP novo entra com justificativa auditável.
- Skill `/mcp-check` tem critério claro para alertar.
- `guide-auditor` pode incluir check de MCPs ativos.

### Negativas
- Fricção para experimentar MCPs novos (ADR + commit).
- Necessidade de manter a allowlist atualizada em `.claude/allowed-mcps.txt`.

### Riscos
- Se `/mcp-check` não for rodado periodicamente, MCPs podem ser adicionados silenciosamente via UI do Claude Code sem que ninguém perceba. **Mitigação:** incluir `/mcp-check` no check-list do `guide-auditor` semanal + considerar hook `SessionStart` futuro que liste MCPs ativos.

### Impacto em outros artefatos
- Hooks afetados: `session-start.sh` pode futuramente chamar `/mcp-check`.
- Sub-agents afetados: nenhum diretamente.
- Skills: `/mcp-check` já existe e se apoia nesta ADR.
- Arquivos: `.claude/allowed-mcps.txt` é o estado operacional.

---

## Allowlist inicial

Registrada em `.claude/allowed-mcps.txt`:

| MCP | Justificativa |
|---|---|
| `plugin:context-mode:context-mode` | Compressão de contexto para outputs grandes (logs, arquivos) — reduz consumo de tokens e mantém raw data fora do contexto. Alinhado com R8. |
| `plugin:context7:context7` | Documentação oficial de libs/frameworks em tempo real — evita que o agente invente APIs. Alinhado com "explorar antes de responder" do CLAUDE.md global. |
| `plugin:github:github` | Operações de issue/PR/review no GitHub — único canal oficial para abrir PRs do V2. |

Adições futuras a este ADR devem:
1. Adicionar linha à tabela com justificativa.
2. Atualizar `.claude/allowed-mcps.txt`.
3. Incrementar o contador `versão` no topo deste ADR.
4. Commit `chore(harness): adiciona MCP <nome> (ref: ADR-0002 v<N>)`.

---

## Processo de remoção

- MCP que reportou falha grave ou injeção de instrução → remoção imediata + incidente em `docs/incidents/`.
- MCP não-utilizado por 60 dias → candidato a remoção via retrospectiva.
- Em todos os casos: atualizar este ADR com `superseded by` ou entrada de histórico.

---

## Referências

- `CLAUDE.md` §3 (R1)
- `docs/constitution.md` §4 R1
- `.claude/skills/mcp-check.md`
- `.claude/allowed-mcps.txt`
