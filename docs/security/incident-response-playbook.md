# Playbook de resposta a incidente — Kalibrium

> **Status:** `draft-awaiting-dpo`. Item T2.5 da Trilha #2. Depende de `threat-model.md` (T2.1). Cumpre o Art. 48 da LGPD quanto à notificação à ANPD em até 72 horas. Revisão formal do DPO obrigatória antes do primeiro tenant real.

## 1. Princípios

- **Transparência imediata ao PM.** Qualquer suspeita de incidente com dado pessoal gera aviso imediato ao PM, mesmo que depois se prove falso-alarme.
- **Preservação de evidência antes de conter.** Snapshot de logs e dump de estado antes de tocar em qualquer coisa.
- **Nada de heróis.** Ninguém resolve sozinho em silêncio. Incidente vira arquivo em `docs/incidents/` e puxa retrospectiva.

## 2. Classificação

### P0 — Crítico

- Vazamento cruzado entre tenants (T-007 do threat model).
- Vazamento confirmado de dado pessoal para atacante externo.
- Ransomware com cifragem de dados do produto.
- Acesso não autorizado a certificados de múltiplos tenants.
- Perda permanente e irrecuperável de dados de calibração.

**SLA de contenção:** 1 hora máxima desde detecção.
**SLA de notificação interna:** 15 minutos máximos.
**Notificação ANPD:** obrigatória em até 72 horas (Art. 48 LGPD).

### P1 — Importante

- Vazamento de dado pessoal de poucos titulares em único tenant.
- Exploração de vulnerabilidade conhecida sem vazamento confirmado.
- Indisponibilidade estendida acima de 4 horas (RTO RNF-008 estourado).
- Exportação indevida de CSV por usuário legítimo com intenção suspeita.
- Backup falhou e não há outro recente (RPO RNF-007 estourado).

**SLA de contenção:** 4 horas.
**SLA de notificação interna:** 1 hora.
**Notificação ANPD:** caso-a-caso com parecer do DPO.

### P2 — Baixo

- Tentativas de login falhadas em série sem sucesso.
- Vulnerabilidade descoberta mas sem exploração.
- Indisponibilidade curta (menor que 1 hora).
- Erro em formulário que impede conclusão de uma ação por usuário isolado.

**SLA de contenção:** 48 horas.
**SLA de notificação interna:** 24 horas.
**Notificação ANPD:** não aplicável na maioria dos casos.

## 3. Fluxo em 6 passos

### Passo 1 — Detecção

Origem possível: alerta automático (§9 de `foundation-constraints.md`), relato de usuário, descoberta durante verifier, auditoria externa. Toda detecção entra em `docs/incidents/<slug>-YYYY-MM-DD.md` em até 15 minutos.

### Passo 2 — Classificação

O primeiro responsável (PM por padrão, DPO se for incidente de dado pessoal) classifica P0/P1/P2 conforme §2. Classificação errada é aceitável quando conservadora — P1 tratado como P0 nunca é problema.

### Passo 3 — Contenção

- Isolar o recurso afetado (bloquear usuário, tirar instância do pool, revogar token).
- Preservar estado (snapshot de log, dump de banco parcial, screenshot da tela de erro).
- Comunicar usuários diretamente afetados se necessário.
- Nunca apagar evidência para "limpar" rastro do atacante.

### Passo 4 — Notificação à ANPD (apenas P0 e alguns P1)

Se o incidente envolve dado pessoal e é classificado como de risco relevante (Art. 48 LGPD):

1. DPO redige comunicação inicial em até 24 horas da classificação, usando modelo oficial da ANPD.
2. PM aprova.
3. Envio à ANPD pelo canal oficial dentro da janela de 72 horas.
4. Registro da comunicação em `docs/incidents/<slug>-YYYY-MM-DD.md` com número do protocolo.

Conteúdo mínimo da comunicação:
- Descrição da natureza do incidente.
- Categorias e número aproximado de titulares afetados.
- Categorias de dados afetados.
- Medidas técnicas e de segurança aplicadas.
- Riscos prováveis.
- Medidas adotadas para reverter ou mitigar.

### Passo 5 — Comunicação aos titulares

Se o incidente impõe risco alto aos titulares (por exemplo, vazamento de e-mail de cliente externo):

