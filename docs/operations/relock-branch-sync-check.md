# Procedimento de relock — adicionar branch-sync-check ao harness

**Débito fechado por este procedimento:** B-031 (guide-backlog)

## Contexto

O slice-015 mostrou que branches que ficam muito atrás de `origin/main` causam conflitos grandes quando retomadas — inclusive em arquivos selados (`MANIFEST.sha256`), o que força abandono de PR e recriação via cherry-pick.

**Mitigação:** rodar `branch-sync-check.sh` no SessionStart e avisar quando a branch está > 10 commits atrás de main.

Script já implementado em `scripts/staging/branch-sync-check.sh` (área não selada, agente pode editar livremente). Resta o passo de integração com o harness selado.

## O que falta fazer (PM, terminal externo)

### 1. Validar o script

```bash
cd /c/PROJETOS/saas/kalibrium-v2
bash scripts/staging/branch-sync-check.sh
# Esperado em main: sem output, exit 0.
# Esperado em branch atrasada: banner de aviso, exit 0 (ou 1 se KALIB_BRANCH_SYNC_FAIL=1).
```

### 2. Mover para área selada

```bash
mv scripts/staging/branch-sync-check.sh scripts/hooks/branch-sync-check.sh
chmod +x scripts/hooks/branch-sync-check.sh
```

### 3. Referenciar em `session-start.sh`

Editar `scripts/hooks/session-start.sh` adicionando, antes da linha `[session-start] concluído`:

```bash
# Aviso de branch desatualizada (B-031)
bash "$SCRIPT_DIR/branch-sync-check.sh" || true
```

(O `|| true` mantém SessionStart non-blocking; o aviso aparece mas não aborta.)

### 4. Relock do harness

```bash
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
# Pedirá digitação literal "RELOCK" (TTY check obrigatório).
# Criará docs/incidents/harness-relock-<timestamp>.md automaticamente.
```

### 5. Commit do relock

```bash
git add scripts/hooks/branch-sync-check.sh \
        scripts/hooks/session-start.sh \
        scripts/hooks/MANIFEST.sha256 \
        docs/incidents/harness-relock-*.md
git commit -m "chore(harness): add branch-sync-check no session-start + relock (fecha B-031)"
```

### 6. Verificar no próximo SessionStart

Abrir nova sessão Claude Code. O SessionStart deve rodar sem erro; em branch atrasada, imprimir o banner de aviso.

## Rollback

Se o hook começar a gerar falsos positivos:

```bash
# Em terminal externo:
cd /c/PROJETOS/saas/kalibrium-v2
# Remover referência em session-start.sh
sed -i '/branch-sync-check/d' scripts/hooks/session-start.sh
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
git commit -am "chore(harness): remove branch-sync-check (rollback B-031)"
```

## Threshold

Padrão: 10 commits. Ajustável via env:

```bash
# Aviso a partir de 5 commits:
KALIB_BRANCH_SYNC_THRESHOLD=5 bash scripts/hooks/branch-sync-check.sh

# Falhar (exit 1) em vez de só avisar:
KALIB_BRANCH_SYNC_FAIL=1 bash scripts/hooks/branch-sync-check.sh
```
