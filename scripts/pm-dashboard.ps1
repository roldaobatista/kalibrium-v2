# ============================================================
# Kalibrium V2 - Dashboard do PM (PT-BR, linguagem de produto)
# Atualiza a cada 20 segundos lendo project-state.json
# ============================================================

$ErrorActionPreference = "SilentlyContinue"
$ProjectRoot = "C:\PROJETOS\saas\kalibrium-v2"
Set-Location $ProjectRoot

# Forca UTF-8 no output pra acentos renderizarem certo
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8
try { chcp 65001 > $null } catch {}

function Get-EpicTitle {
    param([string]$EpicKey)
    $titles = @{
        "E01" = "Fundacao do Sistema"
        "E02" = "Planos e Assinaturas"
        "E03" = "Gestao de Clientes"
        "E04" = "Gestao de Contratos"
        "E05" = "Faturamento"
        "E06" = "Cobranca e Pagamento"
        "E07" = "Produtos e Servicos"
        "E08" = "Equipe e Permissoes"
        "E09" = "Relatorios"
        "E10" = "Integracoes Fiscais"
        "E11" = "Portal do Cliente"
        "E12" = "Notificacoes"
        "E13" = "Analytics (Pos-MVP)"
        "E14" = "API Publica (Pos-MVP)"
    }
    if ($titles.ContainsKey($EpicKey)) { return $titles[$EpicKey] }
    return $EpicKey
}

function Translate-Phase {
    param([string]$Phase)
    switch ($Phase) {
        "discovery"  { return "Descoberta (entendendo o produto)" }
        "strategy"   { return "Estrategia Tecnica" }
        "planning"   { return "Planejamento" }
        "execution"  { return "Execucao (construindo)" }
        "closing"    { return "Encerramento" }
        default      { return $Phase }
    }
}

function Translate-Gate {
    param([string]$Gate)
    switch ($Gate) {
        "spec_auditor"        { return "Especificacao auditada" }
        "plan"                { return "Plano tecnico criado" }
        "plan_review"         { return "Plano revisado" }
        "verifier"            { return "Verificacao mecanica" }
        "reviewer"            { return "Revisao de codigo" }
        "security_reviewer"   { return "Seguranca (OWASP/LGPD)" }
        "test_auditor"        { return "Auditoria de testes" }
        "functional_reviewer" { return "Revisao funcional" }
        "master_auditor"      { return "Auditoria mestre dual" }
        default               { return $Gate }
    }
}

function Get-StoryTitles {
    param([string]$EpicKey)
    $indexPath = "$ProjectRoot\epics\$EpicKey\stories\INDEX.md"
    $result = [ordered]@{}
    if (-not (Test-Path $indexPath)) { return $result }
    $lines = Get-Content $indexPath -Encoding UTF8
    foreach ($line in $lines) {
        # Exige pelo menos 4 colunas (primeira tabela tem 5) pra ignorar tabela de ACs (so 2 cols)
        if ($line -match '^\|\s*(E\d+-S\d+[a-z]?)\s*\|\s*([^|]+?)\s*\|[^|]+\|[^|]+\|') {
            $id = $matches[1].Trim()
            $title = $matches[2].Trim()
            # Ignora se o "titulo" for so numero (caso a regex capture linha de contagem)
            if ($title -notmatch '^\d+$' -and -not $result.Contains($id)) {
                $result[$id] = $title
            }
        }
    }
    return $result
}

function Render-CurrentEpicStories {
    param($state)

    $epicKey = $state.active_epic
    $epic = $state.epics_status.$epicKey
    if (-not $epic) { return }

    $titles = Get-StoryTitles $epicKey
    $currentStory = $state.execution.current_story

    Write-Host ("  EPICO {0} - {1} (detalhe)" -f $epicKey, (Get-EpicTitle $epicKey)) -ForegroundColor Yellow
    Write-Host "  ---------------------------------------------------------"

    $storyList = $epic.stories.PSObject.Properties | Sort-Object { $_.Name }
    foreach ($s in $storyList) {
        $id = $s.Name
        $status = $s.Value
        $title = if ($titles.Contains($id)) { $titles[$id] } else { "(sem titulo)" }
        if ($title.Length -gt 55) { $title = $title.Substring(0, 52) + "..." }

        $icon = "[  ]"
        $color = "DarkGray"
        switch ($status) {
            "merged"      { $icon = "[OK]"; $color = "Green" }
            "in_progress" { $icon = "[>>]"; $color = "Yellow" }
            "draft"       { $icon = "[  ]"; $color = "DarkGray" }
            default       { $icon = "[  ]"; $color = "DarkGray" }
        }

        $suffix = ""
        if ($id -eq $currentStory) {
            $suffix = "  <-- ATIVA"
            if ($status -eq "draft") { $icon = "[>>]"; $color = "Cyan" }
        }

        Write-Host ("  {0} {1,-10} {2}{3}" -f $icon, $id, $title, $suffix) -ForegroundColor $color
    }
    Write-Host ""
}

