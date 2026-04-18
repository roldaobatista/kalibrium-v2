@echo off
REM ============================================================================
REM relock-slice-018.bat
REM
REM Abre Git Bash interativo executando relock-slice-018.sh
REM (relock-harness exige TTY real — nao funciona via bash.exe pipe)
REM ============================================================================

echo.
echo ============================================================
echo  Kalibrium V2 - Relock pos-merge Slice 018
echo ============================================================
echo.
echo  Vou abrir o Git Bash numa janela propria.
echo  La voce digita RESET (se pedir) e RELOCK (quando pedir).
echo.
pause

cd /d "%~dp0\..\.."

REM Abre Git Bash com TTY real rodando o script.
REM --login -i garante terminal interativo. Script termina com read para
REM manter a janela aberta.
start "Kalibrium Relock Slice 018" "C:\Program Files\Git\git-bash.exe" --login -i -c "KALIB_RELOCK_AUTHORIZED=1 bash scripts/pm/relock-slice-018.sh; echo; echo '========================================'; echo '  Script terminou.'; echo '========================================'; read -p 'Pressione Enter para fechar a janela... '"

echo.
echo Janela do Git Bash aberta noutra tela.
echo Quando terminar, pode fechar esta janela tambem.
echo.
pause
