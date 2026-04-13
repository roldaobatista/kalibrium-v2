# Executability Gap Action Plan — Bloco 10

**Status:** 📝 rascunho — aguardando decisão PM
**Data de criação:** 2026-04-11
**Origem:** pergunta do PM 2026-04-11 — *"nesse ambiente tem o risco do sistema ser construído e não conseguirmos executar os fluxos do prd, funções etc?"*
**Restrição crítica reafirmada pelo PM 2026-04-11:** *"não entendo nada de código, posso validar teste de produto no navegador"*. Isso bate exatamente com `CLAUDE.md §3.1` (humano = Product Manager). Qualquer gate neste plano ou é 100% mecânico (hook/CI/sub-agent) ou é browser walkthrough pelo PM. Nada entre os dois.

---

## 1. O problema identificado

O harness atual (Blocos 0-9) garante:

- ✅ Testes rodam de verdade (Bloco 3)
- ✅ ACs têm testes mapeados (3.5)
- ✅ Dual-verifier independente (R11)
- ✅ Tradutor R12 para linguagem PM
- ✅ Eval suite do próprio harness (8.2)
- ✅ CI externo como juiz (Bloco 5)

Mas **não garante** nenhum dos pontos abaixo:

1. O produto **alguma vez boota** de ponta a ponta.
2. Fluxos do PRD **atravessam o sistema integrado** (slice A + slice B + slice C num fluxo real).
3. O PM **alguma vez toca o produto** em tela.
4. Slices compõem — cada um pode ter passado no seu sandbox isolado e nunca terem sido rodados juntos.
5. A UI **bate com o que o PM imagina**.

### Evidência estrutural do gap

- `docs/constitution.md §3` DoD mecânica tem 10 itens, **zero** mencionam "produto boota" ou "PM confirmou fluxo".
- `CLAUDE.md §6` fluxo padrão do slice, passo 14: *"PR → CI full → revisão humana → merge"*. Mas `CLAUDE.md §3.1` diz que o humano **não faz review técnica de código**. Contradição: o que exatamente a "revisão humana" do passo 14 valida, se não é código?
- Nenhum sub-agent listado em `CLAUDE.md §8` executa o produto bootado. `verifier` opera em worktree isolada, sem app rodando.
- `verification.json` (R4) valida `ac_checks` e `violations` estruturais. Não há campo `e2e_flow_status` ou `pm_walkthrough_status`.
- Eval suite do Bloco 8.2 tem 5 golden tasks do harness ("fix bug simples", "CRUD pequena") — mas são tarefas **para testar o harness**, não os fluxos reais do Kalibrium (emissão de certificado, upload de planilha de medição, exportação fiscal, etc.).

### Consequência concreta

Harness pode fechar o go/no-go do Dia 1 (Bloco 7) com:
- Todos os hooks verdes
- Smoke-test-hooks 75/75
- Eval suite passando
- 0 findings no guide-check
- Dual-verifier aprovou os primeiros 5 slices
- CI externo aprovou 5/5 PRs

E **nenhuma pessoa ter alguma vez bootado o Kalibrium e clicado numa tela.** Os primeiros 20 slices podem estar no main sem nunca terem sido integrados num boot real.

Isso é exatamente o risco que o PM levantou.

---

## 2. Princípio operacional deste bloco

> **"Se o PM não pode clicar, não está pronto."**

Toda feature mergeada em `main` passa por duas portas, não uma:

1. **Porta mecânica** (Blocos 2-7 do plano principal): código compila, testes verdes, lint limpo, verifier aprovado, reviewer aprovado.
2. **Porta de executabilidade** (este bloco): produto boota em preview env, fluxo PRD afetado roda verde em Playwright, PM clicou na sequência e marcou `APROVO` no walkthrough file.

**As duas portas são obrigatórias.** Nenhuma substitui a outra. Quem passa só na porta mecânica não está pronto — é o gap atual. Quem passa só na porta de executabilidade é anedótico — pode ter bug oculto.

