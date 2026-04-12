@echo off
REM tools/relock.bat — atalho 1-click pro wrapper de relock (B-020).
REM
REM Uso: duplo-clique neste arquivo (ou rode `tools\relock.bat` no cmd).
REM Abre Git Bash em janela interativa, roda scripts\relock-and-commit.sh,
REM e mantem a janela aberta no final pro PM ler o output.
REM
REM Por que precisa abrir Git Bash novo:
REM   - O wrapper precisa de TTY real (camada 2 do relock-harness.sh).
REM   - A ferramenta Bash do Claude Code NAO conecta TTY real, entao esta
REM     rota e a unica que funciona pra executar relock.
REM
REM Requisitos:
REM   - Git for Windows instalado (Git Bash)
REM   - Arquivos selados ja editados manualmente ANTES de rodar isto

setlocal

REM Detecta o diretorio deste .bat e sobe 1 nivel (repo root)
set "TOOLS_DIR=%~dp0"
set "REPO_ROOT=%TOOLS_DIR%.."
pushd "%REPO_ROOT%"

REM Tenta caminhos conhecidos do Git Bash em ordem
set "GIT_BASH="
if exist "C:\Program Files\Git\bin\bash.exe" set "GIT_BASH=C:\Program Files\Git\bin\bash.exe"
if exist "C:\Program Files (x86)\Git\bin\bash.exe" set "GIT_BASH=C:\Program Files (x86)\Git\bin\bash.exe"
if exist "%LOCALAPPDATA%\Programs\Git\bin\bash.exe" set "GIT_BASH=%LOCALAPPDATA%\Programs\Git\bin\bash.exe"

if "%GIT_BASH%"=="" (
  echo.
  echo [relock.bat] ERRO: Git Bash nao encontrado nos caminhos padrao.
  echo             Instale Git for Windows ou edite este arquivo com o caminho correto.
  echo.
  pause
  popd
  endlocal
  exit /b 1
)

echo [relock.bat] repo: %REPO_ROOT%
echo [relock.bat] bash: %GIT_BASH%
echo [relock.bat] chamando scripts\relock-and-commit.sh...
echo.

REM -l = login shell (carrega /etc/profile pra PATH completo)
REM -i = interactive (necessario para TTY em read)
"%GIT_BASH%" -l -i -c "bash scripts/relock-and-commit.sh; echo; echo '--- relock finalizado ---'; read -p 'Pressione Enter para fechar...'"

popd
endlocal
