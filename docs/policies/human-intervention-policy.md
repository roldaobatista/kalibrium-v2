# Política de intervenção humana — Kalibrium V2

**Criada em:** 2026-04-11
**Base:** instrução vinculante do PM em 2026-04-11: *"todo o ambiente tem que ser desenhado para ter o mínimo possível de intervenção humana, só quando obrigado... eu não sei nada de código, mas o sistema tem que ser seguro, o ambiente tem que reduzir a zero o risco do código ser gerado errado."*
**Registro formal:** `docs/decisions/pm-decision-direction-reversal-2026-04-11.md §8`.

---

## Regra-mestre

**O PM intervém SÓ quando fisicamente obrigado. Tudo o resto é decisão do agente dentro do harness.**

---

## As 5 situações em que o humano OBRIGATORIAMENTE intervém

**Esta é uma lista fechada e exaustiva.** Qualquer situação fora desta lista = o agente tem que resolver sozinho (ou escalar internamente verifier + reviewer + pausa dura R6/R7).

### 1. Iniciar sessão Claude Code

- Abrir o cliente Claude Code.
- Invocar comando inicial (geralmente o próximo passo do `docs/product/CAMINHO.md`).
- **O agente não pode se auto-inicializar.**

### 2. Responder decisão de produto em linguagem de produto

Formato permitido (tradutor R12):
- `"sim"` / `"não"`
- `"aceito A"` / `"aceito B"` / `"aceito C"`
- `"faltou X"` / `"precisa incluir Y"` / `"remove Z"`
- `"testei a tela, funcionou"` / `"testei a tela, não funcionou — Z"`

Formato **proibido** (responsabilidade do agente, não do PM):
- Avaliar diff de código
- Aprovar schema SQL
- Escolher padrão de projeto
- Avaliar mensagem de erro
- Ler output JSON/YAML cru

### 3. Testar feature entregue

Quando o agente entrega uma tela/feature visível:
- PM abre o sistema no navegador.
- PM navega pela feature como se fosse um usuário real (Marcelo, Juliana ou Rafael conforme o caso).
- PM responde **em linguagem de produto** se funcionou ou não.

**Proibido:** pedir ao PM para abrir DevTools, ver logs, verificar rede, inspecionar elemento.

### 4. Executar as ações manuais administrativas

As 4 ações manuais atualmente abertas em `docs/reports/pm-manual-actions-2026-04-10.md`:

| Código | Ação | Intervenção necessária |
|---|---|---|
| **C4** | Selar `docs/harness-limitations.md` no MANIFEST | Terminal externo + procedimento relock (PM executa, não o agente) |
| **A3** | Adicionar gate de advisor-review ao pre-commit-gate | Terminal externo + procedimento relock |
| **A4** | NDA + proposta comercial do advisor técnico externo | Negociação contratual (PM + advogado/financeiro) |
| **DPO** | Contratar DPO fracionário | Negociação contratual (PM + DPO escolhido) |

Plus as 2 contratações de consultor previstas na trilha paralela:

| Código | Ação | Intervenção necessária |
|---|---|---|
| **M1-M2** | RFP + contratação consultor metrologia (golden tests GUM/ISO 17025) | RFP publicada + negociação |
| **F1-F2** | RFP + contratação consultor fiscal (NF-e/NFS-e/ICMS) | RFP publicada + negociação |

**Nenhuma outra ação manual pode ser criada sem PR de atualização desta política.**

### 5. Autorizar incidente P0 (último recurso)

Se tudo falhar e houver incidente P0 crítico, o PM pode consumir o último bypass disponível (4/5 → 5/5) assinando explicitamente o arquivo de incidente. **Só uma vez**. Depois disso, o projeto pausa para re-auditoria externa obrigatória.

---

## O que o humano NUNCA faz (proibido pelo harness)

**Lista exaustiva.** Se o agente pedir qualquer coisa abaixo, ele errou R12. O PM pode responder `"R12. Em linguagem de produto."` e o agente deve reescrever.

- ❌ Revisar diff de código
- ❌ Analisar stack trace
- ❌ Escolher entre alternativas técnicas sem tradução R12
- ❌ Validar teste unitário ou resultado de lint
- ❌ Decidir sobre migração de banco, schema, índice, query
- ❌ Avaliar mensagem de erro técnica
- ❌ Interpretar output JSON, YAML, log, hook, verification.json cru
- ❌ Aprovar estrutura de diretório, nomenclatura de classe, padrão de projeto
- ❌ Escolher biblioteca, versão de dependência, algoritmo
- ❌ Ler `plan.md` cru — isso é função do verifier/reviewer em sub-agent
- ❌ Ler qualquer arquivo com extensão `.sh`, `.json`, `.yaml`, `.ts`, `.py`, `.sql`, `.css`, etc. para o produto. (Para configuração de ambiente, ver §4 — ações administrativas.)
- ❌ Aprovar merge de PR antes de verifier+reviewer+CI concordarem

