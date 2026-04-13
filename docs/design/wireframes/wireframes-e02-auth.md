# Wireframes E02 — Multi-tenancy, Auth e Planos

> **Status:** draft — aguardando revisão do PM.
> **Data:** 2026-04-13.
> **Epico:** E02 — Multi-tenancy, Auth e Planos.
> **Documento do gate:** D.1 — Wireframes + screen inventory do epico.
> **Base:** `epics/E02/epic.md`, `docs/product/sitemap.md`, `docs/design/screen-inventory.md`, `docs/product/rbac-screen-matrix.md`, ADR-0004.

---

## 1. Inventario do epico

| ID | Tela | URL | Tipo | Persona primaria | Papel minimo |
|---|---|---|---|---|---|
| SCR-E02-001 | Entrada do produto | `/` | redirect/public | Marcelo | anonimo |
| SCR-E02-002 | Login | `/auth/login` | form | Marcelo, Juliana | anonimo |
| SCR-E02-003 | Recuperar senha | `/auth/forgot-password` | form | Marcelo, Juliana | anonimo |
| SCR-E02-004 | Redefinir senha | `/auth/reset-password/{token}` | form | Marcelo, Juliana | anonimo |
| SCR-E02-005 | Desafio 2FA | `/auth/two-factor-challenge` | form | Marcelo | sessao 2FA pendente |
| SCR-E02-006 | Configuracoes do tenant | `/settings/tenant` | form | Marcelo | gerente |
| SCR-E02-007 | Usuarios e papeis | `/settings/users` | list | Marcelo | gerente |
| SCR-E02-008 | Planos e limites | `/settings/plans` | dashboard | Marcelo | gerente |
| SCR-E02-009 | Base legal e consentimentos | `/settings/privacy` | form | Marcelo | gerente |
| SCR-ADM-001 | Tenants | `/admin/tenants` | list | Suporte Kalibrium | suporte-kalibrium |
| SCR-ADM-002 | Auditoria de suporte | `/admin/support-audit` | audit list | Suporte Kalibrium | suporte-kalibrium |

---

## 2. Wireframes principais

### SCR-E02-001 — Entrada do produto (`/`)

```text
┌──────────────────────────────────────────────┐
│ Kalibrium                                    │
│ Plataforma de operacao para laboratorio       │
│                                              │
│ [Entrar no laboratorio]  [Portal do cliente] │
│                                              │
│ Sessao ativa? redireciona para /app          │
└──────────────────────────────────────────────┘
```

Regras:
- Se houver sessão ativa, redirecionar para `/app`.
- Não mostrar dado de tenant para visitante anônimo.

### SCR-E02-002 — Login (`/auth/login`)

```text
┌──────────────────────────────────────────────┐
│ Entrar no laboratorio                        │
│ E-mail                                       │
│ [marcelo@laboratorio.com.br              ]  │
│ Senha                         [mostrar]      │
│ [••••••••••••                            ]  │
│ [ ] Manter conectado neste dispositivo       │
│                                              │
│ [Entrar]                                     │
│ Esqueci minha senha                          │
│                                              │
│ Erro: E-mail ou senha incorretos.            │
└──────────────────────────────────────────────┘
```

Regras:
- Mensagem de erro não revela se o e-mail existe.
- Gerente e administrativo seguem para 2FA quando exigido.
- Rate limit usa mensagem segura: "Muitas tentativas. Tente novamente em alguns minutos."

### SCR-E02-003 — Recuperar senha (`/auth/forgot-password`)

```text
┌──────────────────────────────────────────────┐
│ Recuperar senha                              │
│ Informe seu e-mail. Se ele existir,          │
│ enviaremos um link de redefinicao.           │
│                                              │
│ E-mail                                       │
│ [usuario@laboratorio.com.br               ]  │
│                                              │
│ [Enviar link]                                │
│ Voltar para login                            │
└──────────────────────────────────────────────┘
```

Regras:
- Resposta sempre neutra.
- Link de redefinição expira.

### SCR-E02-004 — Redefinir senha (`/auth/reset-password/{token}`)

```text
┌──────────────────────────────────────────────┐
│ Criar nova senha                             │
│ E-mail                                       │
│ [usuario@laboratorio.com.br               ]  │
│ Nova senha                                   │
│ [••••••••••••                            ]  │
│ Minimo: 12 caracteres                        │
│ Confirmar nova senha                         │
│ [••••••••••••                            ]  │
│                                              │
│ [Salvar nova senha]                          │
└──────────────────────────────────────────────┘
```

Regras:
- Token inválido ou expirado mostra alerta e link para pedir novo e-mail.
- Após redefinir, sessões antigas devem ser invalidadas quando suportado.

### SCR-E02-005 — Desafio 2FA (`/auth/two-factor-challenge`)

