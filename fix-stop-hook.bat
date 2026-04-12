@echo off
echo ============================================
echo   Correcao do stop-gate.sh (caminho absoluto)
echo ============================================
echo.
echo Vai abrir o Git Bash para executar a correcao.
echo Quando pedir, digite RELOCK e aperte Enter.
echo.
pause

"C:\Program Files\Git\bin\bash.exe" -l -c "cd /c/PROJETOS/saas/kalibrium-v2 && sed -i 's|\"bash scripts/hooks/stop-gate.sh\"|\"bash /c/PROJETOS/saas/kalibrium-v2/scripts/hooks/stop-gate.sh\"|' .claude/settings.json && echo '[OK] settings.json atualizado' && echo '' && KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh && echo '' && echo '[OK] Relock concluido!' && echo '' && git add .claude/settings.json .claude/settings.json.sha256 scripts/hooks/MANIFEST.sha256 docs/incidents/harness-relock-*.md && git commit -m 'fix(harness): caminho absoluto para stop-gate.sh (Windows CWD issue)' && echo '' && echo '================================' && echo '  TUDO PRONTO! Pode fechar.' && echo '================================' && read -p 'Pressione Enter para sair...'"
