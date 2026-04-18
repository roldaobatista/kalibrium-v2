# Relock pendente — slice 018 (enum canônico de gates)

**Status:** pendente (aguardando merge do PR do slice 018).
**Branch:** `feat/slice-018-harness-regression-bias-schema`
**Data:** 2026-04-17
**Operador esperado do relock:** PM (roldao.tecnico@gmail.com) — em terminal externo.

---

## Escopo da atualização (após relock)

Após o relock manual do PM, `scripts/merge-slice.sh` passa a:

1. **Invocar `scripts/validate-gate-output.sh` como pre-check** de cada JSON de gate antes de validar `verdict`/`gate`/`$schema`/`slice` — o validator cobre os 3 tipos de violação que o slice 018 introduz fixtures para:
   - violação **`$schema`** (URL em vez do literal `"gate-output-v1"`)
   - violação **`gate`** (valor fora do enum canônico; p. ex. `"review"` vs `"code-review"`)
   - violação **`slice`** (campo `slice` ausente ou fora do padrão `^[0-9]{3}$`)
2. Aceitar aliases legacy (mapeamento abaixo) para os slices 001-017.
3. Emitir warning observável quando um alias for usado.

---

## Motivo

Slice 018 introduz o enum canônico de gates em `docs/protocol/schemas/gate-output.schema.json` (v1.2.4). Os valores aceitos no campo `gate` passam a ser:

```
verify, review, security-gate, audit-tests-draft, audit-tests,
functional-gate, data-gate, observability-gate, integration-gate,
master-audit, audit-spec, audit-story, audit-planning, plan-review,
spec-security, guide-audit, ux-gate, ci-gate
```

Slices 001-017 emitiram JSONs legacy com nomes fora do enum canônico atual (notadamente `"code-review"`, `"security"`, `"functional"`). O validador `scripts/merge-slice.sh` (arquivo **selado**) hoje aceita esses nomes porque o dict `required_gates` usa os próprios valores legacy. Ao endurecer o validador para o enum canônico, merges de slices legacy passariam a quebrar — quebrando reprodução e auditoria retroativa.

**Decisão:** preservar aliases legacy como compat-shim dentro do próprio `merge-slice.sh`, sem alterar os JSONs já commitados dos slices 001-017.

## Diff proposto de `scripts/merge-slice.sh`

**Hash atual (antes do relock):** `f0e420fbec251bb8ed98acf86695dc21ed299d62`
(obtido via `git rev-parse HEAD:scripts/merge-slice.sh` em `157aa8d`)

Alteração proposta no bloco `required_gates` / `validate_gate()`:

```python
# Gates obrigatorios em todo slice (protocolo v1.2.4 §4)
# (filename, expected_gate_value_canonico, aliases_legacy, label_humano)
required_gates = [
    ("verification.json",    "verify",           [],                           "verify"),
    ("review.json",          "review",           ["code-review"],              "code-review"),
    ("security-review.json", "security-gate",    ["security"],                 "security-gate"),
    ("test-audit.json",      "audit-tests",      [],                           "audit-tests"),
    ("functional-review.json","functional-gate", ["functional"],               "functional-gate"),
    # condicionais mantidos como estavam
    ("data-review.json",          "data-gate",           [], "data-gate (condicional)"),
    ("observability-review.json", "observability-gate",  [], "observability-gate (condicional)"),
    ("integration-review.json",   "integration-gate",    [], "integration-gate (condicional)"),
    ("master-audit.json",    "master-audit",     [],                           "master-audit"),
]

def validate_gate(filename, expected_canonical, aliases, label, required=True):
    """Aceita valor canônico; se valor legado aparecer (em aliases), loga aviso e prossegue."""
    # ... carregar JSON, extrair obj["gate"] ...
    if obj["gate"] == expected_canonical:
        return True
    if obj["gate"] in aliases:
        log.warning(
            f"[{label}] {filename} usa gate legacy '{obj['gate']}'; "
            f"canonico esperado = '{expected_canonical}'. Aceitando como compat-shim "
            "(slices 001-017 pre-schema v1.2.4)."
        )
        return True
    return False  # rejeita
```

A lógica do loop `for filename, expected_gate, label in required_gates:` precisa ser atualizada para desempacotar os 4 campos (inclui `aliases`).

## Justificativa da compat layer

