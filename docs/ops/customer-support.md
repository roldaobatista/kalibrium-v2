# Suporte ao cliente — canal alpha

> **Status:** ativo. Item T3.11 da Trilha #3 da meta-auditoria #2. Define o canal único pelo qual o cliente de alpha (e depois o primeiro cliente pagante) reporta bug ou dúvida, com SLA realista para operação de um humano solo (PM) mais agentes de IA.

## 1. Premissas

- **Canal único para reduzir atrito.** Nada de múltiplos canais que se contradizem.
- **Resposta humana, execução via agente.** O PM responde; as correções de código são abertas como slice pelo agente, com verifier e reviewer antes de merge.
- **SLA honesto para alpha.** Alpha não promete horas, promete ordem de grandeza. Quando o cliente virar pagante, o SLA vira contratual e endurece.

## 2. Canal único

- **E-mail:** `suporte@kalibrium.br` (ou domínio equivalente quando o domínio for registrado). Encaminhado para o e-mail do PM.
- **Janela de atendimento:** dias úteis, das 9h às 18h (horário de Brasília). Mensagens fora da janela são respondidas no primeiro dia útil.
- **Fora da janela:** sem resposta humana. Mensagem entra na fila e é confirmada por auto-reply com prazo esperado.

Nenhum outro canal oficial é aceito no alpha. Se o cliente pedir WhatsApp, chat embutido ou telefone, a resposta é "por enquanto, somente e-mail — em breve abriremos outros canais".

## 3. SLAs de resposta (alpha)

| Categoria | Primeiro contato de confirmação | Primeira análise substantiva | Resolução esperada |
|---|---|---|---|
| **Bug P0** (impede emissão de certificado, vazamento, indisponibilidade) | 1 hora útil | 4 horas úteis | 24 horas úteis |
| **Bug P1** (impede fluxo secundário, erro intermitente) | 4 horas úteis | 1 dia útil | 5 dias úteis |
| **Bug P2** (inconveniente, cosmético) | 1 dia útil | 3 dias úteis | próximo ciclo de slice |
| **Dúvida sobre uso** | 1 dia útil | 1 dia útil | 2 dias úteis |
| **Solicitação de funcionalidade nova** | 2 dias úteis | discovery do PM | não garantida no MVP |

**Alpha não promete 24/7.** O oncall é diário (ver `oncall.md`). SLA é medido apenas em horas úteis.

## 4. Template de resposta "recebido / em análise / resolvido"

### 4.1. Recebido (auto-reply imediato)

```
Olá,

Recebemos seu contato sobre "[ASSUNTO]" em [DATA/HORA].
Nosso atendimento funciona de segunda a sexta, 9h às 18h
(horário de Brasília). Você vai receber a primeira análise
dentro do prazo compatível com a prioridade do caso — normalmente
até [PRAZO DE PRIMEIRA ANÁLISE].

Se for urgente (produto fora do ar, risco de vazamento, certificado
bloqueado), responda este e-mail incluindo a palavra "URGENTE" no
assunto. Isso move o caso para a fila de prioridade crítica.

Obrigado por nos procurar.

Equipe Kalibrium
```

### 4.2. Em análise (resposta humana do PM dentro do SLA)

```
Olá,

Seu caso foi registrado como [ID] e classificado como
[P0/P1/P2/dúvida].

Já olhamos e identificamos que [RESUMO DO ACHADO EM LINGUAGEM
DE PRODUTO, SEM JARGÃO — ver glossary-pm.md]. O próximo passo é
[AÇÃO CLARA]. Prazo esperado: [PRAZO].

Qualquer informação adicional que puder enviar (capturas de tela,
número do pedido afetado, horário aproximado) ajuda a acelerar.

Atualizaremos você em no máximo [INTERVALO] com o próximo estado.

Obrigado pela paciência.

Equipe Kalibrium
```

### 4.3. Resolvido

```
Olá,

Seu caso [ID] foi resolvido. O que fizemos: [RESUMO EM LINGUAGEM
DE PRODUTO]. A correção já está disponível para você em
[TELA/FLUXO AFETADO].

Se você notar qualquer comportamento estranho ainda relacionado
a este caso, responda este e-mail e nós reabrimos imediatamente.
Se tudo estiver bem, não precisa responder.

Obrigado por nos ajudar a melhorar o produto.

Equipe Kalibrium
```

## 5. Escalação do suporte para agente

Quando o PM identifica que o caso exige correção de código:

1. O PM abre slice em `specs/NNN-support-<slug>/` com título descritivo.
2. O conteúdo inicial do spec é o próprio e-mail do cliente com os dados sensíveis removidos.
3. O AC do slice é "cliente consegue [ação] sem erro [mensagem]".
4. O agente `architect` gera plan, o `ac-to-test` gera teste vermelho, o `implementer` corrige, o `verifier` aprova, o `reviewer` aprova, o merge acontece.
5. O PM fecha o atendimento com a mensagem "resolvido" (§4.3).

## 6. Registro de atendimento

- Cada caso é um arquivo em `docs/support/<YYYY-MM-DD>-<slug>.md` (criar quando o primeiro caso chegar).
- Campos mínimos: data, cliente, canal, classificação, ações, resolução, tempo gasto.
- Dado pessoal do cliente é anonimizado antes de salvar (nome → hash, e-mail → domínio-apenas, CNPJ mantido).

## 7. Limites honestos

- **Não há fim de semana no alpha.** Se o cliente abrir chamado no sábado, ele é visto na segunda.
- **Não há plantão noturno no alpha.** P0 fora da janela pode esperar algumas horas.
- **Não há segunda linha humana.** O PM é a única linha humana. Se o caso exigir parecer de consultor (metrologia, fiscal, DPO), o prazo estende conforme o contrato do consultor.
- **Quando o primeiro tenant pagante entrar, esta política é revisada** para adicionar horário estendido e canal de urgência.

## 8. Cross-ref

`oncall.md` (T3.8), `glossary-pm.md` (1.5.6 — linguagem de produto das respostas), `incident-response-playbook.md` (T2.5), `procurement-tracker.md` (consultores).
