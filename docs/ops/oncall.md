# Oncall — Kalibrium (PM solo)

> **Status:** ativo. Item T3.8 da Trilha #3 da meta-auditoria #2. Define a política de oncall realista para o único humano do projeto (Product Manager), respeitando que ele não é desenvolvedor nem dedica 24h ao produto. A premissa inicial é "PM vê dashboard uma vez ao dia". A política evolui quando houver primeiro tenant real pagante.

## 1. Papel do oncall no Kalibrium

- **Quem é oncall:** o Product Manager, enquanto for o único humano ativo no projeto.
- **Agente de IA como apoio:** monitoramento automático gera alertas, mas ações corretivas exigem confirmação humana.
- **O oncall NÃO é desenvolvedor de plantão.** Ele verifica, classifica, aciona agente quando necessário, aprova ou recusa sugestões.

## 2. Cadência mínima de verificação

- **Uma verificação diária obrigatória** do dashboard operacional, de segunda a sábado. Janela flexível dentro do horário comercial.
- **Domingo e feriado:** sem verificação obrigatória. Se houver alerta crítico ativo, ele fica pendurado até segunda — aceitável enquanto não houver tenant pagante.
- **Quando houver primeiro tenant pagante:** verificação passa para duas janelas diárias (manhã e início da noite), segunda a sábado, com janela de tolerância.

## 3. Canais de alerta

| Canal | Uso | Prioridade |
|---|---|---|
| E-mail do PM | Alertas P0 e P1 | Crítica |
| WhatsApp pessoal do PM | Alertas P0 (quando canal estiver configurado) | Crítica |
| Dashboard interno | Alertas P2 e status geral | Informativa |
| `docs/reports/daily-*.md` gerado pelo harness | Resumo diário assíncrono | Informativa |

Nenhum canal externo de observabilidade paga é usado no MVP (restrição `foundation-constraints.md §8` — telemetria zero a terceiros sem contrato LGPD).

## 4. Escalação

- **P0 crítico:** PM acionado imediatamente por e-mail + WhatsApp. Se o canal falhar por mais de 30 minutos, o alerta acumula e o próximo login ao dashboard mostra banner persistente.
- **P1 importante:** PM acionado por e-mail em até 1 hora. Sem WhatsApp.
- **P2 baixo:** resumo no próximo relatório diário.
- **Incidente envolvendo dado pessoal:** após classificação P0 ou P1, acionar também o DPO (quando contratado, ver `procurement-tracker.md`) conforme `incident-response-playbook.md` (T2.5).

## 5. Limite de horas de atenção e descanso

- **Teto diário de atenção em incidente:** 3 horas corridas. Além disso, o PM pausa e retoma no dia seguinte. Exceção: P0 ativo com risco de agravamento.
- **Descanso compulsório após P0:** 12 horas afastado de qualquer ação sobre o produto depois de conter um P0, independentemente do horário em que ele fechou. Se o P0 durou mais de 4 horas, o descanso sobe para 24 horas.
- **Férias:** pelo menos 20 dias corridos por ano sem oncall, com aviso prévio de 30 dias no tracker. Durante o período, o produto opera em modo "read-only do PM" — só agentes automáticos fazem contenção básica, sem aprovar alteração.

## 6. Ferramentas do oncall

- **Dashboard consumido:** painel operacional descrito em `foundation-constraints.md §9` (RPS, p95, erro, disco).
- **Checklist diário:** quando a skill `/daily-check` existir (pós-Bloco 2), ela roda a bateria de verificações automáticas e gera o relatório.
- **Relatório diário:** `docs/reports/daily-YYYY-MM-DD.md` produzido pelo harness toda madrugada.

## 7. Situações em que o PM diz "não sei fazer"

- **Leitura de trace de erro de código:** o PM não é desenvolvedor. Ação: registrar incident, pedir ao agente `/explain-slice` ou equivalente a traduzir em linguagem de produto, decidir sim/não.
- **Operação direta em banco:** proibida ao PM. Qualquer alteração direta passa por agente + verifier + reviewer.
- **Criação de hotfix:** PM não escreve código. Pede ao agente para abrir slice emergencial com AC correspondente ao incidente.

## 8. Limites honestos do oncall atual

- Enquanto não houver tenant pagante, a política permite janela única diária.
- Quando houver primeiro tenant real, esta política é revisada e exige no mínimo duas janelas e um canal de alerta ativo verificado semanalmente.
- O Kalibrium **não** promete cobertura 24/7 ao cliente no MVP. A disponibilidade alvo é 99,5% (RNF-016), compatível com oncall humano solo e janelas de manutenção.

## 9. Cross-ref

`incident-response-playbook.md` (T2.5), `foundation-constraints.md §9`, `customer-support.md` (T3.11), `procurement-tracker.md`, `harness-limitations.md` (limites conhecidos do harness).