Este princípio entra como **P10** na constituição (ver §6 deste plano).

---

## 3. Os 5 itens do Bloco 10

### 10.1 — Walking skeleton obrigatório no Slice 1

**Gap fechado:** nada hoje força que o Slice 1 de um sistema novo boote a aplicação com pelo menos 1 rota acessível e 1 teste Playwright cruzando o boot. Sem isso, acumulam-se N slices de backend/lógica que nunca rodaram juntos.

**SELADO?** Sim. Cria hook novo `block-slice-without-skeleton.sh` em `scripts/hooks/` e adiciona ao MANIFEST. Requer `relock-harness.sh` em terminal externo pelo PM.

**Dependência:** Bloco 2 (stack decidida via ADR-0001) + ADR-0003 (tool de e2e — Playwright é candidato padrão porque o MCP já está disponível nativamente no ambiente do agente).

**Tarefas:**

- [ ] 10.1.1 `docs/adr/0003-e2e-testing-tool.md` — recomenda Playwright como default. Alternativas consideradas: Cypress, WebdriverIO. Critério decisivo: MCP nativo no Claude Code (não precisa setup), cross-browser, gera screenshots + traces, integra com CI sem overhead.
- [ ] 10.1.2 Template `templates/slice-001-skeleton/spec.md` com 3 ACs obrigatórios:
  - AC-S1: app boota via comando único definido em `docs/stack.json` (ex.: `pnpm dev`, `bun run dev`, `python manage.py runserver`)
  - AC-S2: rota `/` retorna HTTP 200 com HTML válido e título contendo "Kalibrium"
  - AC-S3: teste Playwright navega até `/`, captura screenshot, assert que elemento-chave (ex.: logo ou menu) está visível
- [ ] 10.1.3 `scripts/hooks/block-slice-without-skeleton.sh` — PreToolUse de `/new-slice`:
  - Se `docs/walking-skeleton-status.json` não existir OU `ready != true` → rejeita qualquer `/new-slice NNN` onde `NNN >= 2`.
  - Mensagem ao agente: "Slice 1 (walking skeleton) precisa estar `ready: true` antes de criar outros slices. Rode `scripts/skeleton-gate.sh --check` para validar."
- [ ] 10.1.4 `scripts/skeleton-gate.sh --check` — script sem-selo que:
  - Lê `docs/stack.json` para pegar `dev_cmd`.
  - Boota o app em background (timeout 60s).
  - `curl -f http://localhost:$PORT/` → exige 200.
  - Roda `npx playwright test tests/e2e/skeleton.spec.ts` → exige green.
  - Se tudo passa, escreve `docs/walking-skeleton-status.json` com `ready: true` + timestamp + hash dos arquivos-chave.
- [ ] 10.1.5 Adicionar novo item ao DoD mecânica (`docs/constitution.md §3`):
  - `[ ] Walking skeleton ainda operante (scripts/skeleton-gate.sh --check → green)`
- [ ] 10.1.6 Smoke test: tentar `/new-slice 002` em repo sem skeleton → deve bloquear. Montar skeleton → `/new-slice 002` libera. Registrar em `docs/reports/skeleton-gate-smoke-YYYY-MM-DD.md`.

**Gate de pronto:** smoke test 10.1.6 executado e registrado.

**Estimativa:** 6 unidades.

---

### 10.2 — Gate de fluxo-PRD ponta-a-ponta

**Gap fechado:** ACs são atômicos (`AC-012 = técnico consegue criar instrumento`). PRD tem **fluxos** que atravessam múltiplos ACs (`FLOW-001 = emitir certificado de calibração = login → criar instrumento → inserir medições → gerar PDF → enviar por email`). Hoje, todos os ACs podem estar verdes em isolamento e o fluxo integrado quebrado (ex.: middleware de auth quebra sessão entre AC-012 e AC-015).

