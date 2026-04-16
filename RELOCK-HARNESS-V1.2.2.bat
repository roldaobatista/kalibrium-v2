@echo off
REM ============================================================
REM  RELOCK HARNESS Kalibrium V2 — protocolo v1.2.2
REM  ----------------------------------------------------------
REM  Aplica o patch v3 no hook verifier-sandbox.sh e regera
REM  os selos criptograficos do harness. Execute apenas quando
REM  autorizado pelo PM e com o Claude Code fechado.
REM ============================================================

setlocal ENABLEDELAYEDEXPANSION
cd /d "%~dp0"

cls
echo.
echo ===============================================================
echo   RELOCK HARNESS KALIBRIUM V2 -- protocolo v1.2.2
echo ===============================================================
echo.
echo Este script vai:
echo.
echo   1. Fazer backup de scripts\hooks\verifier-sandbox.sh
echo   2. Copiar a versao v3 (com nomes canonicos v1.2.2)
echo   3. Validar sintaxe bash do novo hook
echo   4. Rodar relock-harness.sh
echo      -- VAI PEDIR para voce digitar RELOCK (em maiusculas)
echo   5. Preparar commit com as mudancas (voce confirma antes)
echo.
echo IMPORTANTE:
echo   - Feche o Claude Code antes de continuar.
echo   - Requer Git for Windows instalado (Git Bash).
echo   - Qualquer erro pode ser desfeito (backup automatico).
echo.
echo ===============================================================
echo.
set /p CONFIRM="Deseja continuar? (S/N): "

if /i NOT "%CONFIRM%"=="S" (
    echo.
    echo Operacao cancelada pelo usuario.
    pause
    exit /b 0
)

echo.
echo Procurando Git Bash...

set "BASH_EXE="
if exist "C:\Program Files\Git\bin\bash.exe" set "BASH_EXE=C:\Program Files\Git\bin\bash.exe"
if not defined BASH_EXE if exist "C:\Program Files (x86)\Git\bin\bash.exe" set "BASH_EXE=C:\Program Files (x86)\Git\bin\bash.exe"
if not defined BASH_EXE if exist "%LOCALAPPDATA%\Programs\Git\bin\bash.exe" set "BASH_EXE=%LOCALAPPDATA%\Programs\Git\bin\bash.exe"

if not defined BASH_EXE (
    echo.
    echo ERRO: Git Bash nao foi encontrado neste computador.
    echo.
    echo Instale o Git for Windows em: https://git-scm.com/download/win
    echo Depois rode este arquivo novamente.
    echo.
    pause
    exit /b 1
)

echo Git Bash encontrado: %BASH_EXE%
echo.
echo Iniciando o fluxo de relock...
echo ===============================================================
echo.

REM Executa o shell script com -i (interactive) para permitir
REM que o usuario digite RELOCK quando o relock-harness.sh pedir
"%BASH_EXE%" -i -c "cd /c/PROJETOS/saas/kalibrium-v2 && bash scripts/staging/relock-v3-flow.sh"

set EXITCODE=%ERRORLEVEL%

echo.
echo ===============================================================
if %EXITCODE% EQU 0 (
    echo   Concluido com sucesso.
) else (
    echo   Terminou com erro (codigo %EXITCODE%^).
    echo   Verifique as mensagens acima.
    echo   Se o patch foi aplicado mas o relock falhou, o backup
    echo   esta em scripts\hooks\verifier-sandbox.sh.bak-*
)
echo ===============================================================
echo.
pause
exit /b %EXITCODE%
