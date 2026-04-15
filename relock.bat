@echo off
setlocal EnableDelayedExpansion
chcp 65001 > nul

cd /d "%~dp0"

echo.
echo ============================================================
echo   Kalibrium V2 - Relock do harness (operacao do PM)
echo ============================================================
echo.
echo Esta operacao atualiza os hashes selados de:
echo   - .claude/settings.json        (hook de configuracao)
echo   - scripts/hooks/MANIFEST.sha256 (hooks de enforcement)
echo   - .claude/allowed-git-identities.txt
echo   - .claude/git-identity-baseline
echo.
echo Ela sera registrada em docs/incidents/harness-relock-*.md
echo com motivo declarado.
echo.
echo Antes de continuar, confirme que voce editou manualmente o
echo arquivo selado necessario (ex: .claude/settings.json) em um
echo editor de texto. Este .bat apenas re-hasheia e sela.
echo.
echo ============================================================
echo.

set /p CONFIRM="Digite RELOCK (em maiusculas) para confirmar: "

if /i not "!CONFIRM!"=="RELOCK" (
    echo.
    echo [X] Operacao cancelada. Nenhuma alteracao foi aplicada.
    echo.
    pause
    exit /b 1
)

echo.
echo [OK] Confirmacao recebida. Iniciando relock...
echo.

set KALIB_RELOCK_AUTHORIZED=1

where bash > nul 2>&1
if errorlevel 1 (
    echo [X] Git Bash nao encontrado no PATH.
    echo     Instale o Git for Windows: https://git-scm.com/downloads
    echo.
    pause
    exit /b 2
)

bash scripts/relock-harness.sh

set RELOCK_EXIT=%errorlevel%

echo.
echo ============================================================
if !RELOCK_EXIT! EQU 0 (
    echo   [OK] Relock concluido com sucesso.
    echo   Proximo passo: git status ^& git add ^& git commit
) else (
    echo   [X] Relock falhou com codigo !RELOCK_EXIT!.
    echo   Verifique as mensagens acima e o arquivo de incidente
    echo   mais recente em docs/incidents/harness-relock-*.md
)
echo ============================================================
echo.

pause
exit /b !RELOCK_EXIT!
