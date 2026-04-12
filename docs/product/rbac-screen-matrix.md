# RBAC Screen Matrix — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** G.20
> **Dependencias:** `epics/E02/epic.md`, `docs/product/sitemap.md`, `docs/design/screen-inventory.md`, `docs/architecture/foundation-constraints.md`

---

## 1. Objetivo

Este documento define permissao por grupo de tela antes da implementacao de rotas, policies e menus.

Roles canonicas do tenant, conforme E02:
- `gerente`;
- `tecnico`;
- `administrativo`;
- `visualizador`.

Roles externas ou internas:
- `cliente-final`: usuario externo do portal, vinculado a cliente/contato;
- `suporte-kalibrium`: usuario interno do SaaS, fora do tenant, com acesso operacional restrito e auditado.

---

## 2. Legenda

| Codigo | Significado |
|---|---|
| `-` | sem acesso |
| `R` | pode ver/listar |
| `C` | pode criar |
| `U` | pode editar antes de estado imutavel |
| `A` | pode aprovar ou executar acao de gate |
| `X` | pode exportar ou baixar arquivo |
| `D` | pode executar acao destrutiva ou revogacao controlada |
| `S` | acesso de suporte, exige justificativa e log |

Regras globais:
- permissao de UI nunca substitui policy server-side;
- `tenant_id` e empresa ativa fazem parte de toda verificacao;
- `gerente` e `administrativo` exigem 2FA;
- estado imutavel reduz `U` para `R` mesmo quando a role teria edicao;
- feature gate do plano pode bloquear `C`, `U`, `A` e `D` mantendo `R` quando o tenant estiver suspenso.

---

## 3. Matriz por Grupo de Tela

| Grupo de telas | URLs | Gerente | Tecnico | Administrativo | Visualizador | Cliente final | Suporte Kalibrium |
|---|---|---|---|---|---|---|---|
| Autenticacao | `/auth/*`, `/portal/login` | R | R | R | R | R | R |
| Dashboard operacional | `/app`, `/app/dashboard` | R X | R limitado | R | R | - | S |
| Tenant e empresas | `/settings/tenant` | R U | - | R | R | - | S |
| Usuarios e papeis | `/settings/users` | R C U D | - | R | - | - | S |
| Planos e limites | `/settings/plans` | R U | - | R | R | - | S |
| Privacidade e LGPD | `/settings/privacy` | R U | - | R U | R | - | S |
| Configuracao de notificacoes | `/settings/notificacoes`, `/settings/comunicacao/templates` | R C U | - | R U | R | - | S |
| Log de comunicacao | `/settings/comunicacao/log` | R X | - | R X | R | - | S |
| Clientes | `/clientes`, `/clientes/{cliente}` | R C U X | R limitado | R C U X | R | - | S |
| Contatos de cliente | dentro de `/clientes/*` | R C U | - | R C U | R | R proprio | S |
| Instrumentos | `/instrumentos`, `/instrumentos/{instrumento}` | R C U X | R | R C U X | R | R proprio | S |
| Padroes de referencia | `/padroes`, `/padroes/{padrao}` | R C U X | R | R | R | - | S |
| Procedimentos | `/procedimentos`, `/procedimentos/{procedimento}` | R C U X | R | R | R | - | S |
| Orcamento de incerteza | `/procedimentos/{procedimento}/incerteza` | R A X | R U | R | R | - | S |
| Ordens de servico | `/ordens-servico`, `/ordens-servico/{os}` | R C U X | R U limitado | R C U X | R | - | S |
| Criacao de OS | `/ordens-servico/nova` | C | - | C | - | - | S |
| Agenda | `/agenda` | R C U | R limitado | R C U | R | - | S |
| Fila tecnica | `/fila-tecnica` | R | R U proprio | R | R | - | S |
| Checklist de OS | `/ordens-servico/{os}/checklist` | R U | R U proprio | R | R | - | S |
| Bancada tecnica | `/bancada` | R | R U proprio | - | - | - | S |
| Execucao de calibracao | `/calibracoes/{calibracao}`, `/ordens-servico/{os}/calibracao` | R A | R C U proprio | - | R | - | S |
| Lacres e selos | `/ordens-servico/{os}/lacres` | R A | R C U proprio | R | R | - | S |
| Certificados | `/certificados`, `/certificados/{certificado}` | R X | R | R X | R | - | S |
| Revisao de certificado | `/certificados/{certificado}/revisao` | R A | - | R | R | - | S |
| Preview PDF | `/certificados/{certificado}/preview` | R X | R | R X | R | - | S |
| Revogacao de certificado | `/certificados/{certificado}/revogar` | R D | - | - | - | - | S |
| NFS-e | `/fiscal/notas`, `/fiscal/notas/{nota}` | R A X | - | R A X | R | - | S |
| Reprocesso fiscal | `/fiscal/notas/{nota}/reprocessar` | A | - | A | - | - | S |
| Titulos a receber | `/financeiro/titulos`, `/financeiro/titulos/{titulo}` | R X | - | R C U X | R | - | S |
| Baixa manual | `/financeiro/titulos/{titulo}/baixa` | R A | - | R A | - | - | S |
| Conciliacao manual | `/financeiro/conciliacao` | R A X | - | R A X | R | - | S |
| Exportacao contabil | `/financeiro/exportacoes` | R X | - | R X | R | - | S |
| Portal do cliente | `/portal`, `/portal/*` | - | - | - | - | R X proprio | - |
| Documentos GED | `/documentos`, `/documentos/{documento}` | R C U X | R limitado | R C U X | R | R limitado por link | S |
| Upload GED | `/documentos/novo` | C | C limitado | C | - | - | S |
| Relatorios | `/relatorios` | R X | R limitado | R X | R | - | S |
| Notificacoes in-app | `/notificacoes` | R U proprio | R U proprio | R U proprio | R proprio | - | S |
| Fornecedores P1 | `/fornecedores/*` | R C U X | R limitado | R C U X | R | - | S |
| Compras P1 | `/compras/*` | R A X | R C limitado | R C U X | R | - | S |
| Habilitacoes P1 | `/habilitacoes/*` | R C U A X | R proprio | R | R | - | S |
| Treinamentos P1 | `/treinamentos/*` | R C U X | R proprio | R C U | R | - | S |
| Admin interno | `/admin/*` | - | - | - | - | - | S |

