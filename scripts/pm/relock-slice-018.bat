@echo off
REM ============================================================================
REM relock-slice-018.bat
REM
REM Atualiza scripts/merge-slice.sh (selado) com aliases legacy (code-review,
REM security, functional) + relock + commit + push automaticamente.
REM
REM Referencia: specs/018/merge-slice-update-manifest.md
REM              docs/incidents/harness-relock-pending-slice-018.md
REM
REM Voce nao precisa editar nada manualmente. So:
REM   1. Duplo-clique neste arquivo
REM   2. Quando pedir, digite:   RELOCK
REM   3. Aperte Enter
REM   4. Aguarde o push em main
REM ============================================================================

echo.
echo ============================================================
echo  Kalibrium V2 - Relock pos-merge Slice 018
echo ============================================================
echo.
echo  Este script vai:
echo    1. Atualizar main local com origin/main
echo    2. Aplicar aliases legacy em scripts/merge-slice.sh
echo    3. Recalcular selos de seguranca (precisa voce digitar RELOCK)
echo    4. Commitar as mudancas
echo    5. Push para origin/main
echo.
echo  Quando o script pedir, digite (sem aspas):   RELOCK
echo.
pause

cd /d "%~dp0\..\.."

set "KALIB_RELOCK_AUTHORIZED=1"

REM Usa Git Bash (padrao Windows com Git for Windows instalado)
"C:\Program Files\Git\bin\bash.exe" -c "KALIB_RELOCK_AUTHORIZED=1 bash scripts/pm/relock-slice-018.sh"

if errorlevel 1 (
  echo.
  echo ============================================================
  echo  ERRO: relock falhou. Log acima tem os detalhes.
  echo  Copie a mensagem de erro e me mande.
  echo ============================================================
  echo.
  pause
  exit /b 1
)

echo.
echo ============================================================
echo  Relock do slice 018 concluido com sucesso.
echo.
echo  Mudancas aplicadas:
echo    - scripts/merge-slice.sh com aliases legacy
echo    - Selos (settings.json.sha256, MANIFEST.sha256) atualizados
echo    - Incident file criado em docs/incidents/
echo    - Commit + push em main
echo.
echo  Proximo passo: iniciar slice-019 (E15-S04) em nova sessao
echo  Claude Code com /start-story E15-S04.
echo ============================================================
echo.
pause