---

## Garantias de segurança em troca dessa restrição

Para honrar "reduzir a zero o risco de código errado", o harness se compromete a:

### G1. Todo slice passa por 3 verificações independentes antes do merge

1. **Verifier** (sub-agent isolado, não vê a story, só roda testes contra o código real) — emite `verification.json` com `verdict: approved | rejected`.
2. **Reviewer** (sub-agent isolado, não vê o output do verifier, revisa o código contra a spec) — emite `review.json` com `verdict: approved | rejected`.
3. **CI externo** (GitHub Action, terceiro ponto de vista) — roda lint + types + suite completa + harness-integrity check.

**Todos os 3 têm que aprovar. Um "rejected" = slice volta para iteração. R6 se repetir.**

### G2. Nenhum slice em categoria crítica começa sem golden tests

Categorias críticas (definidas em `docs/policies/r6-r7-policy.md`):
- `metrology` (cálculo de incerteza, GUM/JCGM 100:2008)
- `fiscal` (NF-e/NFS-e/ICMS)
- `compliance` (LGPD, ICP-Brasil, RBC)
- `security` (auth, multi-tenant, secrets)
- `numerical_correctness` (qualquer cálculo que vira número em certificado)
- `adr_compliance` (decisões arquiteturais registradas)
- `simplicity` (anti-over-engineering)

**Para slices em categoria crítica, obrigatório:**
- Golden tests do consultor externo (metrologia ou fiscal) aprovados e commitados em `tests/golden/<categoria>/`
- Pausa dura ativa (R6/R7) — PM **não pode** aprovar override
- Assinatura do consultor em incidente de aprovação, se houver dúvida

**Para slices em categoria não-crítica:**
- Verifier + Reviewer + CI continuam obrigatórios
- Golden tests específicos não são pré-requisito
- PM pode aprovar override com justificativa registrada (nunca em categoria crítica)

### G3. Hash-lock permanente no harness

Os arquivos selados (`.claude/settings.json`, `scripts/hooks/*`, `.claude/telemetry/*`, `MANIFEST.sha256`) continuam selados com 4 camadas de proteção (ambiente + TTY + confirmação digitada + incident file). **O agente não pode modificar o próprio harness**, só via procedimento manual do PM (ações C4/A3 na lista acima).

### G4. Tradutor R12 obrigatório em toda saída PM

Cada resposta ao PM passa pelo vocabulário permitido de produto. Vocabulário proibido é bloqueado pelo hook `check-r12-vocabulary.sh` (item 4.2 do tracker — Bloco 4, pendente). Até esse hook existir, o agente é responsável manualmente.

### G5. Cleanup automático de entropia

O sub-agent `guide-auditor` roda periodicamente (Bloco 8.4 pendente, alvo de implementação). Se detectar drift (padrões ruins sendo replicados, convenções sendo violadas), abre PR de cleanup automático.

---

## Como o agente deve agir em cada sessão

### Abertura de sessão

1. `session-start.sh` valida harness (hash-lock, constitution, allowlist git, etc.).
2. Agente lê `docs/product/CAMINHO.md` como primeiro documento.
3. Agente lê `docs/product/PRD.md` como constituição de produto.
4. Agente lê `docs/constitution.md` como constituição de harness.
5. Agente lê esta política (`human-intervention-policy.md`) como lista das intervenções humanas permitidas.
6. Agente lê memória persistente (`memory/MEMORY.md` + índice).
7. **Só depois** o agente começa a responder.

### Durante a sessão

1. Toda saída pro PM passa por R12 mentalmente.
2. Se o agente sentir necessidade de pedir algo ao PM, primeiro verifica esta política.
3. Se o pedido não está nas 5 situações da §1-5, o agente **não pede** — resolve sozinho ou escala R6/R7.
4. Se o PM disser `"R12"`, o agente para e reescreve a última resposta em linguagem de produto.

### Fim de sessão

1. `stop-gate.sh` valida que nada foi quebrado.
2. Agente entrega **1 resposta final** em linguagem de produto: o que fez + próxima ação única.
3. Nenhum TODO pendente fora do `CAMINHO.md`.

---

## Reversão desta política

Esta política só é alterada via novo arquivo em `docs/decisions/pm-decision-human-intervention-update-YYYY-MM-DD.md`, com assinatura explícita do PM consciente do trade-off.

**Nunca editar este arquivo in-place.**
