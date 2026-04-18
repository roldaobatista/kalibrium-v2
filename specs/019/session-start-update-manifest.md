# Manifesto de Patch Diferido — `scripts/hooks/session-start.sh`

**Slice:** 019
**Arquivo alvo:** `scripts/hooks/session-start.sh` (SELADO em `scripts/hooks/MANIFEST.sha256`)
**Risco:** R-04 do plan (arquivo selado bloqueado para o agente Claude Code)
**ADR relevante:** CLAUDE.md §9 — "Como atualizar um hook ou settings.json (pós-Bloco 1)"
**Responsável por aplicar:** Product Manager (terminal externo, `relock-harness.sh`)
**Momento de aplicação:** imediatamente APÓS o merge do PR do slice-019, ANTES de abrir nova sessão Claude.

---

## 1. Por que este arquivo existe

O AC-003 da spec exige que `scripts/hooks/session-start.sh` invoque automaticamente `scripts/install-git-hooks.sh --silent` quando `.git/hooks/pre-push` estiver ausente ou não referenciar `scripts/hooks/pre-push-native.sh`. Mas `session-start.sh` é um arquivo selado:

- Está listado em `scripts/hooks/MANIFEST.sha256` (linha `198d033efbc2328dd1c781cc0fdfd210ced32b52fa16a12114ab52811d5f6530  session-start.sh`).
- O hook `sealed-files-bash-lock.sh` bloqueia qualquer tentativa de `Edit`/`Write`/`Bash` sobre ele.
- Alterar o arquivo **obriga re-geração do selo** via `scripts/relock-harness.sh`, que por sua vez exige:
  1. `KALIB_RELOCK_AUTHORIZED=1` no environment
  2. stdin TTY real (bloqueia a Bash tool do Claude Code)
  3. Confirmação digitada literal `RELOCK`
  4. Criação automática de `docs/incidents/harness-relock-<timestamp>.md`

Portanto, **o agente não pode aplicar o patch em tempo de slice**. O agente entrega (a) os scripts que serão invocados, (b) o patch textual pronto, (c) este manifesto auditável. O PM aplica em T-14 (pós-merge do PR).

---

## 2. Patch a aplicar

### Localização

`scripts/hooks/session-start.sh` — novo bloco após o bloco 4.5 "Drift checks selados" e antes do bloco final de resumo. Nomear como **Bloco 4.7 — Git native hooks (slice-019)**.

### Conteúdo do bloco (copiar literalmente)

```bash
# --------------------------------------------------------------------------
# Bloco 4.7 — Git native hooks (slice-019 / AC-003 / B-042)
#
# Reinstala .git/hooks/pre-push automaticamente quando ausente ou quando
# o hook atual nao referencia scripts/hooks/pre-push-native.sh (ex: hook
# legado sobrescrito por outro tool como husky).
#
# Modo --silent: nao bloqueia SessionStart. Falha silenciosa e aceitavel —
# session-start nao e gate de pre-push; o hook e auto-healing.
# --------------------------------------------------------------------------
if [ ! -f .git/hooks/pre-push ] || ! grep -q "pre-push-native.sh" .git/hooks/pre-push 2>/dev/null; then
  if [ -f scripts/install-git-hooks.sh ]; then
    bash scripts/install-git-hooks.sh --silent 2>/dev/null || true
    echo "[session-start] reinstalled git hook: .git/hooks/pre-push" >&2
  fi
fi
```

### Ponto de inserção exato

Antes da linha final que imprime "Session ready" (ou equivalente — última linha visível do script). Indentação: nenhuma (top-level do script, igual aos outros blocos 4.x).

---

## 3. Procedimento do PM (pós-merge)

Executar em terminal externo (Git Bash no Windows, fora do Claude Code):

