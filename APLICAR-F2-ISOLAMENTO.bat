@echo off
chcp 65001 >nul 2>&1
title Kalibrium V2 - Correcao F2 (Isolamento dos Gates)

echo.
echo ========================================================
echo   KALIBRIUM V2 - Correcao de Arquivo Selado (F2)
echo ========================================================
echo.
echo O que este script faz:
echo.
echo   1. Substitui scripts\hooks\verifier-sandbox.sh pela
echo      versao corrigida que inclui isolamento para:
echo      - security-reviewer
echo      - test-auditor
echo      - functional-reviewer
echo.
echo   2. Regenera os selos de seguranca (MANIFEST + SHA256)
echo.
echo   3. Cria registro de incidente em docs\incidents\
echo.
echo   4. Faz git commit automaticamente
echo.
echo ========================================================
echo.

set /p CONFIRM=Digite AUTORIZO para confirmar (qualquer outra coisa cancela):
if not "%CONFIRM%"=="AUTORIZO" (
    echo.
    echo Cancelado. Nenhuma alteracao foi feita.
    echo.
    pause
    exit /b 1
)

echo.
echo [1/4] Aplicando correcao em verifier-sandbox.sh...

REM Verifica que o arquivo novo existe
if not exist "scripts\staged-fixes\verifier-sandbox.sh.new" (
    echo ERRO: scripts\staged-fixes\verifier-sandbox.sh.new nao encontrado!
    pause
    exit /b 1
)

REM Copia a versao corrigida
copy /Y "scripts\staged-fixes\verifier-sandbox.sh.new" "scripts\hooks\verifier-sandbox.sh" >nul
if errorlevel 1 (
    echo ERRO: Falha ao copiar arquivo!
    pause
    exit /b 1
)
echo    OK - verifier-sandbox.sh atualizado

echo.
echo [2/4] Regenerando selos de seguranca...

REM Regenera settings.json.sha256
bash -c "cd /c/PROJETOS/saas/kalibrium-v2 && sha256sum --text .claude/settings.json | awk '{print $1}' | xargs -I{} printf '%%s  settings.json\n' {} > .claude/settings.json.sha256 && echo '   OK - settings.json.sha256 regenerado'"
if errorlevel 1 (
    echo ERRO: Falha ao regenerar settings.json.sha256!
    pause
    exit /b 1
)

REM Regenera MANIFEST.sha256
bash -c "cd /c/PROJETOS/saas/kalibrium-v2/scripts/hooks && find . -maxdepth 1 -type f -name '*.sh' | sed 's|.*/||' | sort | xargs sha256sum --text > MANIFEST.sha256 && echo '   OK - MANIFEST.sha256 regenerado ('"$(bash -c "wc -l < /c/PROJETOS/saas/kalibrium-v2/scripts/hooks/MANIFEST.sha256 2>/dev/null")"' hooks)'"
if errorlevel 1 (
    echo ERRO: Falha ao regenerar MANIFEST.sha256!
    pause
    exit /b 1
)

echo.
echo [3/4] Criando registro de incidente...

REM Cria incidente via bash (mais facil para datas e formatacao)
bash -c "cd /c/PROJETOS/saas/kalibrium-v2 && TS=$(date -u +%%Y-%%m-%%dT%%H-%%M-%%SZ) && mkdir -p docs/incidents && cat > docs/incidents/harness-relock-f2-${TS:-manual}.md << 'INCEOF'
# Incidente — relock do harness (F2 isolamento gates)

**Data:** $(date -u +%%Y-%%m-%%dT%%H:%%M:%%SZ 2>/dev/null || echo 'manual')
**Operador:** PM via APLICAR-F2-ISOLAMENTO.bat
**Origem:** master-independent-audit-2026-04-12, Finding F2

## Alteracao

Substituicao de \`scripts/hooks/verifier-sandbox.sh\` pela versao que inclui
sandbox para \`security-reviewer\`, \`test-auditor\` e \`functional-reviewer\`.

Versao anterior: sandbox apenas para verifier + reviewer.
Versao nova: sandbox para todos os 5 gates isolados.

## Validacao

Selos regenerados automaticamente pelo script .bat.
INCEOF
echo '   OK - incidente criado'"

echo.
echo [4/4] Fazendo git commit...

bash -c "cd /c/PROJETOS/saas/kalibrium-v2 && git add scripts/hooks/verifier-sandbox.sh scripts/hooks/MANIFEST.sha256 .claude/settings.json.sha256 docs/incidents/harness-relock-f2-*.md scripts/staged-fixes/ && git commit -m 'fix(harness): F2 isolamento real para security-reviewer, test-auditor, functional-reviewer

verifier-sandbox.sh agora aplica sandbox de leitura para todos os 5 gates.
Cada gate so pode ler seu diretorio de input dedicado.
Ref: master-independent-audit-2026-04-12 Finding F2.

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>'"

if errorlevel 1 (
    echo.
    echo AVISO: git commit pode ter falhado. Verifique manualmente.
    echo Voce pode voltar ao Claude Code e pedir para verificar.
) else (
    echo    OK - commit criado
)

echo.
echo ========================================================
echo   CONCLUIDO!
echo.
echo   Voce pode fechar esta janela e voltar ao Claude Code.
echo   Na proxima sessao, peca ao agente para verificar
echo   que tudo esta correto com: /guide-check
echo ========================================================
echo.
pause
