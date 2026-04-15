@echo off
chcp 65001 >nul
setlocal

echo.
echo ============================================================
echo   Instalar MCP do PostgreSQL no Claude Code
echo   (ADR-0012 — decisao aprovada 2026-04-15)
echo ============================================================
echo.
echo O que este instalador faz:
echo.
echo   1. Registra o MCP oficial Postgres da Anthropic
echo      (@modelcontextprotocol/server-postgres)
echo.
echo   2. Le as credenciais do seu .env local para conectar
echo      no banco de desenvolvimento.
echo.
echo   3. Atualiza a allowlist de MCPs e o ADR-0002.
echo.
echo   4. Regenera os selos do harness (CLAUDE.md §9).
echo.
echo IMPORTANTE — antes de continuar:
echo.
echo   [ ] Feche o Claude Code (digite /exit na sessao atual).
echo.
echo   [ ] Confirme que seu .env tem DB_HOST, DB_PORT, DB_DATABASE,
echo       DB_USERNAME e DB_PASSWORD configurados.
echo.
echo   [ ] Durante a execucao, o script vai pedir voce digitar
echo       a palavra RELOCK em maiusculas. Isso e protecao do
echo       harness — esta tudo certo, pode digitar.
echo.
echo ============================================================
echo.
pause
echo.
echo ============================================================
echo   Executando...
echo ============================================================
echo.

cd /d "%~dp0"
bash scripts/install-postgres-mcp.sh

set EXITCODE=%ERRORLEVEL%

echo.
echo ============================================================
if %EXITCODE%==0 (
  echo   SUCESSO. Voce pode abrir o Claude Code agora.
  echo   Rode /resume para continuar de onde parou.
) else (
  echo   FALHOU com codigo %EXITCODE%.
  echo   Leia as mensagens acima para entender o que aconteceu.
  echo   Em caso de duvida, copie o output e mostre ao Claude
  echo   na proxima sessao para diagnostico.
)
echo ============================================================
echo.
pause

endlocal
exit /b %EXITCODE%