**SELADO?** Parcial. Item 10.2.4 modifica `verify-slice.sh` que hoje não está na lista de selados mas é script crítico (confirmar na auditoria 9.1 do Bloco 9 se ele deveria estar). Itens 10.2.1–10.2.3 são docs + scripts auxiliares.

**Dependência:** Bloco 2 (stack) + Bloco 3 (testes reais rodando) + 10.1 (skeleton boota).

**Tarefas:**

- [ ] 10.2.1 Schema `docs/schemas/prd-flows.schema.json`:
  ```json
  {
    "flow_id": "FLOW-001",
    "title_pm": "Emitir certificado de calibração",
    "description_pm": "Descrição em PT-BR do fluxo como um usuário real faria (R12).",
    "spans_acs": ["AC-012", "AC-015", "AC-023", "AC-031"],
    "spans_slices": ["slice-003", "slice-005", "slice-007"],
    "e2e_test": "tests/e2e/flow-001-emissao-certificado.spec.ts",
    "screenshot_routes": ["/login", "/instrumentos/novo", "/certificados/NNN", "/download"],
    "last_green_run": "2026-04-15T10:30:00Z",
    "last_green_trace": "playwright-traces/flow-001-2026-04-15.zip",
    "walkthrough_required": true
  }
  ```
- [ ] 10.2.2 Quando o PM aceita um PRD (primeiro ADR de produto), o agente gera `docs/prd-flows.json` traduzindo os fluxos descritos no PRD em objetos conforme schema. R12 aplicado — descrição em PT-BR, não em termos técnicos.
- [ ] 10.2.3 `scripts/validate-prd-flow-coverage.sh`:
  - Lê `docs/prd-flows.json`.
  - Para cada flow, confirma que o `e2e_test` apontado existe.
  - Para cada AC em `spans_acs`, confirma que o AC está em `ac-list.json` do slice correspondente.
  - Para cada slice que toca um AC listado em algum flow, confirma que o flow tem e2e verde registrado em `last_green_run` dentro da janela aceitável (default: últimos 7 dias, ou desde o último push que tocou arquivos de `spans_slices`).
  - Violação = exit 1 com lista de gaps.
- [ ] 10.2.4 `verify-slice.sh`: ao verificar slice NNN, identificar todos os flows em `prd-flows.json` cujo `spans_slices` inclui `slice-NNN`, rodar Playwright para cada, atualizar `last_green_run` se verde, rejeitar o slice se algum flow estiver vermelho. **Este item modifica `verify-slice.sh` e vai precisar de relock externo.**
- [ ] 10.2.5 Adicionar novo item ao DoD mecânica (`docs/constitution.md §3`):
  - `[ ] Todos os fluxos PRD que atravessam este slice estão verdes (validate-prd-flow-coverage.sh)`
- [ ] 10.2.6 Smoke test: criar flow fictício FLOW-TEST atravessando 2 slices. Quebrar intencionalmente middleware entre os 2. Rodar `/verify-slice` no segundo slice → deve reprovar por flow failure, mesmo com ACs unitários verdes. Corrigir middleware → passar. Registrar em `docs/reports/flow-gate-smoke-YYYY-MM-DD.md`.

**Gate de pronto:** smoke test 10.2.6 executado e registrado.

**Estimativa:** 8 unidades.

---

### 10.3 — PM Browser Walkthrough Gate (peça central do bloco)

**Gap fechado:** `CLAUDE.md §3.1` diz que o PM *"testa o produto visualmente quando houver UI"*, mas **nenhum gate registra essa ação**. É aspiração, não enforcement. R12 traduz o output do verifier para PT-BR, mas output é read-only; o PM pode aprovar sem nunca ter aberto o navegador.

**SELADO?** Sim. Cria hook novo `block-merge-without-walkthrough.sh` em `scripts/hooks/`. Requer relock.

**Dependência:** 10.1 (skeleton boota) + 10.2 (flow existe) + Bloco 4 (explain-slice real) + 10.4 (preview env, opcional mas fortemente recomendado — sem preview o PM teria que bootar localhost).

**Tarefas:**

