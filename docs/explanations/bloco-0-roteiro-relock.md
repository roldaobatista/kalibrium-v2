# Bloco 0 — Roteiro pro PM executar em terminal externo

**Data:** 2026-04-12
**Contexto:** Fase A do plano pós-auditoria de operabilidade. O agente preparou 3 arquivos novos e 1 rascunho. Você (PM) precisa executar **um único procedimento manual** pra ativar o rascunho via relock.

---

## O que o agente já fez sozinho (commitado normalmente depois deste roteiro)

1. **`.github/workflows/ci.yml`** — CI do GitHub Actions com 6 jobs (harness integrity, PHP lint, PHP static analysis, PHP tests, JS lint, security). Cada job de PHP/JS começa pulando se `composer.json`/`package.json` não existir, então o workflow funciona mesmo com o projeto Laravel ainda não inicializado — ele "acorda" automaticamente quando você rodar `composer create-project` no futuro.

2. **`scripts/relock-and-commit.sh`** — wrapper do relock. Detecta quais arquivos selados mudaram, pergunta uma linha de descrição, chama o relock, stage cirúrgico, commit automático. **Preserva todas as salvaguardas** (TTY, digitação `RELOCK`, incidente auditável).

3. **`tools/relock.bat`** — atalho Windows. Duplo-clique abre Git Bash numa janela nova e roda o wrapper.

4. **`scripts/drafts/post-edit-gate.sh`** — rascunho do novo hook com comandos Laravel concretos (Pint, PHPStan/Larastan, Pest). Substitui o esqueleto stack-agnóstico atual. **Não está ativo ainda** — precisa do relock pra virar `scripts/hooks/post-edit-gate.sh`.

---

## O que você precisa fazer — passos concretos

### Passo 1 — Ativar o draft do post-edit-gate (B-001)

**Abra Git Bash** (terminal externo, NÃO dentro do Claude Code) e rode **exatamente estes comandos**:

```bash
cd /c/PROJETOS/saas/kalibrium-v2

# 1. Sobrescreve o hook selado com o rascunho
cp scripts/drafts/post-edit-gate.sh scripts/hooks/post-edit-gate.sh

# 2. Verifica que a cópia foi feita (deve mostrar diff)
git diff scripts/hooks/post-edit-gate.sh | head -30
```

Se o `git diff` mostrar as mudanças esperadas (linhas sobre Laravel/Pint/PHPStan/Pest), siga pro Passo 2.

Se mostrar vazio ou erro, **pare e me avise** — algo não bateu.

### Passo 2 — Rodar o relock via wrapper (primeiro teste real do B-020)

**Ainda no mesmo Git Bash**, rode:

```bash
bash scripts/relock-and-commit.sh
```

Ou — **se você quiser testar o `.bat`** que é o caminho "duplo-clique" do dia-a-dia:

1. Feche o Git Bash
2. Abra o Explorer do Windows na pasta `C:\PROJETOS\saas\kalibrium-v2\tools\`
3. Duplo-clique em `relock.bat`

Em qualquer dos 2 caminhos, o que vai acontecer:

1. Wrapper detecta que `scripts/hooks/post-edit-gate.sh` mudou
2. Pede uma descrição: digite **exatamente** isto (sem aspas):
   ```
   B-001 — post-edit-gate com comandos Laravel (Pint + Larastan + Pest)
   ```
3. `relock-harness.sh` pede digitação literal `RELOCK` pra confirmar — digite e aperte Enter
4. Wrapper stage automaticamente + commita com mensagem `chore(harness): B-001 — post-edit-gate ...`
5. Mostra diff do commit e termina

### Passo 3 — Voltar pro Claude Code

Volta pra sessão do Claude Code aqui no terminal/editor. O SessionStart hook vai validar automaticamente que:

- `.claude/settings.json.sha256` bate (settings não mudou nesta rodada, só o hook)
- `scripts/hooks/MANIFEST.sha256` bate com os hooks atuais (agora com o novo post-edit-gate)

Se ambos passarem, me avise (ex.: "relock feito") que eu continuo pela Fase A e fecho o Bloco 0.

---

## Coisas que podem dar errado (e o que fazer)

| Sintoma | Causa provável | Ação |
|---|---|---|
| `relock.bat` diz "Git Bash não encontrado" | Git for Windows instalado em local atípico | Edite `tools/relock.bat` na linha `set "GIT_BASH="` com o caminho correto; ou rode `bash scripts/relock-and-commit.sh` diretamente |
| Wrapper diz "Nada pra relock" no Passo 2 | O `cp` do Passo 1 não foi feito | Refaça Passo 1 |
| `relock-harness.sh` diz "camada 2 falhou (sem TTY)" | Você rodou o wrapper dentro do Claude Code | Feche a sessão, abra Git Bash externo |
| Digitou algo diferente de `RELOCK` | Proteção de camada 3 | Rode o wrapper de novo — ele volta pro passo 1 do relock sem ter alterado nada |
| `pre-commit-gate.sh` reprova o commit | Algum check do R5/R9 falhou | Leia a mensagem de erro. Mais comum: autor git não está na allowlist. Corrija `git config user.name/email` e rode `bash scripts/relock-and-commit.sh` de novo — ele detecta que nada mudou desde o último relock e commita sem re-relock |
| SessionStart do próximo Claude Code reprovar | Drift entre manifesto e realidade | Não tente "consertar com relock" — me chama; investigamos juntos |

---

## Checklist de confirmação (marque ao terminar)

- [ ] Passo 1 executado, `git diff` mostrou as mudanças Laravel do `post-edit-gate.sh`
- [ ] Passo 2 executado via `bash scripts/relock-and-commit.sh` OU via `tools/relock.bat`
- [ ] Descrição digitada corretamente
- [ ] `RELOCK` digitado
- [ ] Commit criado (`git log -1` mostra `chore(harness): B-001 — post-edit-gate ...`)
- [ ] Commit **não** foi pushado ainda (intencional — quem decide é você)
- [ ] Voltou pra sessão do Claude Code

Quando todos marcados, me avise neste chat e eu fecho o Bloco 0.