```text
┌──────────────────────────────────────────────┐
│ Verificacao em duas etapas                   │
│ Digite o codigo do seu aplicativo            │
│ autenticador.                                │
│                                              │
│ Codigo de 6 digitos                          │
│ [ _  _  _  _  _  _ ]                         │
│                                              │
│ [Verificar]                                  │
│ Usar codigo de recuperacao                   │
│ Sair                                         │
└──────────────────────────────────────────────┘
```

Regras:
- Códigos de recuperação são aceitos como alternativa.
- Uso de código de recuperação gera log de auditoria.

### SCR-E02-006 — Configuracoes do tenant (`/settings/tenant`)

```text
Breadcrumb: Configuracoes > Laboratorio

Laboratorio
[Dados gerais] [Empresas e filiais] [Perfil operacional]

┌──────────────────────────────────────────────┐
│ Razao social *     [Laboratorio ABC Ltda ]   │
│ CNPJ *             [00.000.000/0000-00 ]     │
│ Nome fantasia      [ABC Calibracoes    ]     │
│ E-mail principal   [contato@abc.com.br ]     │
│ Telefone           [(66) 99999-0000    ]     │
│                                              │
│ [Salvar alteracoes] [Cancelar]               │
└──────────────────────────────────────────────┘
```

Regras:
- Somente gerente edita.
- Mudança de perfil operacional exige confirmação e log.

### SCR-E02-007 — Usuarios e papeis (`/settings/users`)

```text
Usuarios e papeis                       [+ Convidar usuario]

Busca [ nome ou e-mail             ] Papel [todos v]

┌──────────────────────────────────────────────────────────────┐
│ Nome        E-mail                 Papel          2FA        │
│ Marcelo     marcelo@lab.com.br     gerente        ativo      │
│ Juliana     juliana@lab.com.br     tecnico        opcional   │
│ Ana         ana@lab.com.br         administrativo pendente   │
└──────────────────────────────────────────────────────────────┘

Modal: Convidar usuario
Nome, e-mail, papel, empresa/filial, obrigar 2FA.
```

Regras:
- Não permitir remover o último gerente.
- Alterar papel exige 2FA ativo do gerente logado.

### SCR-E02-008 — Planos e limites (`/settings/plans`)

```text
Plano atual: Basic                         Status: ativo

┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Usuarios     │ │ OS no mes     │ │ Armazenamento│
│ 8 de 10      │ │ 145 de 200    │ │ 3.2 de 5 GB │
│ 80%          │ │ 72%           │ │ 64%          │
└──────────────┘ └──────────────┘ └──────────────┘

Modulos
Metrologia: liberado
Fiscal: bloqueado no Starter [Ver upgrade]
Portal cliente: liberado
```

Regras:
- Alertas em 80% e 95% dos limites.
- Módulo fora do plano aparece bloqueado para gerente com CTA.

### SCR-E02-009 — Base legal e consentimentos (`/settings/privacy`)

```text
Base legal e consentimentos
[Categorias LGPD] [Consentimentos] [Opt-out]

┌──────────────────────────────────────────────────────────────┐
│ Categoria        Base legal           Retencao               │
│ Contato cliente  execucao contrato    10 anos                │
│ Usuario interno  execucao contrato    enquanto ativo         │
│ Marketing        consentimento        ate opt-out            │
└──────────────────────────────────────────────────────────────┘

[Salvar politica]
```

Regras:
- Consentimento de marketing é separado de e-mail transacional obrigatório.
- Alterações geram log de auditoria.

### SCR-ADM-001 — Tenants (`/admin/tenants`)

```text
Admin Kalibrium > Tenants
Busca [ laboratorio, CNPJ, plano ] Status [todos v]

┌──────────────────────────────────────────────────────────────┐
│ Tenant             Plano     Status     Saude       Acoes    │
│ Laboratorio ABC    Basic     ativo      ok          ver      │
│ Lab XYZ            Trial     dunning    atencao     ver      │
└──────────────────────────────────────────────────────────────┘
```

Regra: somente `suporte-kalibrium`, sem dados técnicos de calibração.

### SCR-ADM-002 — Auditoria de suporte (`/admin/support-audit`)

```text
Admin Kalibrium > Auditoria de suporte
Periodo [01/04/2026 - 13/04/2026] Usuario [todos v]

┌──────────────────────────────────────────────────────────────┐
│ Data/hora       Suporte       Tenant       Acao              │
│ 13/04 09:12     operador      ABC          abriu detalhe     │
│ 13/04 09:15     operador      ABC          alterou status    │
└──────────────────────────────────────────────────────────────┘
```

Regra: append-only, sem edição manual.

---

## 3. Checklist do PM

- [ ] Login, recuperação de senha e 2FA fazem sentido para o primeiro cliente.
- [ ] Telas de configuração cobrem dados mínimos do laboratório.
- [ ] Tela de usuários deixa claro quem pode convidar e alterar papéis.
- [ ] Plano/limites explica bloqueios sem linguagem técnica.
- [ ] Privacidade separa consentimento de marketing e comunicação obrigatória.