- [ ] 10.3.1 `explain-slice.sh` (item 4.1 do tracker principal) passa a emitir **duas seções**, não só uma:
  1. **Resumo do que mudou** (R12 atual — traduz verification.json + review.json para PT-BR).
  2. **Passo-a-passo para você testar** — numerado, em PT-BR, sem jargão:
     ```
     ## Como testar este slice no navegador

     1. Abra esta URL: https://slice-007.preview.kalibrium.dev
        (ou, se não houver preview, rode `pnpm dev` e abra http://localhost:3000)
     2. Faça login com: testador@kalibrium.dev / senha123
     3. Clique em "Novo instrumento" no menu lateral
     4. Preencha o nome "Balança Teste", modelo "ABC-123", número de série "SN001"
     5. Clique em "Emitir certificado"
     6. Confirme que um PDF baixou
     7. Abra o PDF — deve mostrar o nome do instrumento e a data de hoje
     8. Se tudo acima funcionou, abra o arquivo `docs/pm-walkthroughs/slice-007-walkthrough-2026-04-11.md`
        e mude a linha `veredito: AGUARDANDO` para `veredito: APROVO`.
     9. Se algo quebrou, mude para `veredito: REPROVO` e descreva o problema na seção "Problemas encontrados".
     ```
- [ ] 10.3.2 Template `templates/pm-walkthrough.md`:
  ```markdown
  ---
  slice_id: slice-NNN
  data_inicio: YYYY-MM-DDTHH:MM:SS
  data_veredito: null
  url_testada: null
  veredito: AGUARDANDO  # APROVO | REPROVO | AGUARDANDO
  assinatura_pm: null   # nome do PM
  ---

  ## Passos do walkthrough

  (copiado do explain-slice output — cada passo vira um checkbox aqui)

  - [ ] Passo 1: ...
  - [ ] Passo 2: ...

  ## Problemas encontrados

  (vazio se veredito = APROVO)

  ## Observações livres do PM

  ```
- [ ] 10.3.3 `scripts/hooks/block-merge-without-walkthrough.sh` — novo hook, PreToolUse de push (e também de `gh pr merge`):
  - Identifica slices tocados pelo diff (via `specs/NNN/` no path do staged).
  - Para cada slice tocado, exige `docs/pm-walkthroughs/slice-NNN-walkthrough-*.md` existente E com frontmatter `veredito: APROVO`.
  - Se faltante ou `veredito != APROVO` → bloqueia push com mensagem PM-friendly (R12).
- [ ] 10.3.4 `scripts/validate-walkthrough.sh`:
  - Valida frontmatter YAML.
  - Confirma que `slice_id` bate com path.
  - Confirma que `data_veredito` é ≤ 48h antes do push (walkthrough velho não conta).
  - Confirma que `veredito` é um dos 3 valores permitidos.
  - Se `veredito == REPROVO`, cria automaticamente `docs/incidents/pm-reproved-slice-NNN-YYYY-MM-DD.md` copiando a seção "Problemas encontrados".
- [ ] 10.3.5 R9 zero bypass — `--no-verify` em push que toca walkthroughs é detectado por `pre-commit-gate.sh` (já existente) e vira incident.
- [ ] 10.3.6 Adicionar novo item ao DoD mecânica (`docs/constitution.md §3`):
  - `[ ] Walkthrough PM em navegador registrado com veredito APROVO em docs/pm-walkthroughs/slice-NNN-walkthrough-*.md`
- [ ] 10.3.7 Adicionar comando em `CLAUDE.md §7`:
  - `/walkthrough NNN` — abre o walkthrough file pronto, roda `explain-slice` com a seção 2, abre o browser na URL do preview env.
- [ ] 10.3.8 Atualizar fluxo padrão em `CLAUDE.md §6` — passo 14 muda de:
  > 14. PR → CI full → revisão humana → merge.

  para:
  > 14. PR → CI full → preview env deploya → `/walkthrough NNN` no navegador → PM marca `APROVO` no walkthrough file → merge.
