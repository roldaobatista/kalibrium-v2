#!/usr/bin/env bash
# scripts/install-postgres-mcp.sh — helper de instalação do MCP Postgres.
#
# INVOCADO POR: instalar-postgres-mcp.bat (duplo-clique do PM).
# PRÉ-REQUISITO: Claude Code CLI precisa estar fechado (o arquivo .claude/settings.json
#                vai ser modificado fora da sessão, seguindo CLAUDE.md §9).
#
# O script:
#   1. Sanity checks (Claude CLI disponível, .env presente).
#   2. Lê DB_* do .env local.
#   3. Chama `claude mcp add postgres` com a connection string montada.
#   4. Acrescenta entrada em .claude/allowed-mcps.txt (se ainda não existir).
#   5. Atualiza docs/adr/0002-mcp-policy.md (bump versão + linha na tabela).
#   6. Roda KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
#      — o PM precisa digitar RELOCK quando o script pedir.
#   7. Deixa os arquivos prontos pra commit na próxima sessão do Claude Code.
#
# NÃO commita automaticamente — o commit fica para a próxima sessão.

set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BOLD='\033[1m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

header() { printf "\n${BOLD}${CYAN}=== %s ===${NC}\n" "$1"; }
ok()     { printf "${GREEN}✓ %s${NC}\n" "$1"; }
warn()   { printf "${YELLOW}! %s${NC}\n" "$1"; }
fail()   { printf "${RED}✗ %s${NC}\n" "$1"; exit 1; }

header "1/6 — Verificando pré-requisitos"

if ! command -v claude >/dev/null 2>&1; then
  fail "Comando 'claude' não encontrado no PATH. Instale o Claude Code CLI antes de rodar este script."
fi
ok "Claude CLI encontrado em $(command -v claude)"

if [[ ! -f .env ]]; then
  fail ".env não encontrado na raiz do projeto. Copie .env.example para .env e configure DB_* antes de rodar."
fi
ok ".env presente"

# 2/6 — ler DB vars
header "2/6 — Lendo configuração de banco do .env"

# Extrai valores sem vazar no log. Só lemos o que precisamos.
DB_HOST="$(grep -E '^DB_HOST=' .env | tail -1 | cut -d= -f2- | tr -d '"' | tr -d "'" || echo '')"
DB_PORT="$(grep -E '^DB_PORT=' .env | tail -1 | cut -d= -f2- | tr -d '"' | tr -d "'" || echo '')"
DB_DATABASE="$(grep -E '^DB_DATABASE=' .env | tail -1 | cut -d= -f2- | tr -d '"' | tr -d "'" || echo '')"
DB_USERNAME="$(grep -E '^DB_USERNAME=' .env | tail -1 | cut -d= -f2- | tr -d '"' | tr -d "'" || echo '')"
DB_PASSWORD="$(grep -E '^DB_PASSWORD=' .env | tail -1 | cut -d= -f2- | tr -d '"' | tr -d "'" || echo '')"

: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=5432}"

