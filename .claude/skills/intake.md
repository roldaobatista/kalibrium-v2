---
description: Entrevista guiada de descoberta com o PM. Faz as 10 perguntas estrategicas que determinam arquitetura, infra, seguranca e custo. Produz intake-responses.md e dispara product-expert (discovery). Uso: /intake.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-005R"
---

# /intake

## Uso
```
/intake
```

## Por que existe
Antes de qualquer decisao tecnica, o sistema precisa entender contexto, restricoes e riscos. As 10 perguntas estrategicas extraem informacao que muda fundamentalmente as decisoes de stack, deploy, seguranca e custo. Sem intake, decisoes sao tomadas no escuro.

## Quando invocar
No inicio do projeto, antes de `/freeze-prd`. Pode ser reinvocado se o contexto mudar significativamente.

## Pre-condicoes
- Nenhuma (e a primeira skill do fluxo)

## O que faz

### Fase 1 — Perguntas estrategicas (interativo com PM)

Fazer as perguntas abaixo **uma por vez**, em linguagem R12 (produto, nao tecnica). Esperar resposta do PM antes de avancar. Se o PM nao souber, registrar como "pendente" e prosseguir.

#### As 10 perguntas obrigatorias

1. **Onde isso vai rodar?**
   "Voce ja tem servidor, ou precisa de hospedagem? Se ja tem, que tipo? (computador dedicado, servico na nuvem, hospedagem compartilhada)"

2. **Qual o volume de uso esperado?**
   "Quantos usuarios voce espera por dia/semana? Todos ao mesmo tempo ou espalhados? Tem pico de uso (ex: fim de mes)?"

3. **Tem dados sensiveis?**
   "O sistema vai lidar com CPF, dados de saude, pagamentos, dados corporativos? Precisa seguir alguma lei especifica (LGPD, etc.)?"

4. **Vai ter processamento demorado?**
   "Tem algo que demora mais que alguns segundos? Ex: gerar relatorio grande, enviar muitos emails, processar arquivo, integrar com sistema externo"

5. **Qual o nivel de disponibilidade?**
   "Se o sistema ficar fora do ar por 1 hora, qual o impacto? E por 1 dia? Precisa funcionar 24/7 ou tem horario comercial?"

6. **Como funciona o acesso?**
   "Quem usa o sistema? Uma empresa so ou varias? Tem niveis de permissao (admin, usuario, visualizador)? Login com email/senha ou algum sistema especifico?"

7. **Quais sistemas externos entram no MVP?**
   "Precisa conectar com algo externo no lancamento? Ex: sistema de pagamento, WhatsApp, email automatico, ERP, planilha, outro software"

8. **Quem cuida do sistema depois?**
   "Voce tem equipe tecnica para manter? Ou precisa ser 'automatico' o maximo possivel?"

9. **Qual o teto de custo mensal?**
   "Quanto pode gastar por mes com infraestrutura (servidor, banco de dados, servicos)? Tem faixa: ate R$100, ate R$500, ate R$2000, sem limite?"

10. **O que nao pode dar errado de jeito nenhum?**
    "Se voce pudesse garantir UMA coisa sobre o sistema, o que seria? (Ex: nunca perder um certificado, nunca mostrar dado de um cliente para outro, nunca errar um calculo)"

### Fase 2 — Registro

Ao ter todas as respostas (ou pendentes marcadas), escrever `docs/product/intake-responses.md` com:

```markdown
# Intake — Respostas do PM

Data: YYYY-MM-DD
PM: <nome>

## 1. Hospedagem
**Resposta:** <resposta do PM>
**Implicacao tecnica:** <o que isso significa para arquitetura>

## 2. Volume de uso
**Resposta:** <resposta>
**Implicacao tecnica:** <implicacao>

[... para cada pergunta]

## Perguntas pendentes
- [ ] Pergunta N: <motivo da pendencia>

## Sinais importantes detectados
- <sinal 1 e o que significa>
- <sinal 2>
```