- [ ] 10.3.9 Smoke test: tentar push com walkthrough ausente → bloqueia. Criar walkthrough com `AGUARDANDO` → bloqueia. Marcar `APROVO` → libera. Marcar `REPROVO` → bloqueia e cria incident automaticamente. Registrar em `docs/reports/walkthrough-gate-smoke-YYYY-MM-DD.md`.

**Gate de pronto:** smoke test 10.3.9 executado e registrado.

**Estimativa:** 5 unidades (mais denso em entrega, menos código — é política + hook simples).

---

### 10.4 — Demo environment sempre ligado

**Gap fechado:** sem preview env, o walkthrough do 10.3 vira *"abra localhost:3000"*, o que envolve comandos no terminal — contradiz §3.1. PM precisa de uma URL clicável.

**SELADO?** Não. GitHub Action + config de hosting. Nenhum hook local.

**Dependência:** Bloco 2 (stack) + Bloco 5 (CI operacional) + ADR de hosting.

**Tarefas:**

- [ ] 10.4.1 `docs/adr/0004-preview-hosting.md` — candidatos: Vercel, Railway, Fly.io, Render. Critérios: (a) dado residente no Brasil ou EUA com DPA adequado (LGPD), (b) deploy < 3 min, (c) URL pública temporária sem auth complicada, (d) custo previsível. Recomendação forte vem com cálculo de custo mensal para 30 slices simultâneos.
- [ ] 10.4.2 `.github/workflows/preview-deploy.yml` — trigger em push para qualquer branch `slice-*`:
  - Build do produto.
  - Deploy para provider escolhido.
  - Comenta no PR: "Preview disponível em https://slice-NNN.kalibrium-preview.{provider}".
  - Atualiza `docs/pm-walkthroughs/slice-NNN-walkthrough-*.md` campo `url_testada` automaticamente.
- [ ] 10.4.3 Preview env expira automaticamente:
  - 7 dias após último push, ou
  - No momento em que o PR é mergeado em main, ou
  - No momento em que o branch é deletado.
- [ ] 10.4.4 `docs/environment-setup.md` (item 8.6 do Bloco 8) incorpora seção "Como acessar preview envs como PM" — zero jargão.
- [ ] 10.4.5 Fallback para quando preview deploy falhar: walkthrough aceita `url_testada: localhost` + instruções de `pnpm dev` em PT-BR step-by-step, mas gera WARN no slice-report ("PM teve que rodar localhost — preview env estava quebrado").
- [ ] 10.4.6 Smoke test: push em branch `slice-preview-test` → workflow roda → URL acessível do navegador externo → PM consegue abrir.

**Gate de pronto:** smoke test 10.4.6 com URL acessada externamente.

**Estimativa:** 5 unidades.

---

### 10.5 — Visual regression + screenshot baseline

**Gap fechado:** walkthrough depende de ação humana. Humano se cansa, distrai, pula passos. Entre um walkthrough e outro, a UI pode regredir silenciosamente (botão sumiu, contraste quebrou, formulário mudou de ordem) sem ninguém notar até o cliente reclamar.

**SELADO?** Parcial. Item 10.5.3 modifica `verify-slice.sh` (mesma observação do 10.2.4).

**Dependência:** 10.1 (Playwright integrado) + 10.3 (walkthrough estabelecido como fonte de aprovação de screenshots).

**Tarefas:**

- [ ] 10.5.1 Toda rota listada em `docs/prd-flows.json §screenshot_routes` (soma de todos os flows) é capturada pelo Playwright em cada run de `/verify-slice`. Screenshots salvos em `.playwright-cache/screenshots/<slice-NNN>/<route-hash>.png`.
- [ ] 10.5.2 Baseline em `tests/e2e/screenshots/baseline/`:
  - Primeiro screenshot verde + walkthrough aprovado (10.3) vira baseline.
  - Baseline é commitado ao repo (arquivos binários PNG pequenos, OK).
