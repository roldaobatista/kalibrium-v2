# Sitemap — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** G.17
> **Dependencias:** `epics/ROADMAP.md`, `docs/product/mvp-scope.md`, `docs/product/personas.md`, `docs/product/journeys.md`, `docs/design/layout-master.md`

---

## 1. Objetivo

Mapa mestre de navegacao do Kalibrium V2. Este arquivo e a fonte para rotas, wireframes, inventario de telas e matriz RBAC.

Escopo:
- back-office autenticado do laboratorio;
- telas publicas de autenticacao;
- portal do cliente final;
- telas P1 previstas no roadmap, marcadas como pos-MVP;
- operacao interna minima do suporte Kalibrium.

---

## 2. Convencoes

| Prefixo | Uso |
|---|---|
| `/` | entrada publica e redirecionamento inicial |
| `/auth` | autenticacao do back-office |
| `/app` | shell autenticado do laboratorio |
| `/settings` | tenant, usuarios, planos e comunicacao |
| `/portal` | portal do cliente final |
| `/admin` | operacao interna Kalibrium, fora do tenant |

| Role | Descricao |
|---|---|
| `gerente` | responsavel tecnico/admin do tenant, aprova e configura |
| `tecnico` | tecnico calibrador, atua na fila, bancada e evidencias |
| `administrativo` | cadastro, atendimento, fiscal, financeiro e comunicacao |
| `visualizador` | leitura operacional, auditoria e consulta interna |
| `cliente-final` | usuario externo do portal vinculado a um cliente |
| `suporte-kalibrium` | usuario interno, acesso restrito e auditado |

---

## 3. Arvore de Navegacao

```text
/
├── auth/
│   ├── login
│   ├── forgot-password
│   ├── reset-password/{token}
│   └── two-factor-challenge
├── app/
│   └── dashboard
├── clientes
├── instrumentos
├── padroes
├── procedimentos
├── ordens-servico
├── agenda
├── fila-tecnica
├── bancada
├── calibracoes
├── certificados
├── fiscal/
│   └── notas
├── financeiro/
│   ├── titulos
│   ├── conciliacao
│   └── exportacoes
├── documentos
├── relatorios
├── notificacoes
├── fornecedores
├── compras/
│   ├── requisicoes
│   ├── cotacoes
│   └── pedidos
├── habilitacoes
├── treinamentos
├── settings/
│   ├── tenant
│   ├── users
│   ├── plans
│   ├── privacy
│   ├── notificacoes
│   └── comunicacao
├── portal/
│   ├── login
│   ├── certificados
│   ├── instrumentos
│   └── perfil
└── admin/
    ├── tenants
    └── support-audit
```

---

## 4. Sitemap por Modulo

### 4.1. Publico e autenticacao

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Entrada do produto | `/` | E02 | Marcelo | anonimo |
| Login | `/auth/login` | E02 | Marcelo, Juliana | anonimo |
| Recuperar senha | `/auth/forgot-password` | E02 | Marcelo, Juliana | anonimo |
| Redefinir senha | `/auth/reset-password/{token}` | E02 | Marcelo, Juliana | anonimo |
| Desafio 2FA | `/auth/two-factor-challenge` | E02 | Marcelo | anonimo com sessao 2FA pendente |

### 4.2. Tenant, usuarios e planos

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Dashboard inicial | `/app` | E11 | Marcelo | `visualizador` |
| Dashboard operacional | `/app/dashboard` | E11 | Marcelo | `visualizador` |
| Configuracoes do tenant | `/settings/tenant` | E02 | Marcelo | `gerente` |
| Usuarios e papeis | `/settings/users` | E02 | Marcelo | `gerente` |
| Planos e limites | `/settings/plans` | E02 | Marcelo | `gerente` |
| Base legal e consentimentos | `/settings/privacy` | E02 | Marcelo | `gerente` |
| Configuracao de notificacoes | `/settings/notificacoes` | E12 | Marcelo | `gerente` |
| Log de comunicacao | `/settings/comunicacao/log` | E12 | Marcelo | `administrativo` |
| Templates de e-mail | `/settings/comunicacao/templates` | E12 | Marcelo | `gerente` |

