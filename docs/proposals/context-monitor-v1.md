# Proposta — Context Monitor v1 (statusline colorida por zona)

**Data:** 2026-04-17
**Status:** pronto para PM aplicar
**Escopo:** apenas `~/.claude/` (config pessoal). Nada selado do harness Kalibrium é tocado. Nenhum relock necessário.

## Problema

Hoje a statusline mostra `87k/1M (91% livre)` — texto monocromático. O PM precisa **ler o número e calcular mentalmente** se está em zona verde/amarela/vermelha. Isso é fricção suficiente para ser ignorado na prática, e o PM só percebe o problema quando já está em 85%+.

## Solução v1 — enhancement mínimo da statusline existente

Substituir `~/.claude/statusline-command.sh` por versão que:

1. Calcula a zona automaticamente (verde 0-60 / amarelo 60-80 / laranja 80-90 / vermelho 90%+).
2. Pinta o bloco de tokens com cor ANSI + emoji correspondente.
3. Mantém todo o resto igual (modelo, custo, pasta, branch) — não muda o layout.

Exemplo visual (antes → depois):

```
antes:   opus-4.7[1M] | 87k/1M (91% livre) | $0.42 | kalibrium-v2 (main)
depois:  opus-4.7[1M] | 🟢 87k/1M (91% livre) | $0.42 | kalibrium-v2 (main)
                        ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
                        verde (pinta só esse pedaço)
```

Quando o PM cruzar 60%, o verde vira amarelo + 🟡 — visualmente óbvio, zero cálculo mental.

## O que NÃO está nesta proposta

- **Stop hook com aviso automático** (camada 2 da recomendação original). Propositalmente adiado. Rodar camada 1 por 1 semana; se o sinal visual por si só resolver, não precisa de hook. Se não resolver, escrevo a camada 2 com os thresholds calibrados pelo uso real.
- **Qualquer mudança no repositório Kalibrium.** Nenhum arquivo selado é tocado. Nenhum relock de harness é necessário.

## Risco

Baixíssimo. O pior caso é o script não rodar — e nesse caso o Claude Code simplesmente não mostra statusline (fallback natural). Backup automático do script atual é criado antes da substituição (`~/.claude/statusline-command.sh.bak-<timestamp>`).

## Reversibilidade

Total. Para reverter:

```powershell
# Windows
copy "C:\Users\rolda\.claude\statusline-command.sh.bak-<timestamp>" ^
     "C:\Users\rolda\.claude\statusline-command.sh"
```

Feche/reabra o Claude Code e volta ao estado original.

## Arquivos entregues

| Arquivo | Papel |
|---|---|
| `scripts/pm/context-monitor/statusline-command.sh` | Novo script (com colorização por zona) |
| `scripts/pm/context-monitor/instalar.sh` | Instalador bash (backup + copia + smoke test) |
| `scripts/pm/context-monitor/INSTALAR-CONTEXT-MONITOR.bat` | Ponto de entrada para PM (duplo-clique) |
| `docs/proposals/context-monitor-v1.md` | Este documento |

## Como o PM aplica

1. Abrir o Explorer do Windows em `C:\PROJETOS\saas\kalibrium-v2\scripts\pm\context-monitor\`.
2. Duplo-clique em `INSTALAR-CONTEXT-MONITOR.bat`.
3. Ler o aviso, apertar qualquer tecla.
4. Aguardar mensagem `CONTEXT MONITOR INSTALADO COM SUCESSO`.
5. Fechar a janela.
6. Fechar o Claude Code atual (`/exit` ou fechar janela).
7. Abrir o Claude Code de novo — statusline já aparece colorida.

Tempo estimado: 30 segundos.

## Observação fora do escopo (P0 de segurança)

Ao ler `~/.claude/settings.json` durante esta análise, detectei que o campo `env.GITHUB_PERSONAL_ACCESS_TOKEN` está armazenado **em plaintext** no arquivo global. Isso é um risco relevante se o arquivo for sincronizado (OneDrive, backup automático, etc.) ou acessado por outro processo. Recomendação: rotacionar o token no GitHub e migrar para variável de ambiente do sistema ou para um cofre (Windows Credential Manager). Essa correção não tem relação com este Context Monitor — fica para decisão separada.

## Próxima iteração (se v1 não bastar)

Adicionar Stop hook global em `~/.claude/settings.json` que, ao fim de cada turno, emita aviso no próprio chat quando:
- 60%+ e slice acabou de fechar → sugere `/checkpoint + /clear + /resume`
- 80%+ → aviso forte independente do estado
- 90%+ → ação obrigatória

Só escrever depois de medir se o sinal visual sozinho resolve.