- [ ] 10.5.3 `verify-slice.sh` passa a rodar comparação pixel-diff:
  - Para cada rota modificada (identificar via `specs/NNN/affected-routes.json`, gerado pelo architect), permite diff > 5%.
  - Para cada rota **não** modificada pelo slice, diff > 5% é **regressão visual** → reprova o slice com mensagem PM-friendly: "Tela X mudou sem ser parte deste slice — provavelmente regressão acidental. Detalhe: link para HTML diff report."
- [ ] 10.5.4 Atualização de baseline **exige walkthrough explícito** do PM mencionando na seção "Observações livres do PM" o texto `BASELINE_OK: <lista-de-rotas>`. Sem isso, screenshots novos não viram baseline.
- [ ] 10.5.5 `scripts/screenshot-diff-report.sh` gera `docs/pm-walkthroughs/slice-NNN-visual-diff.html` com imagens lado-a-lado: baseline | atual | diff highlight. PM abre, olha, decide no walkthrough.
- [ ] 10.5.6 Hook `sealed-files-bash-lock.sh` já existente garante que agente não pode mexer em `tests/e2e/screenshots/baseline/` sem passar por walkthrough (adicionar a esse diretório na lista de selados via relock).
- [ ] 10.5.7 Smoke test: mudar uma string de header sem declarar em `affected-routes.json` → `/verify-slice` bloqueia com diff visual. PM abre walkthrough, vê que é erro, corrige. Segundo smoke: mudar header declarando em `affected-routes.json` → `/verify-slice` passa. PM aprova → baseline atualizado. Registrar em `docs/reports/visual-regression-smoke-YYYY-MM-DD.md`.

**Gate de pronto:** smoke test 10.5.7 executado e registrado.

**Estimativa:** 7 unidades.

---

## 4. Como os 5 itens compõem o fechamento do risco

| Risco original (pergunta do PM) | Item que fecha | Mecânica |
|---|---|---|
| Produto nunca boota integrado | 10.1 | Hook bloqueia slice 2+ sem skeleton operante |
| Slices verdes isoladamente, integrado quebrado | 10.2 | verify-slice roda flows PRD, não só unit |
| PM nunca toca o produto | 10.3 | Hook bloqueia push sem walkthrough APROVO |
| PM precisa rodar comandos no terminal | 10.4 | Preview env dá URL clicável |
| UI regride sem ninguém notar | 10.5 | verify-slice compara pixels contra baseline |

**Sem todos os 5, o risco persiste.** Cada um fecha uma camada distinta.

10.1 e 10.3 são os dois obrigatórios. Os outros três são "só remove atrito" no sentido de que, se não existirem, o PM ainda pode validar (abrir localhost, clicar manualmente), mas a chance dele efetivamente fazer isso cai rápido. 10.4 e 10.5 são multiplicadores de aderência humana. 10.2 é o que pega as regressões cruzadas entre slices.

---

## 5. Dependências e ordem de execução

```
Bloco 2 (stack — ADR-0001)
   │
   ├──→ ADR-0003 (10.1.1) — E2E tool → decidir Playwright
   ├──→ ADR-0004 (10.4.1) — Preview hosting
   │
   ├──→ 10.1 (walking skeleton) ──┐
   │                               │
   │    Bloco 3 (testes reais)    │
   │         │                     │
   │         └──→ 10.2 (flow gate)│
   │                               │
   │    Bloco 4 (explain-slice)   │
   │         │                     │
   │         └──→ 10.3 (walkthrough) ← PEÇA CENTRAL
   │                               │
   │    Bloco 5 (CI externo)       │
   │         │                     │
   │         └──→ 10.4 (preview env)
   │                               │
   │                               └──→ 10.5 (visual regression)
```

**Ordem recomendada:**

