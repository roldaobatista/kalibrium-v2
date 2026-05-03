@echo off
setlocal EnableDelayedExpansion

rem ============================================================
rem  RESET DE HARNESS - Cenario A (manter codigo + docs de produto)
rem ============================================================

echo.
echo ============================================================
echo  RESET DE HARNESS - Kalibrium V2
echo ============================================================
echo.
echo  Este script vai APAGAR PERMANENTEMENTE:
echo.
echo   - .claude\agents\               (12 agentes)
echo   - .claude\skills\               (40+ skills)
echo   - .claude\telemetry\            (telemetria)
echo   - .claude\settings.json + selos
echo   - .claude\allowed-mcps.txt
echo   - scripts\hooks\                (todos os hooks)
echo   - docs\protocol\                (protocolo v1.2.4)
echo   - docs\constitution.md
echo   - docs\documentation-requirements.md
echo   - docs\handoffs\
echo   - docs\retrospectives\
echo   - docs\incidents\
echo   - docs\audits\
echo   - Memoria PM em %%USERPROFILE%%\.claude\projects\C--PROJETOS-saas-kalibrium-v2\memory\
echo.
echo  Backup ja criado:
echo   - tag git: pre-harness-reset-2026-05-02
echo   - branch git: archive/harness-v3-completo
echo.
echo  Para reverter depois:
echo    git checkout pre-harness-reset-2026-05-02
echo.
echo ============================================================
echo.

set /p CONFIRM=Digite RESET para confirmar:
if /I not "%CONFIRM%"=="RESET" (
    echo.
    echo Cancelado. Nada foi apagado.
    pause
    exit /b 1
)

echo.
echo Iniciando reset...
echo.

cd /d "C:\PROJETOS\saas\kalibrium-v2"
if errorlevel 1 (
    echo ERRO: pasta nao encontrada.
    pause
    exit /b 2
)

echo [1/8] Apagando .claude\agents\, skills\, telemetry\ ...
if exist ".claude\agents" rmdir /s /q ".claude\agents"
if exist ".claude\skills" rmdir /s /q ".claude\skills"
if exist ".claude\telemetry" rmdir /s /q ".claude\telemetry"
if exist ".claude\snapshots" rmdir /s /q ".claude\snapshots"
if exist ".claude\worktrees" rmdir /s /q ".claude\worktrees"
if exist ".claude\review-input" rmdir /s /q ".claude\review-input"
if exist ".claude\commands" rmdir /s /q ".claude\commands"

echo [2/8] Apagando selos do harness ...
if exist ".claude\settings.json" del /q ".claude\settings.json"
if exist ".claude\settings.json.sha256" del /q ".claude\settings.json.sha256"
if exist ".claude\settings.local.json" del /q ".claude\settings.local.json"
if exist ".claude\allowed-git-identities.txt" del /q ".claude\allowed-git-identities.txt"
if exist ".claude\git-identity-baseline" del /q ".claude\git-identity-baseline"
if exist ".claude\allowed-mcps.txt" del /q ".claude\allowed-mcps.txt"
if exist ".claude\scheduled_tasks.lock" del /q ".claude\scheduled_tasks.lock"

echo [3/8] Apagando scripts\hooks\ ...
if exist "scripts\hooks" rmdir /s /q "scripts\hooks"

echo [4/8] Apagando docs\protocol\, constitution.md, documentation-requirements.md ...
if exist "docs\protocol" rmdir /s /q "docs\protocol"
if exist "docs\constitution.md" del /q "docs\constitution.md"
if exist "docs\documentation-requirements.md" del /q "docs\documentation-requirements.md"

echo [5/8] Apagando handoffs, retrospectives, incidents, audits ...
if exist "docs\handoffs" rmdir /s /q "docs\handoffs"
if exist "docs\retrospectives" rmdir /s /q "docs\retrospectives"
if exist "docs\incidents" rmdir /s /q "docs\incidents"
if exist "docs\audits" rmdir /s /q "docs\audits"
if exist "docs\agents-redesign" rmdir /s /q "docs\agents-redesign"
if exist "docs\governance" rmdir /s /q "docs\governance"
if exist "docs\harness-limitations.md" del /q "docs\harness-limitations.md"
if exist "docs\guide-backlog.md" del /q "docs\guide-backlog.md"
if exist "docs\agents-elite-profiles.md" del /q "docs\agents-elite-profiles.md"

echo [6/8] Apagando scripts auxiliares do harness antigo ...
if exist "scripts\relock-harness.sh" del /q "scripts\relock-harness.sh"
if exist "scripts\pm\relock-harness.bat" del /q "scripts\pm\relock-harness.bat"
if exist "scripts\add-codex-permissions.bat" del /q "scripts\add-codex-permissions.bat"
if exist "scripts\relock-codex.bat" del /q "scripts\relock-codex.bat"
if exist "scripts\translate-pm.sh" del /q "scripts\translate-pm.sh"
if exist "scripts\record-telemetry.sh" del /q "scripts\record-telemetry.sh"
if exist "scripts\sequencing-check.sh" del /q "scripts\sequencing-check.sh"
if exist "scripts\merge-slice.sh" del /q "scripts\merge-slice.sh"
if exist "scripts\merge-slice.sh.pre-slice-018-relock.bak" del /q "scripts\merge-slice.sh.pre-slice-018-relock.bak"
if exist "scripts\draft-spec.sh" del /q "scripts\draft-spec.sh"
if exist "scripts\pm\context-monitor" rmdir /s /q "scripts\pm\context-monitor"
if exist "scripts\pm-dashboard.ps1" del /q "scripts\pm-dashboard.ps1"

echo [7/8] Apagando memoria do PM ...
if exist "%USERPROFILE%\.claude\projects\C--PROJETOS-saas-kalibrium-v2\memory" (
    rmdir /s /q "%USERPROFILE%\.claude\projects\C--PROJETOS-saas-kalibrium-v2\memory"
)

echo [8/8] Apagando project-state.json e CLAUDE.md (serao recriados) ...
if exist "project-state.json" del /q "project-state.json"
if exist "CLAUDE.md" del /q "CLAUDE.md"

echo.
echo ============================================================
echo  RESET CONCLUIDO
echo ============================================================
echo.
echo  Proximos passos:
echo   1. ENCERRAR esta sessao Claude Code (fechar a janela).
echo   2. ABRIR uma sessao NOVA do Claude Code neste projeto.
echo   3. Pedir ao agente: "finalize o reset"
echo.
echo  Em caso de arrependimento:
echo    git checkout pre-harness-reset-2026-05-02
echo.
echo ============================================================
echo.
pause
exit /b 0
