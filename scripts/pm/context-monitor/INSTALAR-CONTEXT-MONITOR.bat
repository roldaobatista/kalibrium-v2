@echo off
setlocal
title Instalar Context Monitor v1 (statusline colorida por zona)
cd /d "%~dp0"

echo.
echo =====================================================
echo  INSTALAR CONTEXT MONITOR v1
echo =====================================================
echo.
echo Este instalador vai:
echo   1. Fazer backup do ~/.claude/statusline-command.sh atual
echo      em ~/.claude/statusline-command.sh.bak-^<timestamp^>
echo   2. Substituir pelo novo script com colorizacao por zona:
echo        VERDE    0-60%% usado   (trabalhar normal)
echo        AMARELO  60-80%% usado  (checkpoint no proximo marco)
echo        LARANJA  80-90%% usado  (checkpoint agora)
echo        VERMELHO 90%%+  usado   (resetar imediatamente)
echo   3. Testar que o script nao tem erro de sintaxe.
echo.
echo IMPORTANTE:
echo   - Este script NAO altera nada no projeto Kalibrium.
echo   - Ele edita APENAS ~/.claude/statusline-command.sh (config pessoal).
echo   - Nenhum relock de harness necessario.
echo   - Idempotente — rodar de novo substitui pelo mesmo arquivo.
echo   - Para reverter: renomeie o .bak-* de volta.
echo.
echo =====================================================
echo Pressione qualquer tecla para CONTINUAR ou Ctrl+C para CANCELAR.
echo =====================================================
pause >nul

echo.
echo Chamando bash para instalar...
echo.

bash instalar.sh
set EXIT_CODE=%errorlevel%

echo.
if %EXIT_CODE% EQU 0 (
  echo =====================================================
  echo  CONTEXT MONITOR INSTALADO COM SUCESSO
  echo =====================================================
  echo.
  echo Proximos passos:
  echo   1. FECHE esta sessao do Claude Code (digite /exit ou feche a janela^).
  echo   2. Abra o Claude Code de novo — a statusline vai aparecer colorida.
  echo   3. O indicador muda de cor sozinho conforme o contexto enche.
  echo.
  echo Se algo der errado, restaure o backup:
  echo   copy "C:\Users\rolda\.claude\statusline-command.sh.bak-*" ^
  echo        "C:\Users\rolda\.claude\statusline-command.sh"
) else (
  echo =====================================================
  echo  INSTALACAO FALHOU - codigo de saida %EXIT_CODE%
  echo =====================================================
  echo.
  echo Leia a mensagem de erro acima. O statusline atual foi preservado.
)
echo.
echo Pressione qualquer tecla para fechar esta janela.
pause >nul
endlocal
