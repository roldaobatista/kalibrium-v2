@echo off
setlocal
title Aplicar Relock ADR-0017 + ADR-0019 (Kalibrium)
cd /d "%~dp0"

echo.
echo =====================================================
echo  APLICAR RELOCK - ADR-0017 + ADR-0019
echo =====================================================
echo.
echo Este script vai:
echo   1. Criar backup dos 3 hooks selados
echo   2. Aplicar patches de enforcement mecanico:
echo      - session-start.sh   (reconcile project-state vs git)
echo      - pre-commit-gate.sh (bloqueio de commit harness sem aprovacao)
echo      - merge-slice.sh     (validacao referenced_artifacts no master-audit)
echo   3. Regenerar os selos SHA-256 via relock-harness.sh
echo   4. Registrar incidente auditavel em docs/incidents/
echo.
echo IMPORTANTE:
echo   - Voce vai precisar digitar RELOCK (em maiusculas) uma vez.
echo   - Este script e IDEMPOTENTE — rodar de novo nao quebra nada.
echo   - Se falhar, os backups ficam em *.bak-^<timestamp^>.
echo.
echo =====================================================
echo Pressione qualquer tecla para CONTINUAR ou Ctrl+C para CANCELAR.
echo =====================================================
pause >nul

echo.
echo Definindo KALIB_RELOCK_AUTHORIZED=1...
set KALIB_RELOCK_AUTHORIZED=1

echo Chamando bash para aplicar patches + relock...
echo.

bash scripts/aplicar-relock-adr-0017-0019.sh
set EXIT_CODE=%errorlevel%

echo.
if %EXIT_CODE% EQU 0 (
  echo =====================================================
  echo  RELOCK CONCLUIDO COM SUCESSO
  echo =====================================================
  echo.
  echo Proximos passos sugeridos pelo script:
  echo   1. git add . ^(com os arquivos listados acima^)
  echo   2. git commit -m "chore^(harness^): relock ADR-0017 + ADR-0019"
  echo   3. Volte ao Claude Code — SessionStart vai validar.
) else (
  echo =====================================================
  echo  RELOCK FALHOU - codigo de saida %EXIT_CODE%
  echo =====================================================
  echo.
  echo O que fazer:
  echo   - Leia a mensagem de erro acima
  echo   - Backups estao preservados em scripts/hooks/*.bak-* e scripts/merge-slice.sh.bak-*
  echo   - Para reverter: renomeie os .bak-* de volta
  echo   - Se o erro mencionar "TTY" ou "stdin", feche e reabra este .bat
)
echo.
echo Pressione qualquer tecla para fechar esta janela.
pause >nul
endlocal
