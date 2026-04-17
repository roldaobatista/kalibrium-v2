# ============================================================
# Kalibrium V2 - Cria atalho "Kalibrium PM" na Area de Trabalho
# ============================================================

$ErrorActionPreference = "Stop"

$ProjectRoot    = "C:\PROJETOS\saas\kalibrium-v2"
$TargetPath     = "$ProjectRoot\scripts\open-workspace.bat"
$DesktopPath    = [Environment]::GetFolderPath("Desktop")
$ShortcutPath   = Join-Path $DesktopPath "Kalibrium PM.lnk"

if (-not (Test-Path $TargetPath)) {
    Write-Host ""
    Write-Host "ERRO: arquivo alvo nao encontrado:" -ForegroundColor Red
    Write-Host "  $TargetPath" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

try {
    $WshShell = New-Object -ComObject WScript.Shell
    $Shortcut = $WshShell.CreateShortcut($ShortcutPath)
    $Shortcut.TargetPath       = $TargetPath
    $Shortcut.WorkingDirectory = $ProjectRoot
    $Shortcut.IconLocation     = "C:\Windows\System32\shell32.dll,13"
    $Shortcut.Description      = "Kalibrium V2 - Area de trabalho do PM (Claude Code + Dashboard)"
    $Shortcut.WindowStyle      = 1
    $Shortcut.Save()

    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host " ATALHO INSTALADO COM SUCESSO"               -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  Nome:     Kalibrium PM"                    -ForegroundColor White
    Write-Host "  Local:    $ShortcutPath"                   -ForegroundColor White
    Write-Host ""
    Write-Host "  Como usar:"                                -ForegroundColor Yellow
    Write-Host "    1. Va para a Area de Trabalho"           -ForegroundColor White
    Write-Host "    2. Clique 2x no icone 'Kalibrium PM'"    -ForegroundColor White
    Write-Host "    3. Windows Terminal abre com:"           -ForegroundColor White
    Write-Host "       - Esquerda (60%): Claude Code"        -ForegroundColor White
    Write-Host "       - Direita  (40%): Dashboard PM"       -ForegroundColor White
    Write-Host ""
} catch {
    Write-Host ""
    Write-Host "ERRO ao criar atalho: $_" -ForegroundColor Red
    exit 1
}
