@echo off
REM ============================================================================
REM relock-apos-auditoria.bat
REM
REM Pre-requisito 7 da decisao pos re-auditoria dual-LLM 2026-04-15.
REM
REM Este .bat abre um terminal novo e executa scripts/relock-harness.sh que:
REM   1. Recalcula hashes dos arquivos selados (settings.json + MANIFEST.sha256)
REM   2. Pede para voce digitar "RELOCK" para confirmar
REM   3. Cria arquivo de incidente automatico em docs/incidents/harness-relock-*.md
REM
REM Voce nao precisa entender os detalhes. So:
REM   1. Duplo-clique neste arquivo
REM   2. Quando pedir, digite:   RELOCK
REM   3. Aperte Enter
REM
REM Se der erro, me mande print/log e eu analiso.
REM ============================================================================

echo.
echo ============================================================
echo  Kalibrium V2 - Relock pos re-auditoria dual-LLM
echo ============================================================
echo.
echo  Este script vai recalcular os selos de seguranca apos as
echo  alteracoes feitas em CLAUDE.md, orchestrator.md e
echo  constitution.md no PR #16.
echo.
echo  Quando o script pedir, digite (sem aspas):   RELOCK
echo.
echo  Apos concluir, feche esta janela.
echo.
pause

cd /d "%~dp0"

set "KALIB_RELOCK_AUTHORIZED=1"

REM Usa Git Bash (padrao Windows com Git for Windows instalado)
"C:\Program Files\Git\bin\bash.exe" -c "KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh"

if errorlevel 1 (
  echo.
  echo ============================================================
  echo  ERRO: relock falhou. Log acima tem os detalhes.
  echo  Copie a mensagem de erro e me mande.
  echo ============================================================
  echo.
  pause
  exit /b 1
)

echo.
echo ============================================================
echo  Relock concluido com sucesso.
echo  Um arquivo de incidente foi criado em docs/incidents/.
echo.
echo  Proximo passo: commitar a mudanca dos selos.
echo  Me avise que eu faco o commit + push.
echo ============================================================
echo.
pause