### 4.3. Cadastro core

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Clientes | `/clientes` | E03 | Marcelo | `administrativo` |
| Novo cliente | `/clientes/novo` | E03 | Marcelo | `administrativo` |
| Detalhe do cliente | `/clientes/{cliente}` | E03 | Marcelo | `administrativo` |
| Editar cliente | `/clientes/{cliente}/editar` | E03 | Marcelo | `administrativo` |
| Instrumentos | `/instrumentos` | E03 | Marcelo | `administrativo` |
| Novo instrumento | `/instrumentos/novo` | E03 | Marcelo | `administrativo` |
| Detalhe do instrumento | `/instrumentos/{instrumento}` | E03 | Marcelo, Rafael | `administrativo` |
| Padroes de referencia | `/padroes` | E03 | Marcelo, Juliana | `tecnico` |
| Novo padrao | `/padroes/novo` | E03 | Marcelo | `gerente` |
| Detalhe do padrao | `/padroes/{padrao}` | E03 | Marcelo, Juliana | `tecnico` |
| Procedimentos | `/procedimentos` | E03 | Marcelo, Juliana | `tecnico` |
| Detalhe do procedimento | `/procedimentos/{procedimento}` | E03 | Juliana | `tecnico` |

### 4.4. Ordens de servico e operacao

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Ordens de servico | `/ordens-servico` | E04 | Marcelo | `administrativo` |
| Nova OS | `/ordens-servico/nova` | E04 | Marcelo | `administrativo` |
| Detalhe da OS | `/ordens-servico/{os}` | E04 | Marcelo, Juliana | `tecnico` |
| Editar OS | `/ordens-servico/{os}/editar` | E04 | Marcelo | `administrativo` |
| Agenda | `/agenda` | E04 | Marcelo | `administrativo` |
| Fila tecnica | `/fila-tecnica` | E04 | Juliana | `tecnico` |
| Checklist da OS | `/ordens-servico/{os}/checklist` | E04 | Juliana | `tecnico` |

### 4.5. Laboratorio e certificados

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Bancada tecnica | `/bancada` | E05 | Juliana | `tecnico` |
| Execucao de calibracao | `/calibracoes/{calibracao}` | E05 | Juliana | `tecnico` |
| Nova calibracao da OS | `/ordens-servico/{os}/calibracao` | E05 | Juliana | `tecnico` |
| Orcamento de incerteza | `/procedimentos/{procedimento}/incerteza` | E05 | Juliana | `tecnico` |
| Historico tecnico do instrumento | `/instrumentos/{instrumento}/calibracoes` | E05 | Marcelo, Juliana | `tecnico` |
| Lacres e selos da OS | `/ordens-servico/{os}/lacres` | E05 | Juliana | `tecnico` |
| Certificados | `/certificados` | E06 | Marcelo | `visualizador` |
| Revisao de certificado | `/certificados/{certificado}/revisao` | E06 | Marcelo | `gerente` |
| Detalhe do certificado | `/certificados/{certificado}` | E06 | Marcelo | `visualizador` |
| Preview PDF | `/certificados/{certificado}/preview` | E06 | Marcelo | `visualizador` |
| Revogar certificado | `/certificados/{certificado}/revogar` | E06 | Marcelo | `gerente` |

