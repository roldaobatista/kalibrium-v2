# Playbook — outage da API da Anthropic

> **Status:** ativo. Item 6.13 dos micro-ajustes da meta-auditoria #2. Define o que o PM faz se a API da Anthropic (que alimenta o Claude Code e, portanto, todo o harness) ficar indisponível por horas. Mesmo que a resposta seja "pause e aguarde", **documentar é melhor do que improvisar no meio do problema**.

## 1. Premissa

O Kalibrium depende da API da Anthropic para **construir** o produto (harness). O **runtime** do produto MVP não chama LLM na rota crítica (ver `foundation-constraints.md §1`), portanto outage da Anthropic:

- **Não derruba o produto para o cliente final** durante o MVP.
- **Derruba toda atividade de desenvolvimento** enquanto durar.

## 2. Detecção — como o PM sabe que é outage

### 2.1. Primeira suspeita

Sintomas típicos que aparecem primeiro no cliente do PM:

- Claude Code não responde ao comando.
- Sub-agent spawnado retorna erro "500" ou "connection refused".
- Lentidão inesperada em qualquer operação que envolva o modelo.
- Mensagem "rate limit" quando o uso está claramente abaixo do teto.

### 2.2. Confirmação

Antes de concluir que é outage real (e não problema local):

1. **Checar o status oficial da Anthropic** — abrir `https://status.anthropic.com` em navegador externo.
2. **Testar rede local** — `ping 8.8.8.8`, abrir outro site qualquer. Se a rede local está boa, o problema não é aqui.
3. **Testar cliente alternativo** — abrir o Claude.ai direto no navegador e mandar uma mensagem curta. Se também falha, é outage real. Se funciona, é problema local do Claude Code (ver §8).
4. **Testar em outro dispositivo** — celular com 4G, por exemplo. Se falha lá também, é outage.

Quando os 4 indicarem outage, proceder para §3.

## 3. Classificação por duração estimada

A resposta muda conforme a duração prevista do outage. A duração vem do próprio status page da Anthropic (quando informado) ou da observação do PM.

| Duração prevista | Ação |
|---|---|
| Até 30 minutos | Pausar trabalho em andamento, tomar café, esperar. |
| 30 min a 2 horas | Pausar trabalho, fazer revisão manual de docs, escrever retrospectiva que esteja atrasada. |
| 2 a 4 horas | Ação do §4 (pausa formal + salvamento de contexto). |
| 4 a 12 horas | Ação do §4 + comunicação ao primeiro cliente pagante se houver (§5). |
| Acima de 12 horas | Ação do §4 + comunicação + reavaliação de cronograma. Incidente formal em `docs/incidents/anthropic-outage-YYYY-MM-DD.md`. |

## 4. Ação — pausa formal e salvamento de contexto

Quando o outage excede 2 horas:

1. **Salvar o estado** — se há sessão ativa no Claude Code, anotar manualmente em arquivo `docs/incidents/anthropic-outage-YYYY-MM-DD.md`:
   - O que estava sendo feito (qual slice, qual tarefa).
   - Qual foi o último commit antes do outage.
   - Quais são os próximos passos planejados.
2. **Não forçar o agente.** Tentar rodar comando em loop no mesmo agente durante outage só consome token quando a API voltar. Melhor esperar.
3. **Não trocar de agente.** Trocar para Cursor, Copilot, Gemini viola R2 (um harness por branch). Esperar é a resposta correta.
4. **Trabalho alternativo válido sem API:**
   - Revisar documentação manualmente.
   - Atualizar `docs/audits/progress/meta-audit-tracker.md` com linhas ainda não registradas.
   - Escrever retrospectiva de bloco fechado.
   - Revisar `procurement-tracker.md` e contatos de consultores.
   - Ler auditorias externas acumuladas.
   - Revisar `operating-budget.md` vs consumo real.

## 5. Comunicação a cliente se houver outage longo

**Quando o MVP tiver primeiro cliente pagante**, outage prolongado da Anthropic **não** afeta o produto (premissa §1) mas afeta o suporte (as respostas do PM via customer-support dependem do PM, não da API). Se o outage exceder 4 horas em horário comercial:

1. Responder aos chamados pendentes manualmente, sem usar o agente (`customer-support.md` §2 prevê canal único por e-mail).
2. Se a indisponibilidade do suporte via agente for material, enviar comunicação curta ao cliente pagante:

```
Olá,

Nossa equipe técnica enfrenta indisponibilidade temporária de
ferramentas de desenvolvimento auxiliares. Isso não afeta o uso do
produto — você continua emitindo certificados e consultando histórico
normalmente. Apenas a velocidade de resposta a pedidos novos de
suporte pode ficar mais lenta hoje.

Assim que restabelecermos, retomamos o ritmo normal.

Obrigado pela paciência.

Equipe Kalibrium
```

## 6. Retomada

Quando a API volta:

1. **Testar com comando pequeno primeiro.** Não retomar tarefa pesada antes de confirmar que a sessão responde.
2. **Reabrir o incident file** em `docs/incidents/anthropic-outage-YYYY-MM-DD.md` e preencher:
   - Duração real do outage.
   - Trabalho perdido (se houve).
   - Comunicação enviada a cliente (se houve).
   - Lições aprendidas.
3. **Se o outage afetou prazo de entregável** (por exemplo, deadline de consultor), abrir incident separado e renegociar.
4. **Retrospectiva:** ao fim do trimestre, somar todos os incidents de outage e avaliar se a dependência da Anthropic virou risco material para o projeto.

## 7. Plano B de longo prazo

Se outages da Anthropic se tornarem frequentes (mais de 3 incidentes de 4h+ em 90 dias), **reavaliar** a dependência:

- Opção 1: manter a dependência e ampliar a tolerância operacional (slot de trabalho manual maior, buffers de prazo).
- Opção 2: avaliar cliente alternativo (outro modelo) — exigiria ADR de amendment porque mudaria o harness inteiro. Só em último caso.
- Opção 3: reduzir a superfície de uso do agente (usar menos sub-agents por slice, reduzir budget por revisão).

Nenhuma das opções é pré-aprovada — cada uma exige ADR.

## 8. Diferenciação entre outage da API e problema local

Se o teste do §2.2 passo 3 (Claude.ai funciona, Claude Code não) indicar problema local:

- **Reiniciar o Claude Code.**
- **Verificar proxy / VPN / firewall** locais.
- **Verificar se o token de auth expirou.**
- **Checar `~/.claude/settings.json`** (fora do repo — é do cliente, não do projeto).
- **Testar em outro diretório** sem os hooks do projeto.

Se o problema for local, **não é outage da Anthropic** — este playbook não se aplica. Tratar como bug do cliente local.

## 9. Cross-ref

`docs/ops/oncall.md` (quando o PM está disponível), `docs/ops/customer-support.md` (canal de suporte), `docs/security/incident-response-playbook.md` (se o outage derivar em incidente maior), `docs/constitution.md R2` (proibição de usar harness concorrente durante o outage), `docs/finance/operating-budget.md` (custo de tokens — outage reduz consumo mas pode acumular atraso).
