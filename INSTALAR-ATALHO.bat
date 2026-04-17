@echo off
REM ============================================================
REM Kalibrium V2 - Instalador do atalho "Kalibrium PM"
REM
REM Clique 2x neste arquivo UMA VEZ.
REM Ele cria um atalho "Kalibrium PM" na sua Area de Trabalho.
REM
REM Depois, use o atalho da Area de Trabalho para abrir o
REM ambiente de trabalho (Claude Code + Dashboard).
REM ============================================================

echo.
echo ============================================================
echo   INSTALADOR - Atalho "Kalibrium PM"
echo ============================================================
echo.
echo Este instalador cria um atalho na sua Area de Trabalho.
echo Ao clicar nele, abre o Windows Terminal com:
echo   - 60%% esquerda: Claude Code (onde voce conversa)
echo   - 40%% direita:  Dashboard do PM (status do projeto)
echo.
echo Pressione qualquer tecla para continuar ou feche a janela para cancelar.
pause >nul

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\install-desktop-shortcut.ps1"

echo.
echo Pressione qualquer tecla para fechar...
pause >nul