1. Bloco 2 (Stack + ADR-0001) — destrava tudo, já estava no plano principal.
2. ADR-0003 (10.1.1) e ADR-0004 (10.4.1) — podem rodar em paralelo assim que Bloco 2 fecha.
3. 10.1 (walking skeleton) — começa imediatamente após ADR-0003. Bloqueia slices 2+.
4. Bloco 3 (testes reais) — em paralelo com 10.1.
5. 10.2 (flow gate) — após Bloco 3 + 10.1.
6. Bloco 4 (explain-slice real) — em paralelo com 10.2.
7. **10.3 (walkthrough gate)** — após Bloco 4 + 10.1. **Este é o gate que efetivamente bloqueia o risco.**
8. Bloco 5 (CI externo) — em paralelo com 10.3.
9. 10.4 (preview env) — após Bloco 5 + ADR-0004.
10. 10.5 (visual regression) — após 10.3 + 10.4.
11. Bloco 6, 7 (re-auditoria Dia 1) — agora o go/no-go do Dia 1 tem uma pergunta a mais: *"existe ao menos 1 walkthrough com APROVO registrado?"*.

Adiciona ~5 itens ao caminho crítico mas fecha o risco **estruturalmente**, não por boa vontade.

---

## 6. Alterações em artefatos existentes (todas fora dos selados, exceto os hooks novos)

### 6.1 `docs/constitution.md §3` — DoD mecânica

Adicionar 3 itens ao final da lista:

```markdown
- [ ] Walking skeleton operante (scripts/skeleton-gate.sh --check → green)
- [ ] Todos os fluxos PRD afetados por este slice estão verdes (validate-prd-flow-coverage.sh)
- [ ] Walkthrough PM em navegador registrado com veredito APROVO em docs/pm-walkthroughs/slice-NNN-walkthrough-*.md (data ≤ 48h antes do push)
```

Adicionar princípio **P10** em `§2`:

```markdown
### P10. Executabilidade antes de "pronto"
"Pronto" exige que o produto tenha bootado, o fluxo PRD afetado tenha rodado ponta-a-ponta em e2e, e o PM tenha confirmado visualmente no navegador. Código que só passa em sandbox isolado **não é pronto**.
**Enforcement:** `block-slice-without-skeleton.sh`, `block-merge-without-walkthrough.sh`, `validate-prd-flow-coverage.sh`, `verify-slice.sh` rodando Playwright flows.
```

### 6.2 `CLAUDE.md §6` — fluxo padrão do slice

Passo 14 muda de:

> 14. PR → CI full → revisão humana → merge.

para:

> 14. PR → CI full → preview env deploya → `/walkthrough NNN` no navegador → PM marca `APROVO` no walkthrough file → merge.

### 6.3 `CLAUDE.md §7` — comandos

Adicionar linhas:

| Intenção | Comando |
|---|---|
| Validar walking skeleton | `/skeleton-gate` |
| Abrir walkthrough de slice | `/walkthrough NNN` |

### 6.4 `CLAUDE.md §8` — sub-agents

Adicionar:

| Nome | Papel | Budget |
|---|---|---|
| `e2e-driver` | Roda Playwright, captura screenshots, gera traces, produz walkthrough instructions PM-friendly | 25k |

---

## 7. Rejeitados (não resolvem o risco)

- **"Pedir PM para ler `plan.md`"** — viola §3.1 (PM não lê código). O plan.md tem estruturas de pastas, schemas, nomes de funções. Não é PM-friendly.
- **"Treinar PM para ler diff"** — viola §3.1 e a própria afirmação do PM 2026-04-11 ("não entendo nada de código").
- **"Contratar dev só para review"** — já existe item A4 do tracker (advisor técnico externo), mas é review técnico periódico, não por-slice, e não cobre "o produto roda".
- **"Confiar que sub-agent `reviewer` (R11) pega integração"** — reviewer opera em worktree isolada, sem produto bootado. Não pode pegar bug de integração cross-slice. Já temos esse gap, não é novo.
- **"Dashboard que mostra `ac-list.json` verde"** — é o gap atual. Verde em AC ≠ verde em fluxo.
- **"Deploy direto em produção, pular preview"** — viola princípio básico de teste antes de release. Preview env não é opcional.

