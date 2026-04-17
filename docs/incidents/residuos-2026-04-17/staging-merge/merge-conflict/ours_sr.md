# Schemas JSON do Kalibrium V2

Este documento esclarece as **duas famílias de schemas JSON** que coexistem no projeto e define, sem ambiguidade, qual diretório canônico abriga cada uma. Ele existe para eliminar drift de referência entre o protocolo operacional v1.2.2 e as skills que persistem estado (`checkpoint`, `resume`).

**Versão:** 1.0.0 — 2026-04-16
**Fonte normativa superior:** `docs/protocol/00-protocolo-operacional.md` e `docs/protocol/03-contrato-artefatos.md`.

---

## 1. Famílias de schemas

### Família A — Schemas de gate (canônicos em `docs/protocol/schemas/`)

Aplicam-se aos artefatos emitidos pelos **gates do pipeline** (15 gates do protocolo v1.2.2) e pelas skills de governança que seguem contrato mecânico. Todo gate que o protocolo enumera como `approved` | `rejected` | `needs_changes` valida sua saída JSON contra um destes arquivos.

| Arquivo | Escopo de aplicação | Consumidores |
|---|---|---|
| `gate-output.schema.json` | Todos os **15 gates** do pipeline v1.2.2: verify, code-review, security-review, audit-tests, functional-review, data-review, observability-review, integration-review, ux-review, master-audit, audit-spec, audit-story, audit-planning, plan-review, ci-gate | `verify-slice`, `review-pr`, `security-review`, `test-audit`, `functional-review`, `data-review`, `observability-review`, `integration-review`, `ux-review`, `master-audit`, `audit-spec`, `audit-story`, `audit-planning`, `review-plan`, `ci-gate` |
| `harness-audit-v1.schema.json` | Skills de **governança do harness** (varredura, integridade, auditoria de fábrica) que não são gates do pipeline de slice mas precisam do mesmo rigor de contrato | `forbidden-files-scan`, `mcp-check`, `guide-audit`, `harness-learner` |

Regras invioláveis da Família A:

1. Todo output de gate **DEVE** validar contra o schema listado antes de ser aceito pelo orquestrador (R4 — verifier emite JSON validado, não prosa).
2. O diretório canônico é **`docs/protocol/schemas/`**. Referências a gates a partir de qualquer skill, agent ou documento do protocolo **DEVEM** apontar para este caminho.
3. Alterações nestes schemas seguem o fluxo de ADR + relock do harness (ver `CLAUDE.md §9`), nunca edição direta sem governança.

### Família B — Schemas não-de-gate (em `docs/schemas/`)

Aplicam-se a artefatos de **estado persistente do projeto** que não são gates, não emitem `verdict`, e não participam do pipeline de aprovação de slice. São schemas utilitários para serialização/validação de estruturas de dados operacionais.

| Arquivo | Escopo de aplicação | Consumidores |
|---|---|---|
| `project-state.schema.json` | Schema do arquivo `project-state.json` (estado vivo do projeto: épicos, stories, slices, status de merge, bloqueios R6/R13/R14, versão do harness) | `checkpoint` (escrita), `resume` (leitura/validação), `project-status` (leitura), `codex-bootstrap` (leitura) |

Regras invioláveis da Família B:

1. O diretório canônico é **`docs/schemas/`** (raiz de documentação, **fora** de `docs/protocol/`). A separação é intencional: o protocolo v1.2.2 governa gates; o estado do projeto é dado operacional da sessão.
2. `project-state.schema.json` **NÃO** é um gate: não possui campo `verdict`, não bloqueia merge, não entra na cascata S1-S3, não participa do pipeline `orchestrator.md`.
3. As referências a `docs/schemas/project-state.schema.json` em `.claude/skills/checkpoint.md` (L41, L82, L106) e `.claude/skills/resume.md` (L129) **SÃO LEGÍTIMAS** e **NÃO** devem ser migradas para `docs/protocol/schemas/`. Tentativas de migração quebrariam a separação de responsabilidades entre protocolo e estado.

---

## 2. Diretriz para auditorias futuras

Para evitar que re-auditorias reabrem o ticket S-3 ("referência a schema fora do diretório canônico"):

1. **Antes de marcar finding S-3 contra um schema,** verificar se o schema pertence à Família A (de gate) ou à Família B (de estado). Apenas schemas da Família A **precisam** estar em `docs/protocol/schemas/`.
2. Referências da Família B a `docs/schemas/` são **corretas por design**; marcar finding contra elas é falso-positivo.
3. Este README é a fonte de verdade para a classificação. Se um novo schema for criado, atualizar esta tabela antes do commit que o introduz.

---

## 3. Inventário canônico (snapshot 2026-04-16)

```
docs/
├── protocol/
│   └── schemas/                                # Família A — schemas de gate
│       ├── README.md                           # este documento
│       ├── gate-output.schema.json             # 15 gates do pipeline v1.2.2
│       └── harness-audit-v1.schema.json        # skills de governança do harness
└── schemas/                                    # Família B — schemas não-de-gate
    └── project-state.schema.json               # estado persistente do projeto
```

Qualquer divergência entre este inventário e o estado real do repositório é bug de governança e deve ser corrigida no próximo ciclo de auditoria.

---

## 4. Referências cruzadas

- `docs/protocol/00-protocolo-operacional.md` §3.1 — mapa canônico de agentes e gates
- `docs/protocol/03-contrato-artefatos.md` — contrato por modo/artefato (aplica Família A)
- `docs/protocol/04-criterios-gate.md` — critérios objetivos dos 15 gates
- `.claude/skills/checkpoint.md` — consumidor legítimo da Família B
- `.claude/skills/resume.md` — consumidor legítimo da Família B
- `CLAUDE.md §9` — procedimento de alteração de arquivos selados (aplica-se a Família A via relock-harness.sh)
