#!/usr/bin/env bash
# hook-cache.sh — módulo de cache para hooks pesados (G-15).
#
# Evita re-executar lint/typecheck/tests em arquivos que não mudaram
# desde a última validação. Usado como source pelo post-edit-gate.sh.
#
# Uso em hooks:
#   source "$SCRIPT_DIR/../drafts/hook-cache.sh"
#   if is_cached "$file" "lint"; then
#     say "lint cache hit para $file — pulando"
#   else
#     run_lint "$file"
#     cache_result "$file" "lint"
#   fi

set -euo pipefail

CACHE_DIR="${REPO_ROOT:-.}/.claude/.hook-cache"

# Inicializa diretório de cache (não selado — efêmero)
init_cache() {
  mkdir -p "$CACHE_DIR"
}

# Calcula hash do arquivo (conteúdo + mtime)
_file_hash() {
  local file="$1"
  if [ ! -f "$file" ]; then
    echo "missing"
    return
  fi
  # Usa SHA256 do conteúdo (mtime pode mudar sem edição real em Windows)
  sha256sum "$file" 2>/dev/null | cut -d' ' -f1 || echo "error"
}

# Verifica se resultado está em cache
# Retorna 0 (true) se cache hit, 1 se miss
is_cached() {
  local file="$1"
  local stage="$2"  # lint, typecheck, test, format
  local cache_file="$CACHE_DIR/${stage}.cache"

  [ ! -f "$cache_file" ] && return 1

  local current_hash
  current_hash="$(_file_hash "$file")"
  local cached_hash
  cached_hash="$(grep -F "$file" "$cache_file" 2>/dev/null | cut -d'|' -f2 || echo "")"

  [ "$current_hash" = "$cached_hash" ]
}

# Grava resultado no cache
cache_result() {
  local file="$1"
  local stage="$2"
  local cache_file="$CACHE_DIR/${stage}.cache"

  init_cache

  local current_hash
  current_hash="$(_file_hash "$file")"

  # Remove entrada antiga se existir
  if [ -f "$cache_file" ]; then
    grep -vF "$file" "$cache_file" > "$cache_file.tmp" 2>/dev/null || true
    mv "$cache_file.tmp" "$cache_file"
  fi

  # Adiciona nova entrada
  echo "${file}|${current_hash}|$(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$cache_file"
}

# Invalida cache de um arquivo específico (chamado quando edit/write acontece)
invalidate_cache() {
  local file="$1"
  for cache_file in "$CACHE_DIR"/*.cache; do
    [ ! -f "$cache_file" ] && continue
    grep -vF "$file" "$cache_file" > "$cache_file.tmp" 2>/dev/null || true
    mv "$cache_file.tmp" "$cache_file"
  done
}

# Limpa todo o cache (chamado no início de sessão ou após merge)
clear_cache() {
  rm -rf "$CACHE_DIR"
  init_cache
}

# Estatísticas de cache (para debug/telemetria)
cache_stats() {
  local hits=0
  local misses=0
  local total_entries=0

  for cache_file in "$CACHE_DIR"/*.cache; do
    [ ! -f "$cache_file" ] && continue
    local count
    count="$(wc -l < "$cache_file")"
    total_entries=$((total_entries + count))
  done

  echo "cache_entries=$total_entries cache_dir=$CACHE_DIR"
}