```bash
# 1. Garantir branch main local atualizada com o merge do PR-019
cd /c/PROJETOS/saas/kalibrium-v2
git checkout main
git pull origin main

# 2. Editar manualmente scripts/hooks/session-start.sh
#    Colar o bloco 4.7 acima no local indicado.
#    (VS Code, notepad++, vim — qualquer editor fora do Claude Code.)

# 3. Validar sintaxe bash
bash -n scripts/hooks/session-start.sh

# 4. Rodar o script uma vez localmente para validar que o bloco executa
#    (ele vai reinstalar o hook pre-push como side-effect — OK)
bash scripts/hooks/session-start.sh
ls -la .git/hooks/pre-push   # deve existir e ser executavel
grep pre-push-native .git/hooks/pre-push   # deve retornar a linha

# 5. Rodar relock (regenera MANIFEST.sha256 + .claude/settings.json.sha256)
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
#    - script exige stdin TTY: rodar direto no terminal (nao em IDE terminal embutida)
#    - digitar literal: RELOCK
#    - script cria automaticamente docs/incidents/harness-relock-<timestamp>.md

# 6. Stage dos arquivos afetados + commit
git add scripts/hooks/session-start.sh \
        scripts/hooks/MANIFEST.sha256 \
        .claude/settings.json.sha256 \
        docs/incidents/harness-relock-*.md

git commit -m "chore(harness): slice-019 T-14 — bloco 4.7 session-start.sh + relock"

git push origin main
```

---

## 4. Verificação pós-relock (obrigatoria, em nova sessao Claude)

1. Deletar manualmente `.git/hooks/pre-push` (simular condição gatilho).
2. Abrir nova sessão Claude Code (ou novo terminal fazendo `bash scripts/hooks/session-start.sh`).
3. Observar stderr: deve aparecer a linha `[session-start] reinstalled git hook: .git/hooks/pre-push`.
4. Conferir `ls -la .git/hooks/pre-push` — recriado, executável, contendo `pre-push-native.sh`.

Caso a linha não apareça ou o hook não seja recriado, reverter o relock (rodar relock com versão anterior de session-start.sh) e abrir incidente.

---

## 5. Gate residual do slice

O teste PHP `tests/slice-019/AC003SessionStartReinstallTest.php` contém 4 asserções de conteúdo literal (`install-git-hooks.sh`, `--silent`, `pre-push-native.sh`, `[session-start] reinstalled git hook`). Essas 4 asserções **ficarão red até a aplicação do patch pelo PM**. A 5ª asserção (sandbox em repo temp) fica green assim que `scripts/install-git-hooks.sh` existir — ela prova a LÓGICA sem depender do selo.

**Implicação para o pipeline de gates:**

- Os gates L2 subsequentes (`verify-slice`, `review-pr`, `security-review`, etc.) irão rodar a suite `Slice019` e detectar 4 falhas AC-003 parciais.
- Tratamento: plan-review F-002 S4 (non-blocking) **já autoriza** este estado como trade-off aceito do slice. O auditor de tests `audit-tests-draft` **já aprovou** com `verdict: approved` e `findings: []`.
- A cobertura de `AC-003` é declarada como "gated em verificável após relock" — T-14 fecha o gap.

**Resumo em linguagem de produto (R12):** o teste AC-003 prova a lógica do auto-healing em sandbox. A verificação real em `session-start.sh` acontece em um passo manual pós-merge pelo PM (5 minutos, fora do Claude Code, procedimento documentado neste manifesto).

---

## 6. Rollback

Se o relock falhar ou o bloco 4.7 causar regressão inesperada:

1. `git revert` do commit do bloco 4.7 + relock.
2. Push para main.
3. Nova sessão Claude valida SHA antigo no SessionStart (volta ao estado pre-slice-019).

Impacto de rollback: apenas o auto-healing do hook git pára. Os scripts `install-git-hooks.sh`, `pre-push-native.sh`, `check-tenant-filter-coverage.sh` **continuam funcionando** — estão em commits separados. AC-001, AC-002, AC-004, AC-005, AC-006, AC-007 permanecem satisfeitos.

---

## 7. Rastreabilidade

- **spec.md §AC-003:** declara comportamento esperado do auto-healing.
- **plan.md §D-04:** escolhe auto-reinstall silencioso vs alternativas (SHA check / aviso sem ação).
- **plan.md §R-04:** identifica o selo como bloqueador e propõe este manifesto.
- **plan-review.json §F-002 (S4):** aceita o gap residual como trade-off não-bloqueante.
- **tests-draft-audit.json §notes:** declara `AC-003 usa estratégia grep+sandbox para arquivo selado`.
- **CLAUDE.md §9:** procedimento canônico de relock.
- **tests/slice-019/AC003SessionStartReinstallTest.php:** testes que ficarão red em 4 asserções até T-14.