if [[ -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
  fail "DB_DATABASE ou DB_USERNAME ausentes em .env. Configure antes de rodar."
fi
ok "DB: $DB_USERNAME@$DB_HOST:$DB_PORT/$DB_DATABASE (senha oculta)"

# Senha exposta em argumento posicional seria visível via `ps aux`.
# Solução: passar PGPASSWORD no environment e montar conn string sem senha.
# O driver @modelcontextprotocol/server-postgres lê PGPASSWORD automaticamente.
CONN_STRING="postgresql://${DB_USERNAME}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}"

# 3/6 — claude mcp add
header "3/6 — Registrando MCP postgres no Claude Code"

if claude mcp list 2>/dev/null | grep -q '^postgres'; then
  warn "MCP 'postgres' já registrado. Pulando claude mcp add."
else
  echo "Executando: claude mcp add postgres -- npx -y @modelcontextprotocol/server-postgres <conn-string-oculta>"
  if PGPASSWORD="${DB_PASSWORD}" claude mcp add postgres -- npx -y @modelcontextprotocol/server-postgres "$CONN_STRING"; then
    ok "MCP 'postgres' registrado"
  else
    fail "Falha ao registrar MCP. Verifique se 'claude mcp add' funciona manualmente."
  fi
fi

# 4/6 — allowed-mcps.txt
header "4/6 — Atualizando .claude/allowed-mcps.txt"

ALLOWLIST=".claude/allowed-mcps.txt"
if grep -qxF "postgres" "$ALLOWLIST"; then
  warn "'postgres' já está na allowlist. Pulando."
else
  printf "postgres\n" >> "$ALLOWLIST"
  ok "Entrada 'postgres' adicionada à allowlist"
fi

# 5/6 — bump ADR-0002 para v3 + linha na tabela
header "5/6 — Atualizando ADR-0002 (MCP policy) para v3"

ADR0002="docs/adr/0002-mcp-policy.md"

if grep -q '| `postgres` |' "$ADR0002"; then
  warn "ADR-0002 já menciona 'postgres'. Pulando atualização."
else
  # Bump version number (v2 → v3)
  if grep -q '^\*\*Versão:\*\* 2' "$ADR0002"; then
    sed -i.bak 's/^\*\*Versão:\*\* 2/**Versão:** 3/' "$ADR0002"
    ok "Versão bumpada para 3"
  fi

  # Adiciona linha após a linha do 'codex' na tabela
  python3 - "$ADR0002" <<'PY' || fail "Falha ao inserir linha no ADR-0002 (python3 indisponível?)"
import sys, pathlib
path = pathlib.Path(sys.argv[1])
content = path.read_text(encoding='utf-8')
new_row = "| `postgres` | Introspecção de schema, RLS policies e migrations em PostgreSQL. Usado por data-expert, qa-expert (modo verify) e security-expert (modo security-gate) para validações de LGPD/RLS sem parsing manual de output de psql. Ref: ADR-0012. |\n"
anchor = "| `codex` |"
lines = content.splitlines(keepends=True)
out = []
inserted = False
for line in lines:
    out.append(line)
    if (not inserted) and line.startswith(anchor):
        # Insere após a linha do codex
        out.append(new_row)
        inserted = True
if not inserted:
    print("ERRO: não encontrou linha âncora do codex na tabela.", file=sys.stderr)
    sys.exit(1)
path.write_text(''.join(out), encoding='utf-8')
print("Linha 'postgres' adicionada à tabela de allowlist.")
PY

  rm -f "${ADR0002}.bak"
  ok "ADR-0002 atualizado"
fi

# 6/6 — relock
header "6/6 — Regenerando selos do harness"

echo "O próximo passo exige confirmação interativa."
echo "O script vai pedir você digitar literalmente: RELOCK"
echo "Isso é proteção do harness (CLAUDE.md §9) — está tudo certo."
echo
read -rp "Pressione ENTER para continuar, ou Ctrl+C para abortar: " _

KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh || fail "relock-harness.sh falhou. Investigue antes de rodar de novo."

ok "Selos regenerados"

header "CONCLUÍDO"
cat <<EOF
Próximo passo (automático):
  1. Abra o Claude Code normalmente.
  2. Rode /resume — a sessão vai detectar as mudanças e pedir commit.
  3. Rode /mcp-check para confirmar que 'postgres' aparece ativo.
  4. Rode /sealed-diff para confirmar que os selos estão OK.

Arquivos modificados neste install (ficam prontos para commit na próxima sessão):
  - .claude/settings.json                  (via claude mcp add)
  - .claude/settings.json.sha256           (via relock)
  - .claude/allowed-mcps.txt               (entrada 'postgres')
  - docs/adr/0002-mcp-policy.md            (bump v3 + linha na tabela)
  - docs/incidents/harness-relock-*.md     (log de auditoria do relock)
EOF