---

## 4. Escopos Limitados

| Escopo | Definicao |
|---|---|
| `proprio` | usuario so acessa registros atribuidos a ele ou criados por ele |
| `R limitado` | leitura de campos operacionais, sem dados financeiros ou dados pessoais sensiveis sem necessidade funcional |
| `R X proprio` | cliente final so le e baixa documentos do cliente/contato vinculado |
| `S` | suporte interno so ve metadados operacionais e precisa de justificativa auditada para qualquer acao |

Exemplos:
- Juliana em `/fila-tecnica` ve as OS atribuidas a ela e, quando permitido, OS disponiveis para assumir.
- Rafael em `/portal/certificados` ve certificados do proprio CNPJ/CPF vinculado.
- Suporte Kalibrium em `/admin/tenants` ve status do tenant, plano e saude operacional, nao leituras tecnicas de calibracao por padrao.

---

## 5. Acoes Criticas

| Acao | Role autorizada | Exigencias adicionais |
|---|---|---|
| Alterar papel de usuario | `gerente` | 2FA ativo, audit log, nao remover ultimo gerente |
| Suspender ou reativar tenant | `suporte-kalibrium` ou automacao de billing | incidente/log interno |
| Alocar tecnico sem habilitacao vigente | `gerente` | justificativa obrigatoria e audit log |
| Aprovar certificado | `gerente` | dual sign-off, trilha tecnica completa |
| Revogar certificado | `gerente` | confirmacao destrutiva, justificativa, incidente |
| Reprocessar NFS-e rejeitada | `gerente`, `administrativo` | preservar historico da rejeicao |
| Baixar titulo financeiro | `gerente`, `administrativo` | valor, forma, data e usuario |
| Baixar pacote de auditoria | `gerente`, `visualizador` autorizado | log de acesso a documentos |
| Compartilhar link externo | `gerente`, `administrativo` | expiracao e escopo do documento |

---

## 6. Regras para Implementacao

- Policies Laravel sao a fonte de autorizacao server-side; menu e botao apenas refletem o resultado da policy.
- Rotas Livewire precisam validar permissao no mount e em cada action que muda estado.
- Jobs e listeners disparados por eventos de dominio tambem precisam carregar contexto de tenant.
- Qualquer exportacao precisa aplicar os mesmos filtros de tenant, empresa e role da tela.
- Logs de auditoria usam usuario, role ativa, empresa ativa, IP, request id, entidade e diff quando houver alteracao.
- Testes de E02 precisam provar que `tecnico` nao acessa `/settings/users` e que `cliente-final` nao acessa back-office.
