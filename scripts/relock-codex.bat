@echo off
chcp 65001 >nul
setlocal

:: Garante que estamos na raiz do projeto independente de onde o .bat foi chamado
cd /d "%~dp0.."

echo.
echo ============================================================
echo  REGENERAR SELOS DE SEGURANCA DO HARNESS (relock)
echo ============================================================
echo.
echo  Este script vai abrir o Git Bash para regenerar os hashes
echo  SHA256 do harness apos a edicao do settings.json.
echo.
echo  INSTRUCAO: quando o script perguntar, DIGITE exatamente:
echo.
echo    RELOCK
echo.
echo  (em maiusculas) e pressione Enter.
echo.
pause

:: ----------------------------------------------------------------
:: Localizar Git Bash
:: ----------------------------------------------------------------
set "GITBASH="
if exist "C:\Program Files\Git\bin\bash.exe"       set "GITBASH=C:\Program Files\Git\bin\bash.exe"
if exist "C:\Program Files (x86)\Git\bin\bash.exe" set "GITBASH=C:\Program Files (x86)\Git\bin\bash.exe"

if "%GITBASH%"=="" (
    echo.
    echo [ERRO] Git Bash nao encontrado.
    echo        Instale o Git para Windows: https://git-scm.com/download/win
    echo.
    pause
    exit /b 1
)

:: ----------------------------------------------------------------
:: Rodar relock-harness.sh em terminal Git Bash interativo
:: O /WAIT faz este .bat aguardar o Git Bash fechar antes de continuar
:: ----------------------------------------------------------------
echo.
echo Abrindo Git Bash para rodar relock-harness.sh ...
echo (Uma nova janela vai abrir -- siga as instrucoes nela)
echo.

start /WAIT "" "%GITBASH%" --login -c "cd '/c/PROJETOS/saas/kalibrium-v2' && KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh; echo; echo Pressione Enter para fechar...; read"

:: ----------------------------------------------------------------
:: Verificar resultado
:: ----------------------------------------------------------------
if not exist ".claude\settings.json.sha256" (
    echo.
    echo [AVISO] Arquivo .claude\settings.json.sha256 nao encontrado.
    echo         O relock pode nao ter sido concluido corretamente.
    echo         Tente novamente ou chame o agente para investigar.
    echo.
    pause
    exit /b 1
)

echo.
echo ============================================================
echo  Relock concluido!
echo.
echo  PROXIMO PASSO: informe ao agente Claude Code para commitar:
echo    - .claude/settings.json
echo    - .claude/settings.json.sha256
echo    - scripts/hooks/MANIFEST.sha256
echo    - docs/incidents/harness-relock-*.md
echo ============================================================
echo.
pause
endlocal
