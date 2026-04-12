# Auditoria da Fábrica de Software — 2026-04-11

## Resumo Executivo

A fábrica possui **14 agentes**, **32 skills**, **17 hooks** e **7 schemas**. A arquitetura está bem desenhada com isolamento forte (worktrees), gates independentes e separação clara de responsabilidades. Porém há gaps que impedem operação fluida como fábrica real.

**Veredicto: 75% pronto.** Estrutura sólida, falta robustez operacional.

---

## 1. Mapa da Fábrica (Estado Atual)

```
                    ┌──────────────────┐
                    │   ORQUESTRADOR   │
                    │   (Claude Code)  │
                    └────────┬─────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                     │
   ┌────▼────┐         ┌────▼────┐          ┌─────▼─────┐
   │DESCOBERTA│         │PLANEJA- │          │ EXECUÇÃO  │
   │         │         │ MENTO   │          │           │
   ├─────────┤         ├─────────┤          ├───────────┤
   │domain-  │         │epic-    │          │implementer│
   │analyst  │         │decomp.  │          │(80k tok)  │
   │(30k)    │         │(30k)    │          │           │
   ├─────────┤         ├─────────┤          ├───────────┤
   │nfr-     │         │story-   │          │fixer      │
   │analyst  │         │decomp.  │          │(60k tok)  │
   │(25k)    │         │(30k)    │          │           │
   └─────────┘         ├─────────┤          └───────────┘
                        │architect│
                        │(30k)    │
                        ├─────────┤
                        │ac-to-   │
                        │test(40k)│
                        └─────────┘

   ┌─────────────────────────────────────────────────┐
   │          PIPELINE DE GATES (5 independentes)     │
   ├──────────┬──────────┬──────────┬────────┬───────┤
   │verifier  │reviewer  │security- │test-   │funct- │
   │(25k)     │(30k)     │reviewer  │auditor │ional- │
   │mecânico  │estrutural│(25k)     │(25k)   │review │
   │          │          │OWASP     │cobert. │(25k)  │
   │worktree  │worktree  │worktree  │worktree│worktree│
   │isolada   │isolada   │isolada   │isolada │isolada│
   └──────────┴──────────┴──────────┴────────┴───────┘

   ┌──────────────┐
   │ GOVERNANÇA   │
   │guide-auditor │
   │(haiku, 15k)  │
   └──────────────┘
```

---

## 2. O Que Está Funcionando Bem

| Aspecto | Status | Detalhe |
|---------|--------|---------|
| Isolamento de gates | ✅ Forte | 5 reviewers em worktrees separadas, nenhum vê output do outro |
| Separação implementa/aprova | ✅ Forte | Implementer não tem acesso a review; reviewer não edita |
| Integridade do harness | ✅ Forte | MANIFEST.sha256 + settings-lock + hooks-lock + 4 camadas de relock |
| Fluxo de fases | ✅ Forte | Descoberta → Planejamento → Execução → Gates → Merge |
| Contratos de I/O | ✅ Forte | Cada agente tem inputs permitidos/proibidos explícitos |
| Budgets de tokens | ✅ Forte | Cada agente tem budget declarado, guide-auditor monitora |
| Hooks de segurança | ✅ Forte | 17 hooks cobrindo secrets, scope, bypass, sealed files |
| Templates e schemas | ✅ Forte | 12 templates + 7 JSON schemas em docs/ |
| Compliance/LGPD | ✅ Forte | 10 políticas documentadas |

---

## 3. Gaps Críticos (P0 — Bloqueiam operação)

### G-01: Falta orquestrador explícito
**Problema:** O orquestrador é o Claude Code "principal" sem definição formal. Não há `orchestrator.md` com regras de sequenciamento, decisão de paralelismo, ou gestão de estado entre agentes.

**Impacto:** Cada sessão depende do agente lembrar o fluxo. Se o contexto comprimir, perde-se a sequência.

**Solução:** Criar `.claude/agents/orchestrator.md` com:
- Máquina de estados do projeto (fases A→F)
- Regras de quando paralelizar agentes (ex: domain-analyst + nfr-analyst)
- Regras de quando serializar (ex: verifier antes de reviewer)
- Protocolo de checkpoint automático