1. **Retro-compat sem reescrita de histórico:** JSONs de gate dos slices 001-017 são imutáveis (R6-estratégia-evidencias). Reescrevê-los via filter-branch invalidaria assinaturas, commit hashes e auditoria dual-LLM já preservada.
2. **Forward-compat estrita:** novos slices (018+) passam a emitir APENAS nomes canônicos (enforced pelo `audit-tests-draft` e pelo próprio validador de schema quando invocado em gates).
3. **Escopo fechado:** a compat só é aplicada ao `merge-slice.sh`. O schema JSON permanece puro (sem aliases).
4. **Observabilidade:** cada uso do alias gera warning visível, facilitando rastreamento dos slices legacy.

## Instruções ao PM (terminal externo)

**Somente após o PR do slice 018 ter sido mergeado em `main`:**

```bash
# 1. Sair do Claude Code (encerrar a sessao ativa).

# 2. Em terminal externo (Git Bash ou WSL), fora do agente:
cd /c/PROJETOS/saas/kalibrium-v2
git checkout main
git pull origin main

# 3. Editar scripts/merge-slice.sh aplicando o diff proposto acima.
#    (abrir em editor de sua escolha; o arquivo esta selado pelo hooks-lock,
#    mas edicao manual em terminal externo passa — o gate do agente
#    so bloqueia o Claude Code/Codex CLI, nao o PM).

# 4. Executar o relock legitimo (4 camadas de salvaguarda):
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
#    O script pedira digitacao literal de "RELOCK" para confirmar.
#    Gera automaticamente docs/incidents/harness-relock-<timestamp>.md
#    com operador, host, hashes antes/depois.

# 5. Stage + commit (atomico):
git add scripts/merge-slice.sh \
        scripts/hooks/MANIFEST.sha256 \
        .claude/settings.json.sha256 \
        docs/incidents/harness-relock-*.md
git commit -m "chore(harness): merge-slice aceita aliases legacy (slices 001-017) + relock pos-slice-018

Compat shim: gate_value aceita canonico (review/security-gate/functional-gate)
ou alias legacy (code-review/security/functional). Slices 001-017 permanecem
validaveis; slices 018+ usam apenas canonico. Justificativa:
docs/incidents/harness-relock-pending-slice-018.md.
"
git push origin main

# 6. Voltar ao Claude Code. O SessionStart validara automaticamente:
#    - settings-lock --check (confirma .claude/settings.json.sha256 bate)
#    - hooks-lock --check (confirma scripts/hooks/MANIFEST.sha256 bate)
#    Se algum drift for detectado, a sessao aborta.
```

## Até o relock acontecer

- Merges manuais/automaticos de slices legacy (001-017) continuam funcionando com o validador atual.
- Novos slices (018+) emitindo gate names canonicos **vao falhar** no `merge-slice.sh` vigente porque o dict `required_gates` hoje cravado espera `"code-review"` / `"security"` / `"functional"`. A mitigacao intermediaria para o proprio slice 018 e:
  - seus gates locais usarem os valores antigos (`"code-review"`), OU
  - o merge do PR 018 acontecer por **admin bypass do owner** (registrado no log do GitHub — ver `docs/incidents/pr-1-admin-merge.md` como precedente).
- A segunda opcao e preferivel: preserva a pureza do enum para o slice que introduz a mudanca.

## Riscos avaliados

| Risco | Probabilidade | Mitigacao |
|---|---|---|
| PM esquecer do relock | Media | SessionStart falha duro se drift aparecer; lembrete no handoff |
| Aplicar relock sem a edicao manual | Baixa | Script exige digitacao `RELOCK` + cria incident file automatico |
| Edicao manual introduzir bug no parser Python inline | Media | Rodar `python3 scripts/merge-slice.sh --self-test` apos edicao (se existir); caso contrario, simular merge de slice 010 merged em dry-run |
| Compat shim permanecer permanente | Alta | Adicionar nota de sunset em `docs/protocol/08-metricas-processo.md`: remover aliases quando todos os slices <018 forem arquivados/epilogados |

## Cross-references

- `docs/protocol/schemas/gate-output.schema.json` v1.2.4 (enum canonico definido aqui)
- `docs/protocol/04-criterios-gate.md` §§1-15 (criterios objetivos por gate)
- `specs/018/spec.md` (AC-011: migracao enum canonico com preservacao legacy)
- `scripts/relock-harness.sh` (procedimento legitimo de alteracao de arquivos selados)
- `CLAUDE.md` §9 (procedimento completo de relock com 4 camadas)
