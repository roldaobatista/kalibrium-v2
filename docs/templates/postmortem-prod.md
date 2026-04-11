# Template — Postmortem de produção

> **Uso:** sempre que um incidente de produção for fechado. Item T3.12 da Trilha #3 e também referenciado pelo micro-ajuste 6.9. Distinto do template de retrospectiva de slice (`docs/retrospectives/`) porque trata de evento não-planejado em ambiente real, não de lição aprendida durante desenvolvimento.

**Copiar este arquivo para:** `docs/incidents/postmortem-<slug>-YYYY-MM-DD.md`. Preencher cada seção abaixo. Campos sem dado recebem a frase "sem dado disponível" com razão — nunca vazio.

---

## 1. Identificação

- **Título do incidente:** frase curta em português. Ex.: "Portal do cliente final retornou 500 em todas as consultas por 20 minutos".
- **Data e hora de detecção:** AAAA-MM-DD HH:MM (horário de Brasília).
- **Data e hora de contenção:** AAAA-MM-DD HH:MM.
- **Data e hora de resolução completa:** AAAA-MM-DD HH:MM.
- **Classificação final:** P0 / P1 / P2 (ver `docs/security/incident-response-playbook.md`).
- **Tenants afetados:** número + lista (sem PII).
- **Titulares afetados:** número aproximado (sem PII).
- **Autor do postmortem:** nome do papel (PM, DPO quando aplicável).

## 2. Resumo executivo (3 a 5 linhas)

Linguagem de produto, sem jargão (seguir `docs/product/glossary-pm.md`). O PM precisa conseguir ler e entender em menos de um minuto.

## 3. Linha do tempo

Tabela com um marco por linha, em horário de Brasília, do primeiro sinal até o fechamento.

| Hora | Origem do evento | O que aconteceu |
|---|---|---|
| HH:MM | [alerta automático / relato de usuário / verifier / outro] | ... |
| HH:MM | ... | ... |

Incluir pelo menos: primeiro sinal, primeira resposta humana, contenção, confirmação de contenção, comunicação externa (se houve), resolução.

## 4. Impacto

- **Impacto no usuário final:** o que ele viu / não conseguiu fazer.
- **Impacto nos dados:** houve perda? Corrupção? Vazamento suspeito?
- **Impacto financeiro:** estimativa se aplicável.
- **Impacto reputacional:** estimativa conservadora.
- **Impacto regulatório:** houve obrigação de notificar ANPD ou outro órgão? Sim/não + referência.

## 5. Causa raiz

Uma única seção, uma única causa. Se houver causa concorrente, listar como "causa contribuinte" separada.

- **Causa raiz (a única, a direta):** ...
- **Por que essa causa existia:** explicação estrutural, não pessoal. Nunca "culpa do fulano". Sempre "a arquitetura permitia X porque Y".
- **Causas contribuintes (opcional):** fatores que agravaram o impacto sem serem a origem.

## 6. O que funcionou

Prática saudável: reconhecer o que funcionou antes de apontar o que falhou. Ajuda a não quebrar defesas boas por excesso de correção.

- ...
- ...

## 7. O que falhou

- ...
- ...

## 8. Ações corretivas

Tabela com ação, responsável, data-alvo, status. Toda ação aqui entra em `docs/audits/progress/` até fechar.

| Ação | Responsável | Data-alvo | Status |
|---|---|---|---|
| Curto prazo (até 7 dias) | PM | AAAA-MM-DD | aberta / em andamento / fechada |
| Médio prazo (até 30 dias) | PM | AAAA-MM-DD | aberta |
| Longo prazo (até 90 dias) | PM | AAAA-MM-DD | aberta |

## 9. Lições aprendidas (não-atribuíveis)

Princípios gerais que passam a valer para o futuro mesmo sem conexão direta com este incidente específico. Exemplos:

- "Alertas de p95 precisam ter janela deslizante, não pontual."
- "Toda migração de schema precisa de canary antes de ir a produção."
- "Suspender notificação opcional quando o canal de entrega está em alerta."

## 10. Atualização de documentos que já existiam

Marcar quais documentos foram atualizados em função deste incidente e qual é o commit da atualização.

- `docs/security/threat-model.md` — alteração: ..., commit: ...
- `docs/security/incident-response-playbook.md` — alteração: ..., commit: ...
- `docs/product/nfr.md` — alteração: ..., commit: ...
- `docs/architecture/foundation-constraints.md` — alteração: ..., commit: ...

## 11. Comunicação externa

- **Foi necessário comunicar a ANPD?** Sim / não. Se sim, número do protocolo e data.
- **Foi necessário comunicar titulares?** Sim / não. Se sim, canal usado e data.
- **Foi necessário comunicar tenant afetado?** Sim / não + data + texto enviado (resumo).

## 12. Assinaturas

- **PM:** nome, data.
- **DPO** (se houve dado pessoal envolvido): nome, data.
- **Consultor de segurança** (se envolvido na forense): nome, data.
- **Aprovação final para fechar o incidente:** PM, data.

---

## Anexos recomendados

- Gráficos de métricas antes/durante/depois (como imagens ou links para o painel).
- Trechos de log relevantes (sem dado pessoal).
- Capturas de tela do erro do usuário (anonimizadas).
- Diferença de código entre versão pré-incidente e pós-correção (se houve hotfix).

## Notas de uso deste template

1. **Nunca deletar seção.** Se não se aplica, escrever "não aplicável" e a razão.
2. **Nunca misturar em um único documento dois incidentes.** Dois eventos = dois postmortems.
3. **Prazo para primeiro rascunho:** 5 dias úteis após contenção. Rascunho cru, com lacunas declaradas.
4. **Prazo para versão final:** 15 dias corridos após contenção.
5. **Referência cruzada obrigatória:** adicionar entrada no tracker do plano de ação quando gerar ações corretivas.
