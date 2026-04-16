---
description: Assistente guiado para converter descrição livre do PM (português) em specs/NNN/spec.md com ACs numerados e testáveis. Preenche contexto/jornada/ACs/fora-de-escopo e valida via scripts/draft-spec.sh. Resolve o hole de contrato P0-3 do meta-audit #2. Uso: /draft-spec NNN.
---

# /draft-spec

## Uso
```
/draft-spec NNN
```

## Por que existe
Antes do meta-audit #2 (2026-04-11), `architecture-expert` (modo: plan) e `builder` (modo: test-writer) exigiam `spec.md` com ACs numerados, mas nenhum componente traduzia a descrição livre do PM para esse formato. `/draft-spec` fecha essa lacuna: é a skill interativa que transforma NL do PM em spec.md validável.

## Quando invocar
Depois de `/new-slice NNN "título"` (que cria o esqueleto vazio) e **antes** de convocar `architecture-expert` (modo: plan).

## Pré-condições
- `specs/NNN/spec.md` existe e contém o template (status `draft`)
- PM tem em mente (ou já descreveu na conversa) o comportamento desejado em português

## O que faz

1. **Capta a descrição NL** — lê a conversa atual para recuperar o que o PM já disse. Se ainda não houver descrição suficiente, faz **uma única pergunta curta** ao PM (linguagem R12, sem jargão técnico).
2. **Preenche seção por seção** em `specs/NNN/spec.md`:
   - **Contexto** — por que o slice existe, quem se beneficia
   - **Jornada alvo** — ponta a ponta, 1-2 parágrafos
   - **Acceptance Criteria** — cada um numerado (`AC-001`, `AC-002`, …), sequencial, comportamento observável. Formato "Dado X, quando Y, então Z" quando couber
   - **Fora de escopo** — o que explicitamente NÃO entra
   - **Dependências externas** — bibliotecas, APIs, ADRs
   - **Riscos conhecidos** — com mitigação proposta
3. **Valida** executando `scripts/draft-spec.sh NNN --check`. Checagens:
   - Pelo menos 1 AC
   - ACs sequenciais sem buracos (AC-001, AC-002, …)
   - Nenhum AC vazio, com TODO/TBD/FIXME ou reticências
   - Seções obrigatórias não-vazias
   - Placeholders do template (`<título>`, `<humano>`, `<risco>`) removidos
4. **Se passar**, imprime resumo em linguagem R12 e próximo passo: "PM, leia `specs/NNN/spec.md`. Responda *aceito* ou diga o que ajustar."
5. **Se falhar**, mostra exatamente o motivo e volta ao passo 2.

## Por que NÃO é um sub-agent
Conversão NL→AC é conversa interativa com o PM. Sub-agent em contexto isolado (`isolation: worktree`) não consegue pedir esclarecimento em tempo real. Portanto a skill roda no agente principal; o `scripts/draft-spec.sh` é só o validador mecânico.

## Implementação

```bash
bash scripts/draft-spec.sh "$1" --check
```

## Handoff
- **OK + PM aceita** → invocar sub-agent `architecture-expert` (modo: plan) para gerar `plan.md`.
- **PM pede ajuste** → skill reexecuta no trecho apontado.
- **PM não sabe ainda** → registra "spec em pausa" e encerra sem bloquear outras tarefas.

## Regra de ouro
Um AC só vale se **um teste automatizado** consegue dizer "passou" ou "falhou" sem opinião humana (P2). Se o PM descrever algo subjetivo ("a página tem que ficar bonita"), a skill pede reformulação: "PM, como eu sei automaticamente que está bonita? Ex: tempo de carregamento < 2s, todos os elementos dentro da viewport, score Lighthouse > 90."

## Agentes
Nenhum — executada pelo orquestrador. O validador mecânico é `scripts/draft-spec.sh`, não um sub-agent.

## Erros e Recuperação

| Erro | Recuperação |
|---|---|
| `specs/NNN/spec.md` não existe (template ausente) | Sugerir `/new-slice NNN "título"` primeiro para criar o esqueleto. |
| `scripts/draft-spec.sh NNN --check` falha (ACs inválidos) | Mostrar ao PM exatamente quais ACs falharam e por quê. Corrigir e revalidar. |
| PM descreve requisito subjetivo/não-testável | Aplicar regra de ouro: pedir reformulação com exemplo de métrica objetiva. Não registrar AC até ser testável. |
| PM não consegue descrever o comportamento desejado | Fazer perguntas de esclarecimento em linguagem R12. Se após 3 tentativas não houver clareza, registrar "spec em pausa" e encerrar. |
