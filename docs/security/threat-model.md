# Threat model — Kalibrium

> **Status:** `draft-awaiting-dpo`. Item T2.1 da Trilha #2. Aplica STRIDE sobre `docs/architecture/foundation-constraints.md` (1.5.8). Mínimo 12 ameaças identificadas. Revisão externa obrigatória pelo DPO ou por engenheiro de segurança horista antes do primeiro tenant real.

## Contexto

O Kalibrium é um monolito modular (foundation §1), multi-tenant por RLS (§2), web responsivo (§3), deploy em VPS Hostinger (§5), autenticação local com OAuth opcional (§6). O threat model abaixo é o primeiro corte STRIDE sobre essa arquitetura. Está deliberadamente conservador — o DPO pode reduzir mitigação quando houver justificativa, nunca aumentar risco aceito sem ADR.

## Legenda STRIDE

- **S** — Spoofing (representação falsa de identidade)
- **T** — Tampering (alteração indevida de dado)
- **R** — Repudiation (negar que fez)
- **I** — Information disclosure (vazamento de informação)
- **D** — Denial of service (tirar do ar)
- **E** — Elevation of privilege (ganhar permissão não concedida)

## Ameaças identificadas

### T-001 (S) — Atacante autentica-se como outro usuário do mesmo tenant

**Superfície:** login local + OAuth.
**Impacto:** acesso não autorizado a certificados, alteração de aprovação.
**Mitigação:** Argon2id/bcrypt com parâmetros modernos, MFA obrigatório para papel gerente, rate limit em login (5 tentativas/15 minutos), bloqueio temporário após 10 tentativas falhas, log de todos os logins em tabela imutável.

### T-002 (S) — Atacante assume sessão válida via roubo de cookie

**Superfície:** sessão web.
**Impacto:** ação em nome da vítima até expiração.
**Mitigação:** cookie `HttpOnly` + `Secure` + `SameSite=Lax`, rotação de `session_id` em cada escalonamento de privilégio, expiração por inatividade (12h) e absoluta (7d) conforme RNF e foundation §6.

### T-003 (S) — Atacante usa link assinado do portal do cliente final após expiração

**Superfície:** link público assinado para acesso de cliente externo a certificado.
**Impacto:** acesso fora de janela autorizada.
**Mitigação:** TTL máximo de 72h no token assinado, invalidação quando o próprio certificado é substituído, log de uso do link.

### T-004 (T) — Atacante modifica resultado de calibração após emissão

**Superfície:** endpoint de gravação de certificado.
**Impacto:** certificado adulterado vai para o cliente e para auditoria RBC.
**Mitigação:** campo `certificate` é imutável pós-emissão (foundation §4), hash do conteúdo armazenado em tabela separada, qualquer tentativa de `UPDATE` na linha é bloqueada por trigger e gera incident crítico.

### T-005 (T) — Atacante modifica padrão de referência para forjar rastreabilidade

**Superfície:** cadastro de padrão.
**Impacto:** certificado aparenta ter sido feito com padrão válido que na realidade estava vencido.
**Mitigação:** histórico de padrão em append-only, versionamento de cada alteração, regra dura do foundation §2 (padrão vencido bloqueia lançamento de nova calibração).

### T-006 (R) — Operador nega ter emitido certificado específico

**Superfície:** qualquer ação do usuário.
**Impacto:** laboratório perde defesa em auditoria ou processo judicial.
**Mitigação:** audit log imutável com `user_id`, `tenant_id`, `timestamp`, ação, hash do estado antes e depois. Retenção conforme RNF-009 (10 anos).

### T-007 (I) — Vazamento cruzado entre tenants por falha de RLS

**Superfície:** consulta de banco.
**Impacto:** tenant A lê dados de tenant B — **incidente crítico regulatório**.
**Mitigação:** RLS habilitado em todas as tabelas com `tenant_id`, testes automatizados de negação cruzada em `specs/000-isolation/` como primeiro slice após ADR-0001, verifier R11 roda esse slice sempre que migração toca schema.

### T-008 (I) — Vazamento de dados pessoais por log indevido

**Superfície:** logs de aplicação.
**Impacto:** LGPD + ANPD.
**Mitigação:** política de log: nenhum campo de dado pessoal direto em log de aplicação, apenas `user_id` opaco. Teste de grep em log de produção como parte do post-edit-gate. Treinamento do verifier para flag log cru.

### T-009 (I) — Interceptação de tráfego em trânsito

**Superfície:** rede.
**Impacto:** vazamento de credencial e de certificado em trânsito.
**Mitigação:** TLS 1.2 ou superior obrigatório, HSTS ativo, redirecionamento 301 de HTTP para HTTPS, nenhum endpoint responde em HTTP puro.

### T-010 (D) — Flood de requisições derruba a instância

**Superfície:** rede, aplicação.
**Impacto:** downtime, SLA quebrado.
**Mitigação:** rate limit por IP + por tenant, throttling global, plano B de redirecionar tráfego para segunda instância via proxy reverso. Alerta quando RPS médio por tenant ultrapassa 3x o pico esperado.

### T-011 (D) — Backup corrompido no momento do restore

**Superfície:** backup off-site.
**Impacto:** perda de dados em incidente maior.
**Mitigação:** backup testado por restore em ambiente separado a cada 30 dias, hash do backup comparado antes e depois, RTO 4h exigido pelo RNF-008.

### T-012 (E) — Usuário com papel visualizador escala para gerente

**Superfície:** autorização em rotas protegidas.
**Impacto:** ação destrutiva por usuário não autorizado.
**Mitigação:** verificação de papel em toda rota não-anônima, teste automatizado por papel × rota em matriz, verifier R11 exige cobertura completa da matriz antes de merge.

### T-013 (E) — Admin do laboratório (gerente) se eleva para admin global do Kalibrium

**Superfície:** tabela de papéis.
**Impacto:** acesso cross-tenant.
**Mitigação:** tabela de papéis separada de usuários de laboratório; nenhum usuário de tenant pode ter papel de admin global por design; admin global existe apenas como registro manual direto em banco (procedimento controlado).

### T-014 (I) — Dado de calibração exportado via canal não autorizado (CSV, print)

**Superfície:** exportações legítimas.
**Impacto:** vazamento sob controle do usuário legítimo.
**Mitigação:** log de toda exportação (quem, quando, quantos registros), watermark opcional, política de retenção do log de exportação por 10 anos.

### T-015 (T) — Injeção em campo de texto (ex: descrição de instrumento) que corrompe PDF do certificado

**Superfície:** gerador de PDF.
**Impacto:** certificado inválido ou com conteúdo malicioso.
**Mitigação:** sanitização de entrada em cada campo livre, biblioteca de geração de PDF com escape automático, teste de caso "payload em cada campo" como parte do conjunto de regressão.

## Pendências que dependem do DPO

- Revisão de cada mitigação contra boas práticas atuais.
- Adição de ameaças específicas para IA generativa (prompt injection em prompt de agente, leakage de prompt do sistema) quando o Bloco 2 definir se haverá chamada de LLM no runtime do produto.
- Validação do modelo de papéis contra a LGPD quanto a mínimo privilégio.

## Cross-ref

`foundation-constraints.md` (1.5.8), `lgpd-base-legal.md` (T2.2), `dpia.md` (T2.3), `incident-response-playbook.md` (T2.5), `revalidation-calendar.md` (T2.16).