### G-02: Skills sem validação de entrada (16/32)
**Problema:** 50% das skills não documentam pré-condições. Ex: `/decompose-epics` não verifica se PRD existe.

**Impacto:** Falha silenciosa ou resultado incorreto quando pré-condição não é atendida.

**Solução:** Adicionar seção `## Pré-condições` em cada skill com checagem explícita.

### G-03: Skills sem tratamento de erro (24/32)
**Problema:** 75% das skills não documentam cenários de erro ou recuperação.

**Impacto:** Agente fica "perdido" quando algo falha, sem saber como recuperar.

**Solução:** Adicionar seção `## Erros e Recuperação` em cada skill.

### G-04: TECHNICAL-DECISIONS.md é esqueleto (5% completo)
**Problema:** Declarado como leitura obrigatória em §0 do CLAUDE.md, mas contém ~991 bytes sem índice real de ADRs.

**Impacto:** Agentes leem arquivo vazio, perdem contexto de decisões técnicas.

**Solução:** Popular com tabela de ADRs existentes (0001, 0002) e manter atualizado.

### G-05: Cadeia de rejeição/correção ambígua
**Problema:** Quando fixer corrige um finding, quem re-invoca o gate? Não está definido.

**Impacto:** Ciclo de correção pode travar ou pular re-verificação.

**Solução:** Definir no orchestrator: fixer → re-run do gate específico → se aprovado, próximo gate.

---

## 4. Gaps Importantes (P1 — Degradam qualidade)

### G-06: Diretórios órfãos
- `.claude/schemas/` — vazio (schemas estão em `docs/schemas/`)
- `.claude/templates/` — vazio (templates estão em `docs/templates/`)
- `specs/` — vazio (nunca criado)

**Solução:** Remover referências aos diretórios vazios ou criar symlinks.

### G-07: post-edit-gate.sh ainda é draft
**Problema:** O hook mais importante (format→lint→type→test após cada edit) está em `scripts/drafts/`, não ativo.

**Impacto:** Sem stack definida (ADR-0001 existe mas projeto não foi inicializado), o hook não pode rodar. Quando código começar, precisará ativar.

**Solução:** Documentar como ativar pós-`composer install` / `npm install`.

### G-08: Dependência circular na descoberta
**Problema:** domain-analyst pode ler `docs/product/nfr.md`, mas nfr-analyst é quem escreve esse arquivo. Se ambos rodam em paralelo, resultado indefinido.

**Solução:** Definir ordem: domain-analyst primeiro → nfr-analyst segundo (pode ler glossário do domain-analyst).

### G-09: Falta skill de gestão de contexto
**Problema:** O CLAUDE.md descreve que "quando contexto ficar grande, gerar checkpoint e pedir nova sessão", mas não há automação para isso.

**Solução:** Criar hook ou skill `/context-check` que monitora tokens consumidos e sugere checkpoint proativamente.

### G-10: Falta documentar qual agente cada skill spawna
**Problema:** 32/32 skills não têm seção `## Agentes` declarando qual sub-agent é invocado.

**Solução:** Adicionar metadado estruturado em cada skill.

---

## 5. Gaps Menores (P2 — Melhorias)

| ID | Gap | Solução |
|----|-----|---------|
| G-11 | user-prompt-submit.sh é placeholder (374B) | Expandir sanitização |
| G-12 | collect-telemetry.sh ignora falhas silenciosamente | Adicionar log de erro |
| G-13 | sealed-files-bash-lock.sh não cobre `tee >>`, `xargs` | Expandir patterns |
| G-14 | pre-commit-gate verifica existência de teste mas não cobertura | Integrar com test-auditor |
| G-15 | Sem rate-limiting em hooks pesados | Adicionar cache/throttle |
| G-16 | guide-auditor não tem schema de output definido | Criar guide-audit.schema.json |
| G-17 | Falta skill `/release-readiness` (mencionada mas não verificada) | Criar se não existir |
| G-18 | Falta versionamento de specs de agentes | Adicionar changelog por agente |

