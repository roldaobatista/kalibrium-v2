@echo off
REM tools/apply-b001.bat — one-click applier pro B-001.
REM
REM Faz em sequencia:
REM   1. copy /Y scripts\drafts\post-edit-gate.sh scripts\hooks\post-edit-gate.sh
REM      (nao bloqueado pelo harness porque este .bat roda no cmd do Windows,
REM       fora do Claude Code — sealed-files-bash-lock so age dentro do agente)
REM   2. chama tools\relock.bat (que abre Git Bash interativo com TTY real)
REM
REM O PM so precisa:
REM   - duplo-clique neste arquivo
REM   - quando o wrapper pedir descricao: colar/digitar a linha sugerida abaixo
REM   - quando relock-harness.sh pedir "RELOCK": digitar RELOCK e apertar Enter
REM
REM Apos uso, este .bat pode ser deletado (e um one-shot do Bloco 0).

setlocal

set "TOOLS_DIR=%~dp0"
set "REPO_ROOT=%TOOLS_DIR%.."
pushd "%REPO_ROOT%"

echo.
echo ===============================================================
echo  B-001 one-click applier
echo ===============================================================
echo.
echo  Passo 1: copiando draft por cima do hook selado
echo.

if not exist "scripts\drafts\post-edit-gate.sh" (
  echo  ERRO: scripts\drafts\post-edit-gate.sh nao encontrado.
  echo  Esta rodando na pasta errada?
  pause
  popd
  endlocal
  exit /b 1
)

copy /Y "scripts\drafts\post-edit-gate.sh" "scripts\hooks\post-edit-gate.sh" > nul
if errorlevel 1 (
  echo  ERRO: falha ao copiar o arquivo.
  pause
  popd
  endlocal
  exit /b 1
)

echo  Copia OK. scripts\hooks\post-edit-gate.sh atualizado.
echo.
echo ===============================================================
echo  Passo 2: relock (precisa de Git Bash + digitacao interativa)
echo ===============================================================
echo.
echo  Quando o wrapper pedir DESCRICAO, cole ou digite:
echo.
echo    B-001 - post-edit-gate com comandos Laravel (Pint + Larastan + Pest)
echo.
echo  Quando relock-harness.sh pedir "RELOCK", digite RELOCK (maiusculo)
echo  e aperte Enter.
echo.
echo  Abrindo Git Bash em 3 segundos...
echo.
timeout /t 3 /nobreak > nul

call "%TOOLS_DIR%relock.bat"

popd
endlocal
