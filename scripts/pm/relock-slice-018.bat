@echo off
REM ============================================================================
REM relock-slice-018.bat
REM
REM Aplica aliases legacy em scripts/merge-slice.sh + relock + commit + push.
REM Voce so precisa: duplo-clique -> digitar RELOCK -> Enter.
REM ============================================================================

echo.
echo ============================================================
echo  Kalibrium V2 - Relock pos-merge Slice 018
echo ============================================================
echo.
echo  Quando o script pedir, digite (sem aspas):   RELOCK
echo.
pause

cd /d "%~dp0\..\.."

REM Chamada direta ao bash (sem -c para evitar quoting issues)
set "KALIB_RELOCK_AUTHORIZED=1"
"C:\Program Files\Git\bin\bash.exe" scripts/pm/relock-slice-018.sh

if errorlevel 1 (
  echo.
  echo ============================================================
  echo  ERRO: relock falhou. Log acima tem os detalhes.
  echo ============================================================
  echo.
  pause
  exit /b 1
)

echo.
echo ============================================================
echo  Relock concluido. Pode fechar esta janela.
echo ============================================================
echo.
pause