function Render-Pipeline {
    param($state)

    $currentStory = $state.execution.current_story
    $currentSlice = $state.execution.current_slice
    $sliceStatus  = $state.execution.slice_status
    $gates        = $state.gates_status
    $rejections   = $state.execution.consecutive_rejections

    Write-Host ("  FLUXO DA STORY {0}" -f $currentStory) -ForegroundColor Yellow
    Write-Host "  ---------------------------------------------------------"

    if ($sliceStatus -eq "not_started" -or -not $currentSlice) {
        Write-Host "  Aguardando inicio do proximo slice." -ForegroundColor DarkGray
        Write-Host ""
        Write-Host ("  Rode: /start-story {0}" -f $currentStory) -ForegroundColor Cyan
        Write-Host ""
        Write-Host "  Etapas quando o slice comecar:" -ForegroundColor DarkGray
        $allSteps = @(
            "1. Criar slice (escrever spec)",
            "2. Auditar especificacao",
            "3. Criar plano tecnico",
            "4. Revisar plano",
            "5. Escrever testes (red)",
            "6. Implementar codigo (testes verdes)",
            "7. Verificacao mecanica",
            "8. Revisao de codigo",
            "9. Seguranca (OWASP/LGPD)",
            "10. Auditoria de testes",
            "11. Revisao funcional",
            "12. Auditoria mestre dual (Opus + GPT-5)",
            "13. Merge"
        )
        foreach ($s in $allSteps) {
            Write-Host ("    [ ] {0}" -f $s) -ForegroundColor DarkGray
        }
        Write-Host ""
        return
    }

    $steps = @(
        @{ n=1;  name="Criar slice";                         source="always" }
        @{ n=2;  name="Auditar especificacao";               source="gate"; gate="spec_auditor" }
        @{ n=3;  name="Criar plano tecnico";                 source="gate"; gate="plan" }
        @{ n=4;  name="Revisar plano";                       source="gate"; gate="plan_review" }
        @{ n=5;  name="Escrever testes (red)";               source="derived"; kind="tests" }
        @{ n=6;  name="Implementar codigo";                  source="derived"; kind="impl" }
        @{ n=7;  name="Verificacao mecanica";                source="gate"; gate="verifier" }
        @{ n=8;  name="Revisao de codigo";                   source="gate"; gate="reviewer" }
        @{ n=9;  name="Seguranca (OWASP/LGPD)";              source="gate"; gate="security_reviewer" }
        @{ n=10; name="Auditoria de testes";                 source="gate"; gate="test_auditor" }
        @{ n=11; name="Revisao funcional";                   source="gate"; gate="functional_reviewer" }
        @{ n=12; name="Auditoria mestre dual (Opus+GPT-5)";  source="gate"; gate="master_auditor" }
        @{ n=13; name="Merge";                               source="derived"; kind="merge" }
    )

    $currentMarked = $false
    foreach ($step in $steps) {
        $status = "not_run"

        if ($step.source -eq "always") {
            $status = "approved"
        }
        elseif ($step.source -eq "gate") {
            $v = $gates.($step.gate)
            if ($v) { $status = $v }
        }
        elseif ($step.source -eq "derived") {
            if ($step.kind -eq "tests" -or $step.kind -eq "impl") {
                if ($gates.verifier -eq "approved") { $status = "approved" }
                elseif ($gates.plan_review -eq "approved") { $status = "pending" }
            }
            elseif ($step.kind -eq "merge") {
                if ($gates.master_auditor -eq "approved") { $status = "pending" }
            }
        }

        $icon = "[  ]"
        $color = "DarkGray"
        $suffix = ""

        if ($status -eq "approved") {
            $icon = "[OK]"; $color = "Green"
        }
        elseif ($status -eq "rejected") {
            $icon = "[X] "; $color = "Red"
            $suffix = "  <-- CORRIGINDO (rejeicoes: $rejections)"
            if (-not $currentMarked) { $currentMarked = $true }
        }
        elseif ($status -eq "pending") {
            $icon = "[>>]"; $color = "Yellow"
            if (-not $currentMarked) {
                $suffix = "  <-- VOCE ESTA AQUI"
                $currentMarked = $true
            }
        }
        elseif ($status -eq "not_run" -and -not $currentMarked) {
            $icon = "[>>]"; $color = "Cyan"
            $suffix = "  <-- PROXIMA"
            $currentMarked = $true
        }

        Write-Host ("  {0} {1,2}. {2}{3}" -f $icon, $step.n, $step.name, $suffix) -ForegroundColor $color
    }
    Write-Host ""
}

