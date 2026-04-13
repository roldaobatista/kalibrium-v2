# E02 — Multi-tenancy, Auth e Planos

## Objetivo
Implementar o coração da plataforma SaaS: isolamento forte entre tenants, autenticação segura, RBAC, ciclo de vida do tenant (trial → ativo → suspenso) e motor de feature gates por plano. Sem este épico, nenhum módulo de negócio pode ser construído com segurança.

## Valor entregue
Um laboratório consegue se cadastrar como tenant isolado, criar usuários com papéis distintos (gerente, técnico, administrativo, visualizador) e ter seus dados completamente separados de qualquer outro laboratório no sistema.

## Escopo

### Multi-tenancy
- Implementação do modelo single-database com `tenant_id` + Row-Level Security (PostgreSQL RLS) via `stancl/tenancy` em modo single-database
- Global scope Eloquent injetado automaticamente em todos os models sensíveis ao tenant
- Isolamento verificável: dados de tenant A nunca visíveis para tenant B (teste de integração obrigatório)
- Entidades: Tenant, Empresa, Filial

### Autenticação
- Login por e-mail + senha (Laravel Fortify)
- 2FA por TOTP (Laravel Fortify nativo) — obrigatório para papéis gerente e administrativo
- Recuperação de senha por e-mail
- Gestão de sessão (logout, timeout, sessões concorrentes)

### RBAC
- Papéis canônicos: gerente, técnico, administrativo, visualizador
- `spatie/laravel-permission` com policies Laravel
- Permissões por papel × empresa (um usuário pode ter papéis diferentes em empresas distintas)
- Entidades: Usuário, Papel (Role)

### Ciclo de vida do tenant (FR-PRI-01..03)
- Estados: trial (30 dias) → ativo → [dunning] → suspenso (somente leitura) → cancelado → reativado
- 6 planos canônicos configuráveis: Starter, Basic, Professional, Business, Lab, Enterprise
- Feature gates por módulo: módulos fora do plano visíveis mas bloqueados com CTA de upgrade
- Alertas em 80% e 95% dos limites de entitlement (usuários, OS/mês, armazenamento GED)
- Upgrade/downgrade de plano com cálculo pro-rata automático
- Entidades: Assinatura

### Compliance base (REQ-CMP-004)
- Base legal LGPD registrada por categoria de dado pessoal no cadastro do tenant
- Consentimento LGPD para mensageria no cadastro do contato (FR-SEG-03)
- Opt-out LGPD por canal para comunicações de marketing (FR-EML-04)

## Fora de escopo
- SSO / SAML / SCIM / OIDC Enterprise (ADR-0004, post-MVP)
- Billing real (cobrança de assinatura ao tenant) — integração com gateway de pagamento é pós-MVP
- Gestão de parceiros de canal (pós-MVP)

## Critérios de entrada
- E01 completo (staging funcional, CI verde)

## Critérios de saída
- Tenant criado via seed/command com isolamento RLS verificado em teste automatizado
- Login funcional com 2FA para gerente
- RBAC aplicado: técnico não acessa rotas administrativas (verificado por teste)
- Feature gate bloqueia acesso a módulo fora do plano atual do tenant
- Ciclo trial → suspenso funcionando (verificado por teste de estado)
- Cobertura de testes: 100% dos ACs de isolamento

## Stories previstas
- E02-S01 — Multi-tenancy: scaffold stancl/tenancy + RLS PostgreSQL
- E02-S02 — Entidades Tenant, Empresa, Filial com migrations
- E02-S03 — Autenticação: login, 2FA, recuperação de senha
- E02-S04 — RBAC: papéis canônicos + spatie/laravel-permission
- E02-S05 — Ciclo de vida do tenant (estados e transições)
- E02-S06 — Motor de planos e feature gates (FR-PRI-01..03)
- E02-S07 — Base legal LGPD e consentimentos (REQ-CMP-004, FR-SEG-03)
- E02-S08 — Testes de isolamento entre tenants (segurança estrutural)

## Dependências
- E01 (infraestrutura, CI, deploy staging)

## Riscos
- RLS do PostgreSQL pode causar problema de performance em queries complexas — mitigado por índice em `tenant_id` desde o início
- stancl/tenancy em modo single-database requer atenção especial a jobs e filas (contexto de tenant precisa ser propagado) — risco médio, conhecido no ecossistema

## Complexidade estimada
- Stories: 8
- Complexidade relativa: alta
- Duração estimada: 2 semanas
