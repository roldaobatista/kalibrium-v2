@echo off
chcp 65001 >nul
setlocal

:: Garante que estamos na raiz do projeto independente de onde o .bat foi chamado
cd /d "%~dp0.."

echo.
echo ============================================================
echo  ADICIONAR PERMISSOES DO CODEX MCP ao settings.json
echo ============================================================
echo.
echo  Este script vai:
echo    1. Editar .claude\settings.json adicionando as 2 permissoes
echo    2. Exibir instrucao para voce rodar o relock-codex.bat em seguida
echo.
pause

:: ----------------------------------------------------------------
:: Editar settings.json com PowerShell
:: ----------------------------------------------------------------
echo.
echo [1/2] Adicionando permissoes ao .claude\settings.json ...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$settingsPath = Join-Path (Get-Location) '.claude\settings.json';" ^
    "if (-not (Test-Path $settingsPath)) { Write-Host '[ERRO] Arquivo nao encontrado: ' + $settingsPath; exit 1; }" ^
    "$data = Get-Content $settingsPath -Raw | ConvertFrom-Json;" ^
    "if (-not $data.permissions) { $data | Add-Member -NotePropertyName permissions -NotePropertyValue ([pscustomobject]@{ allow = @() }); }" ^
    "if (-not $data.permissions.allow) { $data.permissions | Add-Member -NotePropertyName allow -NotePropertyValue @() -Force; }" ^
    "$allow = [System.Collections.Generic.List[string]]$data.permissions.allow;" ^
    "$added = @();" ^
    "foreach ($perm in @('mcp__codex__codex', 'mcp__codex__codex-reply')) {" ^
    "  if ($allow -notcontains $perm) { $allow.Add($perm); $added += $perm; }" ^
    "};" ^
    "$data.permissions.allow = $allow.ToArray();" ^
    "$data | ConvertTo-Json -Depth 10 | Set-Content $settingsPath -Encoding UTF8;" ^
    "if ($added.Count -gt 0) { Write-Host '[OK] Permissoes adicionadas: ' + ($added -join ', '); }" ^
    "else { Write-Host '[OK] Permissoes ja existiam. Nenhuma alteracao necessaria.'; }"

if errorlevel 1 (
    echo.
    echo [ERRO] Falha ao editar settings.json. Veja mensagem acima.
    echo.
    pause
    exit /b 1
)

:: ----------------------------------------------------------------
:: Instrucao para o proximo passo
:: ----------------------------------------------------------------
echo.
echo [2/2] Edicao concluida!
echo.
echo ============================================================
echo  PROXIMO PASSO OBRIGATORIO:
echo.
echo  Abra (duplo clique) o arquivo:
echo    scripts\relock-codex.bat
echo.
echo  Esse segundo script regenera os selos de seguranca do
echo  harness. Sem ele, o Claude Code vai bloquear na proxima
echo  sessao por detectar divergencia de hash.
echo ============================================================
echo.
pause
endlocal