### 4.6. Fiscal e financeiro

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| NFS-e | `/fiscal/notas` | E07 | Marcelo | `administrativo` |
| Detalhe da NFS-e | `/fiscal/notas/{nota}` | E07 | Marcelo | `administrativo` |
| Reprocessar nota rejeitada | `/fiscal/notas/{nota}/reprocessar` | E07 | Marcelo | `administrativo` |
| Titulos a receber | `/financeiro/titulos` | E08 | Marcelo | `administrativo` |
| Detalhe do titulo | `/financeiro/titulos/{titulo}` | E08 | Marcelo | `administrativo` |
| Baixa manual | `/financeiro/titulos/{titulo}/baixa` | E08 | Marcelo | `administrativo` |
| Conciliacao manual | `/financeiro/conciliacao` | E08 | Marcelo | `administrativo` |
| Exportacao contabil | `/financeiro/exportacoes` | E08 | Marcelo | `administrativo` |

### 4.7. Portal, GED, relatorios e notificacoes

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Login do portal | `/portal/login` | E09 | Rafael | anonimo |
| Home do portal | `/portal` | E09 | Rafael | `cliente-final` |
| Certificados do cliente | `/portal/certificados` | E09 | Rafael | `cliente-final` |
| Detalhe do certificado | `/portal/certificados/{certificado}` | E09 | Rafael | `cliente-final` |
| Instrumentos do cliente | `/portal/instrumentos` | E09 | Rafael | `cliente-final` |
| Detalhe do instrumento | `/portal/instrumentos/{instrumento}` | E09 | Rafael | `cliente-final` |
| Perfil do portal | `/portal/perfil` | E09 | Rafael | `cliente-final` |
| Documentos GED | `/documentos` | E10 | Marcelo | `visualizador` |
| Detalhe do documento | `/documentos/{documento}` | E10 | Marcelo | `visualizador` |
| Upload de documento | `/documentos/novo` | E10 | Marcelo | `administrativo` |
| Relatorios | `/relatorios` | E11 | Marcelo | `visualizador` |
| Notificacoes | `/notificacoes` | E12 | Marcelo, Juliana | `visualizador` |

### 4.8. P1 mapeado

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Fornecedores | `/fornecedores` | E13 | Marcelo | `administrativo` |
| Detalhe do fornecedor | `/fornecedores/{fornecedor}` | E13 | Marcelo | `administrativo` |
| Requisicoes de compra | `/compras/requisicoes` | E13 | Marcelo | `tecnico` |
| Cotacoes | `/compras/cotacoes` | E13 | Marcelo | `administrativo` |
| Pedidos de compra | `/compras/pedidos` | E13 | Marcelo | `administrativo` |
| Habilitacoes tecnicas | `/habilitacoes` | E14 | Marcelo | `gerente` |
| Matriz de competencias | `/habilitacoes/matriz` | E14 | Marcelo | `gerente` |
| Treinamentos | `/treinamentos` | E14 | Marcelo | `gerente` |

### 4.9. Operacao interna Kalibrium

| Tela | URL pattern | Epico | Persona primaria | Role minima |
|---|---|---|---|---|
| Tenants | `/admin/tenants` | E02 | Suporte Kalibrium | `suporte-kalibrium` |
| Auditoria de suporte | `/admin/support-audit` | E02 | Suporte Kalibrium | `suporte-kalibrium` |

---

## 5. Regras de Navegacao

- O menu lateral autenticado lista apenas modulos permitidos por papel e plano.
- Feature gate fora do plano pode aparecer desabilitado para `gerente`, com CTA de upgrade, e deve ficar oculto para demais papeis.
- Portal do cliente nunca compartilha shell com back-office.
- `/admin/*` nunca roda no contexto visual do tenant e deve registrar todo acesso.
- Telas de certificado emitido, NFS-e autorizada e OS imutavel entram em modo leitura com acoes corretivas controladas.

---

## 6. Checklist

| Pergunta | Regra |
|---|---|
| A tela aparece neste sitemap? | Obrigatorio antes de wireframe |
| A URL segue `docs/architecture/naming-conventions.md`? | Obrigatorio |
| A role minima esta definida? | Obrigatorio |
| A tela pertence a um epico do roadmap? | Obrigatorio |
| A tela tem entrada no screen inventory? | Obrigatorio |
| A tela tem permissao na matriz RBAC? | Obrigatorio |
