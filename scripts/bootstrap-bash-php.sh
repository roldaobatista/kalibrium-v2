#!/usr/bin/env bash
# Resolve PHP for Bash processes started from Windows shells.

kalib_normalize_php_path() {
  local php_path="$1"

  php_path="${php_path//$'\r'/}"
  php_path="${php_path//\\//}"

  printf '%s\n' "$php_path"
}

kalib_detect_php_bin() {
  if [ -n "${PHP_BIN:-}" ]; then
    kalib_normalize_php_path "$PHP_BIN"
    return 0
  fi

  if command -v php >/dev/null 2>&1; then
    command -v php
    return 0
  fi

  if command -v php.exe >/dev/null 2>&1; then
    command -v php.exe
    return 0
  fi

  if command -v where.exe >/dev/null 2>&1; then
    where.exe php 2>/dev/null | head -1 | tr -d '\r' | sed 's#\\#/#g'
    return 0
  fi

  return 1
}

kalib_prepend_path_once() {
  local path_entry="$1"

  case ":$PATH:" in
    *":$path_entry:"*) ;;
    *) export PATH="$path_entry:$PATH" ;;
  esac
}

kalib_php_path_arg() {
  local arg="$1"
  local drive
  local rest

  case "$arg" in
    /mnt/[A-Za-z]/*)
      drive="${arg#/mnt/}"
      drive="${drive%%/*}"
      rest="${arg#/mnt/$drive/}"
      printf '%s:/%s\n' "${drive^^}" "$rest"
      ;;
    /[A-Za-z]/*)
      drive="${arg:1:1}"
      rest="${arg:3}"
      printf '%s:/%s\n' "${drive^^}" "$rest"
      ;;
    *)
      printf '%s\n' "$arg"
      ;;
  esac
}

kalib_path_for_php() {
  case "${PHP_BIN:-}" in
    *.exe)
      kalib_php_path_arg "$1"
      ;;
    *)
      printf '%s\n' "$1"
      ;;
  esac
}

kalib_bootstrap_bash_php() {
  local php_bin
  php_bin="$(kalib_detect_php_bin || true)"

  if [ -z "$php_bin" ]; then
    return 1
  fi

  export PHP_BIN="$php_bin"

  if command -v php >/dev/null 2>&1; then
    return 0
  fi

  local wrapper_dir="${TMPDIR:-/tmp}/kalibrium-bin"
  local wrapper_path="$wrapper_dir/php"

  mkdir -p "$wrapper_dir"
  {
    printf '#!/usr/bin/env bash\n'
    printf 'target=%q\n' "$php_bin"
    printf 'args=()\n'
    printf 'for arg in "$@"; do\n'
    printf '  case "$arg" in\n'
    printf '    --*=/mnt/[A-Za-z]/*)\n'
    printf '      prefix="${arg%%%%=*}"\n'
    printf '      value="${arg#*=}"\n'
    printf '      drive="${value#/mnt/}"\n'
    printf '      drive="${drive%%%%/*}"\n'
    printf '      rest="${value#/mnt/$drive/}"\n'
    printf '      args+=("$prefix=${drive^^}:/$rest")\n'
    printf '      ;;\n'
    printf '    --*=/[A-Za-z]/*)\n'
    printf '      prefix="${arg%%%%=*}"\n'
    printf '      value="${arg#*=}"\n'
    printf '      drive="${value:1:1}"\n'
    printf '      rest="${value:3}"\n'
    printf '      args+=("$prefix=${drive^^}:/$rest")\n'
    printf '      ;;\n'
    printf '    /mnt/[A-Za-z]/*)\n'
    printf '      drive="${arg#/mnt/}"\n'
    printf '      drive="${drive%%%%/*}"\n'
    printf '      rest="${arg#/mnt/$drive/}"\n'
    printf '      args+=("${drive^^}:/$rest")\n'
    printf '      ;;\n'
    printf '    /[A-Za-z]/*)\n'
    printf '      drive="${arg:1:1}"\n'
    printf '      rest="${arg:3}"\n'
    printf '      args+=("${drive^^}:/$rest")\n'
    printf '      ;;\n'
    printf '    *)\n'
    printf '      args+=("$arg")\n'
    printf '      ;;\n'
    printf '  esac\n'
    printf 'done\n'
    printf 'exec "$target" "${args[@]}"\n'
  } > "$wrapper_path"
  chmod +x "$wrapper_path"

  kalib_prepend_path_once "$wrapper_dir"
}

kalib_bootstrap_bash_php
