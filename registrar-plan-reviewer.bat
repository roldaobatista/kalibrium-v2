@echo off
chcp 65001 >nul
echo.
echo ============================================================
echo   Registrar agente plan-reviewer no Claude Code
echo ============================================================
echo.
echo O arquivo do agente ja existe em:
echo   .claude\agents\plan-reviewer.md
echo.
echo O Claude Code descobre agentes automaticamente da pasta
echo .claude\agents\ — mas so carrega no INICIO da sessao.
echo.
echo PASSOS:
echo.
echo   1. Feche a sessao atual do Claude Code (digite /exit)
echo   2. Abra uma nova sessao do Claude Code
echo   3. O plan-reviewer vai aparecer automaticamente
echo.
echo Para confirmar que funcionou, na nova sessao digite:
echo   "liste os agentes disponiveis"
echo.
echo O plan-reviewer deve aparecer na lista.
echo.
echo ============================================================
echo.
pause