---

## 8. Esforço total

| Item | Unidades |
|---|---|
| 10.1 walking skeleton | 6 |
| 10.2 flow gate | 8 |
| 10.3 walkthrough gate | 5 |
| 10.4 preview env | 5 |
| 10.5 visual regression | 7 |
| ADR-0003, ADR-0004 | 2 |
| Atualizações em CLAUDE.md + constitution.md | 2 |
| **Total** | **35 unidades** |

**Comparação:** Bloco 1 inteiro foi ~20 unidades. Bloco 10 é ~1.75x o Bloco 1. Pesado, mas proporcional ao risco que fecha — e significativamente menor que os 61-63 unidades do plano externo 8+9.

---

## 9. Restrições operacionais (idênticas aos planos anteriores)

1. **Arquivos selados** (novos hooks 10.1.3, 10.3.3; modificações em `verify-slice.sh` 10.2.4/10.5.3) só via `scripts/relock-harness.sh` em terminal externo pelo PM. Cada relock gera incident file auditável.
2. **Auditoria deste plano em sessão nova.** Este arquivo foi escrito na mesma sessão em que o risco foi levantado — viés confirmatório é inevitável. Adicionar item **9.4** ao Bloco 9 do plano externo:
   > 9.4 Auditar `executability-gap-action-plan.md` em sessão nova — os 5 itens cobrem o risco? Há alternativa mais barata? Algum item é over-engineering?
3. **R9 zero bypass.** Nenhum dos gates novos tem flag `--skip` ou similar.
4. **R12 aplicado a toda saída PM.** Walkthrough files, mensagens de bloqueio, explain-slice output — tudo em PT-BR sem jargão.
5. **Admin bypass do PM (owner merge)** não pode ser usado para pular walkthrough. O walkthrough é o próprio mecanismo pelo qual o PM exerce aprovação — bypassar é contradição.

---

## 10. Próxima ação (após decisão PM)

1. **PM registra decisão** em `docs/decisions/pm-decision-executability-gap-2026-04-11.md`. Template pronto em `docs/decisions/pm-decision-executability-gap-TEMPLATE-2026-04-11.md`.
2. **Se aceito:** agente atualiza `meta-audit-tracker.md` promovendo Bloco 10 para status aceito. Adiciona item 9.4 ao Bloco 9 do plano externo.
3. **Em sessão nova** (viés confirmatório), agente:
   - Abre a auditoria 9.4 (validar se este plano está correto).
   - Começa pelo item 10.1.1 (ADR-0003 E2E tool) — é o **único item do Bloco 10 que pode começar antes do Bloco 2**, porque é puro ADR e pode ser decidido em paralelo com `/decide-stack`.
4. **Todo o resto** espera `/decide-stack` (Bloco 2) fechar.

**Se rejeitado:** gap continua aberto e documentado. PM aceita conscientemente o risco e registra no `docs/decisions/` a justificativa.

**Se parcialmente aceito:** PM marca no template quais dos 5 itens aceita e quais rejeita, com justificativa por item rejeitado.

---

## 11. Rastreabilidade

- **Este plano:** `docs/audits/progress/executability-gap-action-plan.md`
- **Decisão PM:** `docs/decisions/pm-decision-executability-gap-TEMPLATE-2026-04-11.md` (template) → `docs/decisions/pm-decision-executability-gap-2026-04-11.md` (após decisão)
- **Tracker principal atualizado:** `docs/audits/progress/meta-audit-tracker.md` §Bloco 10
- **Memória atualizada** (após decisão): `memory/project_meta_audit_action_plan.md`
- **Pergunta de origem:** PM 2026-04-11 — *"nesse ambiente tem o risco do sistema ser construído e não conseguirmos executar os fluxos do prd, funções etc?"*
- **Restrição-chave de origem:** PM 2026-04-11 — *"não entendo nada de código, posso validar teste de produto no navegador"*
