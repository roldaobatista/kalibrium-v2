# Relatório de Impacto à Proteção de Dados (DPIA) — Kalibrium

> **Status:** `draft-awaiting-dpo`. Item T2.3 da Trilha #2. Template + conteúdo inicial conforme Art. 38 da LGPD. Depende de `threat-model.md` (T2.1) e `lgpd-base-legal.md` (T2.2). Revisão obrigatória pelo DPO antes do primeiro tenant real.

## 1. Identificação

- **Título:** DPIA v1 — Kalibrium MVP
- **Data desta versão:** 2026-04-10
- **Controlador avaliado:** laboratório cliente (modelo genérico antes da contratação dos primeiros tenants reais)
- **Operador avaliado:** Kalibrium Tecnologia (em constituição)
- **Encarregado (DPO):** a ser nomeado pelo controlador. No Kalibrium: DPO fracionário contratado (item do `procurement-tracker.md`).

## 2. Escopo

Esta DPIA cobre o tratamento de dados pessoais realizado pelo Kalibrium no escopo do MVP definido em `docs/product/mvp-scope.md`. Cobre as 9 finalidades listadas em `lgpd-base-legal.md` (T2.2). Não cobre finalidades fora do MVP (marketing, perfilamento, comercialização).

## 3. Descrição do tratamento

- **Natureza dos dados:** cadastrais e operacionais relacionados a pessoa de contato do cliente do laboratório, colaboradores do laboratório e ocasionalmente representante legal. Nenhum dado pessoal sensível (saúde, biometria, criança).
- **Volume estimado:** baseado no laboratório-tipo (`laboratorio-tipo.md`), cada tenant processa de 600 a 2.000 calibrações/mês, envolvendo tipicamente entre 50 e 300 contatos únicos por ano.
- **Origem dos dados:** informados diretamente pelo laboratório via cadastro operacional; parcialmente pelo próprio contato do cliente quando ele acessa o portal.
- **Destinatários:** internos ao tenant, mais eventualmente encaminhamento automático ao cliente (e-mail de certificado), mais fornecedores subcontratados listados em `vendor-matrix.md`.

## 4. Riscos identificados

Cruzamento direto com `threat-model.md` (T2.1). Riscos de maior severidade para proteção de dados pessoais:

| Risco | Impacto sobre o titular | Probabilidade | Severidade residual após mitigação |
|---|---|---|---|
| Vazamento cruzado entre tenants (T-007) | Alto — dados do titular viram visíveis a terceiros | Baixa (se RLS bem implementado) | Baixa |
| Vazamento por log indevido (T-008) | Médio — depende do log que vazou | Baixa (com política de log + grep) | Baixa |
| Roubo de sessão (T-002) | Médio — ações em nome do titular por tempo curto | Média (ameaça comum na web) | Baixa-média |
| Interceptação de tráfego (T-009) | Alto se TLS não ativo, zero se ativo | Muito baixa (TLS obrigatório) | Muito baixa |
| Backup corrompido (T-011) | Médio — perda parcial de dado do titular | Baixa | Baixa |
| Exportação de CSV por usuário legítimo com má intenção (T-014) | Médio | Média | Média |
| Prompt injection em futuras rotas que usem LLM no runtime do produto | Desconhecido no MVP (LLM não está na rota crítica) | — | A reavaliar quando houver LLM na rota |

## 5. Medidas mitigadoras

As medidas já listadas em `threat-model.md` (T2.1) cobrem as 15 ameaças STRIDE identificadas. Do ponto de vista de proteção de dados pessoais, as medidas-chave são:

- **Isolamento forte entre tenants** via RLS + teste automatizado de negação cruzada em `specs/000-isolation/` antes de qualquer slice de domínio.
- **Política de log zero-PII** — nenhum campo de dado pessoal direto em log de aplicação.
- **TLS 1.2+ obrigatório** com HSTS.
- **Audit log imutável** por 10 anos para defesa do titular em caso de disputa.
- **Exportação rastreada** com log obrigatório de cada CSV gerado.
- **Minimização de coleta** — nenhum dado sensível, nenhum CPF desnecessário.

## 6. Direitos do titular e canal de atendimento

Conforme `lgpd-base-legal.md §Direitos do titular`. O controlador (laboratório) é responsável pelo atendimento na interface do usuário. O Kalibrium fornece ferramentas técnicas (consulta, exportação, anonimização).

Canal provisório: e-mail monitorado pelo papel administrativo do tenant. Canal definitivo depende de aprovação do DPO.

## 7. Avaliação de necessidade e proporcionalidade

Cada finalidade tem base legal declarada em `lgpd-base-legal.md`. As bases legais escolhidas (execução de contrato, obrigação legal, legítimo interesse) são compatíveis com a natureza da operação do laboratório. Nenhuma finalidade atual depende exclusivamente de consentimento, o que reduz a superfície de "consentimento inválido".

## 8. Consulta ao titular

Não se aplica no MVP — nenhuma finalidade depende de consulta prévia ao titular sobre nova finalidade.

## 9. Pareceres externos

- **DPO:** aguardando contratação (vide `procurement-tracker.md`).
- **ANPD:** não foi consultada. Não é exigida consulta prévia no escopo atual.

## 10. Pendências que bloqueiam o status "aprovado"

1. Revisão do DPO sobre cada entrada da matriz de riscos.
2. Decisão sobre se cada risco residual "médio" precisa de mitigação adicional ou pode permanecer.
3. Redação do texto visível ao titular no portal.
4. Nomeação formal do DPO e registro na ANPD (se aplicável).
5. Revisão anual obrigatória.

## 11. Revisão

- **Cadência:** anual ou sempre que a arquitetura mudar materialmente.
- **Próxima revisão agendada:** 2027-04-10 (registrada em `revalidation-calendar.md`).
- **Gatilho de reavaliação extraordinária:** novo tipo de dado, novo vendor crítico, mudança na posição da ANPD, incidente classificado P0 ou P1.