1. DPO redige texto de comunicação ao titular em linguagem simples.
2. PM aprova.
3. Envio por e-mail direto aos titulares identificáveis.
4. Quando não é possível identificar individualmente, publicação no portal de cada tenant afetado.
5. Prazo: conforme orientação da ANPD, tipicamente em paralelo à comunicação ao órgão.

### Passo 6 — Post-mortem

Em até 15 dias corridos após a contenção, retrospectiva obrigatória em `docs/retrospectives/incident-<slug>-YYYY-MM-DD.md` com:
- Linha do tempo (detecção, contenção, notificação, comunicação).
- Causa raiz (não "culpa", causa).
- O que funcionou.
- O que falhou.
- Ações corretivas concretas com data e responsável.
- Atualização de `threat-model.md` se ameaça não estava modelada.

## 4. Cenários modelados (mínimo 3, conforme critério do T2.5)

### Cenário 1 — Vazamento de certificado por bug no portal do cliente final

**O que aconteceu (hipótese de teste).** Um bug no controle de acesso do portal permitiu que um cliente ver histórico de outro cliente no mesmo tenant.

**Classificação:** P0 (vazamento de dado pessoal entre titulares distintos).

**Contenção esperada:** tirar o portal do ar imediatamente, validar escopo do bug via logs, identificar quais pares (atacante, vítima) aconteceram, bloquear acesso ao recurso afetado, aplicar hotfix.

**Notificação:** ANPD em até 72 horas; comunicação aos titulares afetados; comunicação ao tenant afetado para responder aos clientes finais em nome próprio.

**Post-mortem:** atualização de `threat-model.md` e criação de teste de regressão em `specs/NNN-portal-access/` para reproduzir o bug e provar que não volta.

### Cenário 2 — Vazamento de CPF de cliente por log indevido

**O que aconteceu.** O verifier descobriu durante auditoria rotineira que uma linha de log em produção contém CPF em texto puro (violação da política de log zero-PII).

**Classificação:** P1 (vazamento de dado pessoal em canal interno com acesso restrito).

**Contenção esperada:** remover a linha de log do código, limpar log acumulado, escrever migration para purgar histórico, revisar a categoria de log para garantir que não há outro caso. Treinar o verifier para flag similar no próximo post-edit-gate.

**Notificação:** caso-a-caso com DPO. Possivelmente não exige ANPD se o log não escapou do ambiente controlado, mas exige parecer formal do DPO.

**Post-mortem:** atualização do teste de post-edit-gate para fazer grep de padrão CPF em toda nova linha de log.

### Cenário 3 — Ransomware na VPS de produção

**O que aconteceu.** O host foi comprometido por exploit de nova vulnerabilidade do sistema operacional do provedor. Arquivos foram cifrados, incluindo o banco e os PDFs locais.

**Classificação:** P0 (indisponibilidade crítica + possível exposição de dados pessoais durante movimentação lateral).

**Contenção esperada:** isolar o host imediatamente, comunicar o provedor Hostinger, avaliar se houve exfiltração antes da cifragem, restaurar a partir do backup off-site mais recente (RPO 1h, RNF-007), medir janela de perda de dados, comunicar tenants e clientes finais afetados.

**Notificação:** ANPD em até 72 horas, explicitando se houve exfiltração confirmada ou apenas cifragem.

**Post-mortem:** atualização da política de backup (T2.6, pós-Bloco 2), contratação adicional de segundo canal de backup independente, revisão da política de atualização do host.

## 5. Papel de cada parte

- **PM** — classifica, aprova comunicações, decide pausa dura (P0).
- **DPO (quando contratado)** — redige comunicações à ANPD e aos titulares, assina a comunicação formal.
- **Consultor de segurança (quando contratado)** — ajuda na análise forense em P0.
- **Provedor de infraestrutura** — responde em incidente que envolve o host (ver `vendor-matrix.md`).

## 6. Pendências que dependem do DPO

1. Redação final dos modelos de comunicação (ANPD e titular).
2. Definição de quando exatamente se aplica notificação ANPD conforme orientação vigente.
3. Revisão dos SLA em função da experiência do DPO.
4. Criação de cenários adicionais baseados em incidentes recentes de outros SaaS multi-tenant brasileiros.

## 7. Cross-ref

`threat-model.md` (T2.1), `lgpd-base-legal.md` (T2.2), `dpia.md` (T2.3), `rot.md` (T2.4), `foundation-constraints.md §9` (observabilidade), `vendor-matrix.md` (fornecedores), `revalidation-calendar.md` (Art. 48 LGPD).
