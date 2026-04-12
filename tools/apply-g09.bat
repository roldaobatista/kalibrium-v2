@echo off
REM tools/apply-g09.bat — one-click applier pro G-09 (session-start.sh com estado do slice).
REM
REM Mesmo padrao do apply-b001.bat — o copy do draft por cima do hook selado
REM NAO e bloqueado aqui porque este .bat roda no cmd do Windows, fora do
REM Claude Code (sealed-files-bash-lock so age dentro do agente).
REM
REM O PM so precisa:
REM   - duplo-clique neste arquivo
REM   - quando o wrapper pedir descricao: colar/digitar a linha sugerida abaixo
REM   - quando relock-harness.sh pedir "RELOCK": digitar RELOCK e apertar Enter
REM
REM Apos uso, este .bat pode ser deletado.

setlocal

set "TOOLS_DIR=%~dp0"
set "REPO_ROOT=%TOOLS_DIR%.."
pushd "%REPO_ROOT%"

echo.
echo ===============================================================
echo  G-09 one-click applier (session-start.sh com estado do slice)
echo ===============================================================
echo.
echo  Passo 1: copiando draft por cima do hook selado
echo.

if not exist "scripts\drafts\session-start.sh" (
  echo  ERRO: scripts\drafts\session-start.sh nao encontrado.
  echo  Esta rodando na pasta errada?
  pause
  popd
  endlocal
  exit /b 1
)

copy /Y "scripts\drafts\session-start.sh" "scripts\hooks\session-start.sh" > nul
if errorlevel 1 (
  echo  ERRO: falha ao copiar o arquivo.
  pause
  popd
  endlocal
  exit /b 1
)

echo  Copia OK. scripts\hooks\session-start.sh atualizado.
echo.
echo ===============================================================
echo  Passo 2: relock (precisa de Git Bash + digitacao interativa)
echo ===============================================================
echo.
echo  Quando o wrapper pedir DESCRICAO, cole ou digite:
echo.
echo    G-09 - session-start.sh mostra estado do slice ativo
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