---

## 6. Comparação: Visão Ideal vs. Estado Atual

| Requisito da Fábrica | Estado | Gap |
|----------------------|--------|-----|
| Orquestrador mestre que coordena | ⚠️ Implícito | G-01: Sem definição formal |
| Descoberta com entrevista guiada | ✅ `/intake` + 10 perguntas | — |
| Perguntas estratégicas (hosting, escala, auth) | ✅ No `/intake` | — |
| Planejamento completo antes do código | ✅ Fases A→C bloqueiam código | — |
| PRD + NFR + arquitetura + deploy + dados + segurança | ✅ Documentado | — |
| Quebra em épicos → stories → tasks | ✅ 3 agentes especializados | — |
| Story com contrato claro (ACs, riscos, deps) | ✅ Template story-contract | — |
| Agente implementa ≠ agente aprova | ✅ Separação total | — |
| Revisão funcional independente | ✅ functional-reviewer | — |
| Revisão de arquitetura/código | ✅ reviewer | — |
| Validação de testes | ✅ test-auditor | — |
| Verificação de segurança | ✅ security-reviewer | — |
| Revisão de documentação/escrita | ⚠️ Parcial | Falta doc-reviewer |
| Correção reabre ciclo de revisão | ⚠️ Parcial | G-05: Cadeia ambígua |
| Gates rígidos (lint, build, typecheck) | ⚠️ Parcial | G-07: post-edit-gate é draft |
| Testes unitários, integração, E2E | ✅ Definido no fluxo | — |
| Aderência arquitetural | ✅ reviewer + ADRs | — |
| Validação de ACs | ✅ ac-to-test + functional-reviewer | — |
| Prontidão operacional | ⚠️ Parcial | G-17: /release-readiness |
| Memória não depende da conversa | ✅ project-state.json + checkpoint | — |
| Checkpoint + resume | ✅ `/checkpoint` + `/resume` | — |
| /HELP ou /RESUME mostra estado | ✅ `/start` + `/where-am-i` | — |

---

## 7. Plano de Ação Recomendado

### Bloco 3A — Fechar P0s (antes de qualquer código)

| # | Ação | Esforço |
|---|------|---------|
| 1 | Criar `orchestrator.md` com máquina de estados | Médio |
| 2 | Popular `TECHNICAL-DECISIONS.md` com índice de ADRs | Baixo |
| 3 | Definir cadeia fixer → re-gate no orchestrator | Baixo |
| 4 | Adicionar pré-condições em 16 skills sem validação | Médio |
| 5 | Adicionar tratamento de erro em 24 skills | Médio |

### Bloco 3B — Fechar P1s (antes do primeiro slice)

| # | Ação | Esforço |
|---|------|---------|
| 6 | Resolver diretórios órfãos | Baixo |
| 7 | Documentar ativação do post-edit-gate | Baixo |
| 8 | Definir ordem domain-analyst → nfr-analyst | Baixo |
| 9 | Criar skill `/context-check` | Médio |
| 10 | Documentar agentes em cada skill | Baixo |

### Bloco 3C — Melhorias (durante execução)

| # | Ação | Esforço |
|---|------|---------|
| 11-18 | Gaps P2 (G-11 a G-18) | Incremental |

---

## 8. Métricas de Saúde da Fábrica

| Métrica | Valor |
|---------|-------|
| Agentes definidos | 14/14 ✅ |
| Agentes com I/O formal | 14/14 ✅ |
| Agentes com budget | 14/14 ✅ |
| Agentes com isolamento | 14/14 ✅ |
| Skills existentes | 32/32 ✅ |
| Skills com pré-condições | 16/32 ⚠️ (50%) |
| Skills com erro handling | 8/32 ❌ (25%) |
| Hooks ativos | 17/17 ✅ |
| Hooks com MANIFEST | 17/17 ✅ |
| Schemas JSON | 7/7 ✅ |
| Templates | 12/12 ✅ |
| Docs de compliance | 10/10 ✅ |
| ADRs reais | 2 ✅ |
| Orquestrador formal | 0/1 ❌ |
