@echo off
REM ============================================================================
REM relock-harness.bat — atalho para PM executar relock seguro
REM
REM O QUE FAZ:
REM   Recalcula os "selos" (hashes SHA-256) dos arquivos criticos do harness:
REM     - .claude/settings.json
REM     - scripts/hooks/*.sh
REM   Chama scripts/relock-harness.sh que tem 4 camadas de salvaguarda e cria
REM   um arquivo de incidente auditavel em docs/incidents/harness-relock-*.md.
REM
REM QUANDO USAR:
REM   Quando o agente avisar que algum arquivo selado foi alterado de verdade
REM   e precisa ser re-lacrado. Evento raro (historicamente 1-3 vezes/mes).
REM
REM COMO USAR:
REM   1. Duplo-clique neste arquivo.
REM   2. Aperte qualquer tecla para comecar.
REM   3. Quando o script pedir, digite exatamente:   RELOCK
REM   4. Aperte Enter.
REM   5. Ao terminar, feche a janela.
REM
REM SUBSTITUI (versao corrigida de):
REM   scripts/pm/relock-apos-auditoria.bat (tinha bug de caminho relativo).
REM
REM Se der erro, copie a mensagem de erro e me avise.
REM ============================================================================

echo.
echo ============================================================
echo  Kalibrium V2 - Relock do harness
echo ============================================================
echo.
echo  Este script recalcula os selos de seguranca dos arquivos
echo  criticos do harness (.claude/settings.json + scripts/hooks/*).
echo.
echo  Quando o script pedir, digite (sem aspas):   RELOCK
echo  Em seguida aperte Enter.
echo.
echo  Ao terminar, feche esta janela.
echo.
pause

REM ----------------------------------------------------------------------------
REM Vai para a raiz do repositorio.
REM Este .bat esta em scripts/pm/, entao subimos 2 niveis.
REM %~dp0 ja termina em barra, por isso nao precisa de separador extra.
REM ----------------------------------------------------------------------------
cd /d "%~dp0..\.."

REM ----------------------------------------------------------------------------
REM Camada 1 das salvaguardas do relock-harness.sh: variavel de autorizacao.
REM Camadas 2 (TTY real) e 3 (digitar RELOCK) sao satisfeitas pela janela
REM do CMD aberta via duplo-clique + input manual do PM.
REM ----------------------------------------------------------------------------
set "KALIB_RELOCK_AUTHORIZED=1"

"C:\Program Files\Git\bin\bash.exe" -c "bash scripts/relock-harness.sh"

if errorlevel 1 (
  echo.
  echo ============================================================
  echo  ERRO: relock falhou. A mensagem acima tem os detalhes.
  echo  Copie o texto do erro e me avise.
  echo ============================================================
  echo.
  pause
  exit /b 1
)

echo.
echo ============================================================
echo  Relock concluido com sucesso.
echo.
echo  Um arquivo de incidente foi criado em:
echo    docs/incidents/harness-relock-^<timestamp^>.md
echo.
echo  Me avise que eu faco o commit + push dos selos novos.
echo ============================================================
echo.
pause