function Render-Dashboard {
    # Move cursor pro topo (ANSI) em vez de apagar - evita piscar/saltar
    [Console]::Out.Write("`e[H")

    try {
        $state = Get-Content "$ProjectRoot\project-state.json" -Raw -Encoding UTF8 -ErrorAction Stop | ConvertFrom-Json
    } catch {
        Write-Host ""
        Write-Host "  ERRO: nao consegui ler project-state.json" -ForegroundColor Red
        Write-Host "  Caminho: $ProjectRoot\project-state.json" -ForegroundColor Yellow
        return
    }

    $branch = (git -C $ProjectRoot branch --show-current 2>$null)
    if (-not $branch) { $branch = "(desconhecida)" }

    $lastCommit = (git -C $ProjectRoot log -1 --format="%h %s" 2>$null)
    if (-not $lastCommit) { $lastCommit = "-" }

    $epicsMerged = 0
    $state.epics_status.PSObject.Properties | ForEach-Object {
        if ($_.Value.status -eq "merged") { $epicsMerged++ }
    }
    $epicsTotal = $state.planning.epics_mvp

    $currentEpicKey = $state.active_epic
    $currentEpic = $state.epics_status.$currentEpicKey
    $storiesTotal = ($currentEpic.stories.PSObject.Properties | Measure-Object).Count
    $storiesMerged = 0
    $currentEpic.stories.PSObject.Properties | ForEach-Object {
        if ($_.Value -eq "merged") { $storiesMerged++ }
    }

    # Header
    Write-Host ""
    Write-Host "  =========================================================" -ForegroundColor Cyan
    Write-Host "            KALIBRIUM V2 - PAINEL DO PM"                    -ForegroundColor Cyan
    Write-Host "  =========================================================" -ForegroundColor Cyan
    Write-Host ""

    # ONDE ESTAMOS
    Write-Host "  ONDE ESTAMOS" -ForegroundColor Yellow
    Write-Host "  ------------"
    Write-Host ("  Fase:          {0}" -f (Translate-Phase $state.current_phase))
    if ($state.paused) {
        Write-Host ("  Status:        PAUSADO - {0}" -f $state.paused_reason) -ForegroundColor Red
    } else {
        Write-Host  "  Status:        Ativo" -ForegroundColor Green
    }
    Write-Host ("  Epico ativo:   {0} - {1}  ({2}/{3} stories entregues)" -f $currentEpicKey, (Get-EpicTitle $currentEpicKey), $storiesMerged, $storiesTotal)
    Write-Host ("  Story atual:   {0}" -f $state.execution.current_story)
    if ($state.execution.current_slice) {
        Write-Host ("  Slice atual:   {0} (status: {1})" -f $state.execution.current_slice, $state.execution.slice_status)
    } else {
        Write-Host  "  Slice atual:   (nenhum iniciado)" -ForegroundColor DarkGray
    }
    Write-Host ""

    # EPICO ATIVO EXPANDIDO
    Render-CurrentEpicStories -state $state

    # FLUXO DA STORY ATUAL
    Render-Pipeline -state $state

    # PROGRESSO GERAL (compacto)
    Write-Host "  PROGRESSO DOS 12 EPICOS MVP" -ForegroundColor Yellow
    Write-Host "  ---------------------------"
    $allEpics = @("E01","E02","E03","E04","E05","E06","E07","E08","E09","E10","E11","E12")
    foreach ($ek in $allEpics) {
        $ep = $state.epics_status.$ek
        if ($ep) {
            $total = ($ep.stories.PSObject.Properties | Measure-Object).Count
            $merged = 0
            $ep.stories.PSObject.Properties | ForEach-Object {
                if ($_.Value -eq "merged") { $merged++ }
            }
            $icon = "[  ]"; $color = "White"
            if ($ep.status -eq "merged")      { $icon = "[OK]"; $color = "Green" }
            if ($ep.status -eq "in_progress") { $icon = "[>>]"; $color = "Yellow" }
            Write-Host ("  {0} {1} - {2,-22} {3}/{4}" -f $icon, $ek, (Get-EpicTitle $ek), $merged, $total) -ForegroundColor $color
        } else {
            Write-Host ("  [  ] {0} - {1,-22} aguardando" -f $ek, (Get-EpicTitle $ek)) -ForegroundColor DarkGray
        }
    }
    Write-Host ""

    # PROXIMA ACAO
    Write-Host "  PROXIMA ACAO RECOMENDADA" -ForegroundColor Yellow
    Write-Host "  ------------------------"
    if ($state.next_recommended_action) {
        Write-Host ("  -> {0}" -f $state.next_recommended_action) -ForegroundColor Green
    } else {
        Write-Host  "  -> (nenhuma acao definida)" -ForegroundColor DarkGray
    }
    Write-Host ""

    # ALERTAS
    Write-Host "  ALERTAS" -ForegroundColor Yellow
    Write-Host "  -------"
    $alertCount = 0
    if ($state.execution.blocked) {
        Write-Host ("  [!] Projeto BLOQUEADO: {0}" -f $state.execution.blocked_reason) -ForegroundColor Red
        $alertCount++
    }
    if ($state.execution.consecutive_rejections -ge 3) {
        Write-Host ("  [!] {0} rejeicoes consecutivas (proximo de R6)" -f $state.execution.consecutive_rejections) -ForegroundColor Red
        $alertCount++
    }
    if ($state.technical_debt_count -gt 0) {
        Write-Host ("  [i] {0} itens de divida tecnica em aberto" -f $state.technical_debt_count) -ForegroundColor DarkYellow
        $alertCount++
    }
    if ($alertCount -eq 0) {
        Write-Host "  Nenhum alerta. Tudo correndo bem." -ForegroundColor Green
    }
    Write-Host ""

    # INFO TECNICA
    Write-Host "  INFO TECNICA" -ForegroundColor DarkGray
    Write-Host "  ------------" -ForegroundColor DarkGray
    Write-Host ("  Branch:             {0}" -f $branch) -ForegroundColor DarkGray
    Write-Host ("  Ultimo commit:      {0}" -f $lastCommit) -ForegroundColor DarkGray
    if ($state.last_checkpoint) {
        Write-Host ("  Ultimo checkpoint:  {0}" -f $state.last_checkpoint) -ForegroundColor DarkGray
    }
    Write-Host ("  Epicos entregues:   {0}/{1} MVP" -f $epicsMerged, $epicsTotal) -ForegroundColor DarkGray
    Write-Host ""

    Write-Host "  ---------------------------------------------------------" -ForegroundColor DarkGray
    Write-Host ("  Atualizado: {0}   (refresh automatico 20s)   Ctrl+C p/ sair" -f (Get-Date -Format "HH:mm:ss")) -ForegroundColor DarkGray

    # Apaga linhas residuais abaixo (caso render anterior fosse maior) - ANSI ESC[J
    [Console]::Out.Write("`e[J")
}

# Loop principal
$prevCursorVisible = [Console]::CursorVisible
try {
    Clear-Host
    [Console]::CursorVisible = $false
    while ($true) {
        Render-Dashboard
        Start-Sleep -Seconds 20
    }
} catch {
    Write-Host ""
    Write-Host "Dashboard encerrado." -ForegroundColor Yellow
} finally {
    [Console]::CursorVisible = $prevCursorVisible
}
