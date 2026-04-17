@echo off
REM ============================================================
REM Kalibrium V2 - Abre area de trabalho do PM
REM Layout: 60%% Claude Code (esquerda) + 40%% Dashboard (direita)
REM ============================================================

set PROJECT_DIR=C:\PROJETOS\saas\kalibrium-v2

REM Abre Windows Terminal com split vertical 60/40
REM -d = diretorio inicial
REM split-pane -V --size 0.4 = painel vertical, o novo ocupa 40%% (direita)

start "" wt.exe ^
  new-tab --title "Kalibrium PM" -d "%PROJECT_DIR%" claude.exe ^
  ; split-pane -V --size 0.4 -d "%PROJECT_DIR%" powershell.exe -NoExit -ExecutionPolicy Bypass -File "%PROJECT_DIR%\scripts\pm-dashboard.ps1"

exit /b 0
