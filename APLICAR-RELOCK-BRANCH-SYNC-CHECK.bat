@echo off
setlocal
title Aplicar Relock — Hook branch-sync-check (Kalibrium, B-031)
cd /d "%~dp0"

echo.
echo =====================================================
echo  APLICAR RELOCK — Hook branch-sync-check (B-031)
echo =====================================================
echo.
echo Este script vai:
echo   1. Mover scripts/staging/branch-sync-check.sh
echo      para scripts/hooks/branch-sync-check.sh (area selada).
echo   2. Dar permissao de execucao (chmod +x).
echo   3. Adicionar invocacao no session-start.sh (nao-bloqueante,
echo      apenas aviso quando a branch esta ^> 10 commits atras de main).
echo   4. Criar backup de session-start.sh antes de editar.
echo   5. Regenerar os selos SHA-256 via scripts/relock-harness.sh.
echo   6. Registrar incidente auditavel em docs/incidents/.
echo.
echo IMPORTANTE:
echo   - Voce vai precisar digitar RELOCK (em maiusculas) uma vez.
echo   - Este script e IDEMPOTENTE — rodar de novo nao quebra nada.
echo   - Se falhar, o backup de session-start.sh fica em *.bak-^<timestamp^>.
echo.
echo Debito fechado: B-031 em docs/guide-backlog.md
echo Referencia: docs/operations/relock-branch-sync-check.md
echo.
echo =====================================================
echo Pressione qualquer tecla para CONTINUAR ou Ctrl+C para CANCELAR.
echo =====================================================
pause >nul

echo.
echo Definindo KALIB_RELOCK_AUTHORIZED=1...
set KALIB_RELOCK_AUTHORIZED=1

echo Chamando bash para aplicar patch + relock...
echo.

bash scripts/aplicar-relock-branch-sync-check.sh
set EXIT_CODE=%errorlevel%

echo.
if %EXIT_CODE% EQU 0 (
  echo =====================================================
  echo  RELOCK CONCLUIDO COM SUCESSO
  echo =====================================================
  echo.
  echo Proximos passos:
  echo   1. git add scripts/hooks/branch-sync-check.sh scripts/hooks/session-start.sh ^
scripts/hooks/MANIFEST.sha256 docs/incidents/harness-relock-*.md
  echo   2. git commit -m "chore^(harness^): ativar branch-sync-check ^(fecha B-031^)"
  echo   3. git push
  echo   4. Volte ao Claude Code — SessionStart vai validar os selos.
  echo.
  echo O arquivo scripts/staging/branch-sync-check.sh foi removido
  echo automaticamente — a area staging fica limpa.
) else (
  echo =====================================================
  echo  RELOCK FALHOU - codigo de saida %EXIT_CODE%
  echo =====================================================
  echo.
  echo O que fazer:
  echo   - Leia a mensagem de erro acima.
  echo   - Backup de session-start.sh preservado em scripts/hooks/session-start.sh.bak-*.
  echo   - Para reverter: renomeie o .bak-* de volta para session-start.sh
  echo     e rode scripts/relock-harness.sh novamente.
  echo   - Se o erro mencionar "TTY" ou "stdin", feche e reabra este .bat
  echo     a partir de um duplo-clique no Explorador do Windows.
)
echo.
echo Pressione qualquer tecla para fechar esta janela.
pause >nul
endlocal