### Fase 3 — Disparo de sub-agents

Apos registrar respostas:
1. Spawn `product-expert` (modo: discovery) — **unica invocacao consolidada** que produz, num so output package, glossario + modelo de dominio + riscos + suposicoes + NFRs estruturados com metricas mensuraveis.

Nao ha modo `nfr-analysis` separado no mapa canonico v1.2.2 — os NFRs sao entregaveis do mesmo modo `discovery` conforme `.claude/agents/product-expert.md §Modo 1: discovery`.

### Fase 4 — Resumo ao PM

Apresentar em linguagem R12:
```
Entendi o contexto do projeto. Aqui esta o resumo:

- Hospedagem: [resumo]
- Volume esperado: [resumo]
- Dados sensiveis: [sim/nao, quais]
- Processamento pesado: [sim/nao, quais]
- Disponibilidade: [nivel]
- Acesso: [modelo]
- Integracoes MVP: [lista]
- Operacao: [quem cuida]
- Custo mensal: [faixa]
- Prioridade absoluta: [o que nao pode falhar]

Proximo passo: revisar o PRD com essas informacoes incorporadas.
Quer seguir para /freeze-prd ou ajustar algo?
```

## Agentes
- `product-expert` (modo: discovery) — unica invocacao consolidada que produz glossario, modelo de dominio, riscos, suposicoes E NFRs estruturados com metricas mensuraveis. Substitui v2 `domain-analyst` + `nfr-analyst` (fusao em v3 — um modo, um agente, um output package contendo `domain/glossary.md`, `domain/model.md`, `docs/nfrs/nfrs.md`). Nao ha invocacao separada de NFR — esta dentro do escopo unico de `discovery` conforme `.claude/agents/product-expert.md §Modo 1: discovery`.

## Erros e Recuperacao

| Erro | Recuperacao |
|---|---|
| PM nao responde a uma pergunta (nao sabe) | Registrar como "pendente" no intake-responses.md. Prosseguir com as demais. Revisitar antes de `/freeze-prd`. |
| `product-expert` (modo: discovery) falha ou produz output incompleto (qualquer dos artefatos: glossario, modelo, riscos, NFRs) | Re-spawnar com contexto adicional do intake. Fazer até 5 ciclos automáticos; na 6ª falha consecutiva, escalar humano (R6). |
| `product-expert` (modo: discovery) produz glossario mas NFRs incompletos | Re-spawnar mesmo modo com prompt reforcado focando apenas em NFRs faltantes — nao ha modo separado. |
| PM contradiz respostas anteriores durante a entrevista | Parar, apresentar a contradicao em linguagem R12, pedir esclarecimento antes de registrar. |

## Handoff
- PM satisfeito → `/freeze-prd`
- PM quer ajustar → reexecutar perguntas especificas
- PM nao sabe ainda → registrar pendencias e pausar

## Conformidade com protocolo v1.2.4

- **Agents invocados:** `product-expert (discovery)` — unica invocacao consolidada (glossario + modelo + riscos + NFRs no mesmo modo) conforme mapa canonico 00 §3.1 e `.claude/agents/product-expert.md §Modo 1: discovery`
- **Gates produzidos:** n/a — skill de descoberta, nao gera gate JSON
- **Output:** `docs/product/intake-responses.md` (markdown R12) + artefatos subsequentes em `docs/domain/` via sub-agents
- **Schema formal:** nao aplicavel (skill nao produz gate output)
- **Isolamento R3:** nao aplicavel — entrevista interativa com PM roda no contexto principal do orquestrador
- **Zero-tolerance:** nao aplicavel (sem verdict)
- **Ordem no pipeline:** pre-requisito: nenhuma (primeira skill do fluxo); proximo: `/freeze-prd`
- **Referencia normativa:** `CLAUDE.md §6 Fase A`; `docs/constitution.md §2 P1` (contexto precede decisao tecnica)
