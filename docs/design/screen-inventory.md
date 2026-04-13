# Screen Inventory — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** B.5 / G.5
> **Dependencias:** `docs/product/sitemap.md`, `docs/design/component-patterns.md`, `docs/design/layout-master.md`, `docs/product/domain-model.md`

---

## 1. Objetivo

Tabela mestre das telas do Kalibrium V2. Conecta sitemap, wireframes, componentes, dados de dominio e epicos.

Status de wireframe:
- `pending`: ainda nao existe wireframe por epico;
- `draft`: wireframe criado, aguardando revisao;
- `approved`: wireframe aprovado para implementacao;
- `n/a`: tela tecnica ou redirecionamento sem wireframe proprio.

---

## 2. Inventario

| ID | Epico | Tela | URL pattern | Persona primaria | Tipo | Wireframe | Componentes principais | Dados consumidos |
|---|---|---|---|---|---|---|---|---|
| SCR-E02-001 | E02 | Entrada do produto | `/` | Marcelo | redirect | pending | Layout publico | Sessao, Usuario |
| SCR-E02-002 | E02 | Login | `/auth/login` | Marcelo, Juliana | form | pending | Form, Button, Alert Banner | Usuario, Sessao |
| SCR-E02-003 | E02 | Recuperar senha | `/auth/forgot-password` | Marcelo, Juliana | form | pending | Form, Button, Toast | Usuario |
| SCR-E02-004 | E02 | Redefinir senha | `/auth/reset-password/{token}` | Marcelo, Juliana | form | pending | Form, Password Input, Alert Banner | Usuario, Token |
| SCR-E02-005 | E02 | Desafio 2FA | `/auth/two-factor-challenge` | Marcelo | form | pending | Form, Number Input, Alert Banner | Usuario, Segundo fator |
| SCR-E02-006 | E02 | Configuracoes do tenant | `/settings/tenant` | Marcelo | form | pending | Section Header, Form, Tabs | Tenant, Empresa, Filial |
| SCR-E02-007 | E02 | Usuarios e papeis | `/settings/users` | Marcelo | list | pending | Table, Badge, Modal, Dropdown | Usuario, Papel, Empresa |
| SCR-E02-008 | E02 | Planos e limites | `/settings/plans` | Marcelo | dashboard | pending | Stat, Card, Progress Bar, Badge | Assinatura, Entitlement |
| SCR-E02-009 | E02 | Base legal e consentimentos | `/settings/privacy` | Marcelo | form | pending | Form, Tabs, Alert Banner | Categoria LGPD, Consentimento |
| SCR-E12-001 | E12 | Configuracao de notificacoes | `/settings/notificacoes` | Marcelo | settings | pending | Tabs, Form, Toggle Switch | Template, Canal, Preferencia |
| SCR-E12-002 | E12 | Log de comunicacao | `/settings/comunicacao/log` | Marcelo | list | pending | Table, Badge, Date Range | Log de comunicacao |
| SCR-E12-003 | E12 | Templates de e-mail | `/settings/comunicacao/templates` | Marcelo | settings | pending | Form, Tabs, Preview Panel | Template de e-mail |
| SCR-E03-001 | E03 | Clientes | `/clientes` | Marcelo | list | pending | Table, Search, Filter Chips, Pagination | Cliente, Contato |
| SCR-E03-002 | E03 | Novo cliente | `/clientes/novo` | Marcelo | form | pending | Form, Input Mask, Section Header | Cliente, Contato, Consentimento |
| SCR-E03-003 | E03 | Detalhe do cliente | `/clientes/{cliente}` | Marcelo | detail | pending | Tabs, Table, Stat, Timeline | Cliente, Contato, Instrumento, OS |
| SCR-E03-004 | E03 | Editar cliente | `/clientes/{cliente}/editar` | Marcelo | form | pending | Form, Alert Banner, Button | Cliente, Contato |
| SCR-E03-005 | E03 | Instrumentos | `/instrumentos` | Marcelo | list | pending | Table, Search, Badge, Pagination | Instrumento, Cliente |
| SCR-E03-006 | E03 | Novo instrumento | `/instrumentos/novo` | Marcelo | form | pending | Form, Select, Number Input | Instrumento, Cliente |
| SCR-E03-007 | E03 | Detalhe do instrumento | `/instrumentos/{instrumento}` | Marcelo | detail | pending | Tabs, Timeline, Table | Instrumento, Calibracao, Certificado |
| SCR-E03-008 | E03 | Padroes de referencia | `/padroes` | Juliana | list | pending | Table, Badge, Alert Banner | Padrao de referencia |
| SCR-E03-009 | E03 | Novo padrao | `/padroes/novo` | Marcelo | form | pending | Form, File Upload, Date Picker | Padrao de referencia, Documento GED |
| SCR-E03-010 | E03 | Detalhe do padrao | `/padroes/{padrao}` | Juliana | detail | pending | Card, Timeline, Badge | Padrao de referencia, Documento GED |
| SCR-E03-011 | E03 | Procedimentos | `/procedimentos` | Juliana | list | pending | Table, Badge, Search | Procedimento |
| SCR-E03-012 | E03 | Detalhe do procedimento | `/procedimentos/{procedimento}` | Juliana | detail | pending | Tabs, Table, Badge | Procedimento, Documento GED |
| SCR-E04-001 | E04 | Ordens de servico | `/ordens-servico` | Marcelo | list | pending | Table, Status Badge, Date Range, Bulk Actions | OS, Cliente, Instrumento, Tecnico |
| SCR-E04-002 | E04 | Nova OS | `/ordens-servico/nova` | Marcelo | form | pending | Form, Step Indicator, Combobox | OS, Cliente, Instrumento, Procedimento |
| SCR-E04-003 | E04 | Detalhe da OS | `/ordens-servico/{os}` | Marcelo, Juliana | detail | pending | Timeline, Tabs, Status Badge, Alert Banner | OS, Checklist, Agendamento, Evidencia |
| SCR-E04-004 | E04 | Editar OS | `/ordens-servico/{os}/editar` | Marcelo | form | pending | Form, Alert Banner, Button | OS |
| SCR-E04-005 | E04 | Agenda | `/agenda` | Marcelo | calendar | pending | Table/List, Date Picker, Badge | Agendamento, OS, Tecnico |
| SCR-E04-006 | E04 | Fila tecnica | `/fila-tecnica` | Juliana | list | pending | Card List, Badge, Empty State | OS, Agendamento |
| SCR-E04-007 | E04 | Checklist da OS | `/ordens-servico/{os}/checklist` | Juliana | form | pending | Checkbox Group, Form, Progress Bar | Checklist, OS |
| SCR-E05-001 | E05 | Bancada tecnica | `/bancada` | Juliana | workbench | pending | Card List, Number Input, Large Button | OS, Calibracao, Instrumento |
| SCR-E05-002 | E05 | Execucao de calibracao | `/calibracoes/{calibracao}` | Juliana | technical form | pending | Number Input, Table, Autosave Status, Alert Banner | Calibracao, Padrao, Procedimento |
| SCR-E05-003 | E05 | Nova calibracao da OS | `/ordens-servico/{os}/calibracao` | Juliana | form | pending | Form, Select, Step Indicator | OS, Calibracao |
| SCR-E05-004 | E05 | Orcamento de incerteza | `/procedimentos/{procedimento}/incerteza` | Juliana | table form | pending | Table, Number Input, Badge | Procedimento, Orcamento de incerteza |
| SCR-E05-005 | E05 | Historico tecnico do instrumento | `/instrumentos/{instrumento}/calibracoes` | Marcelo, Juliana | timeline | pending | Timeline, Table, Stat | Instrumento, Calibracao |
| SCR-E05-006 | E05 | Lacres e selos da OS | `/ordens-servico/{os}/lacres` | Juliana | form | pending | Form, File Upload, Badge | Lacre, Selo, OS |
| SCR-E06-001 | E06 | Certificados | `/certificados` | Marcelo | list | pending | Table, Badge, Date Range, Search | Certificado, Cliente, Instrumento |
| SCR-E06-002 | E06 | Revisao de certificado | `/certificados/{certificado}/revisao` | Marcelo | approval | pending | Split View, Alert Banner, Modal | Certificado, Calibracao, OS |
| SCR-E06-003 | E06 | Detalhe do certificado | `/certificados/{certificado}` | Marcelo | detail | pending | Tabs, Badge, Timeline, QR Preview | Certificado, Hash, Audit Log |
| SCR-E06-004 | E06 | Preview PDF | `/certificados/{certificado}/preview` | Marcelo | preview | pending | PDF Viewer, Toolbar, Alert Banner | Certificado |
| SCR-E06-005 | E06 | Revogar certificado | `/certificados/{certificado}/revogar` | Marcelo | destructive form | pending | Modal, Textarea, Danger Button | Certificado, Incidente |
| SCR-E07-001 | E07 | NFS-e | `/fiscal/notas` | Marcelo | list | pending | Table, Badge, Alert Banner | NFS-e, Cliente, OS |
| SCR-E07-002 | E07 | Detalhe da NFS-e | `/fiscal/notas/{nota}` | Marcelo | detail | pending | Tabs, Code Block, Timeline | NFS-e, XML, Protocolo |
| SCR-E07-003 | E07 | Reprocessar nota rejeitada | `/fiscal/notas/{nota}/reprocessar` | Marcelo | action form | pending | Modal, Alert Banner, Button | NFS-e, Erro fiscal |
| SCR-E08-001 | E08 | Titulos a receber | `/financeiro/titulos` | Marcelo | list | pending | Table, Stat, Date Range, Export Button | Titulo financeiro, Cliente |
| SCR-E08-002 | E08 | Detalhe do titulo | `/financeiro/titulos/{titulo}` | Marcelo | detail | pending | Timeline, Badge, Table | Titulo financeiro, Baixa |
| SCR-E08-003 | E08 | Baixa manual | `/financeiro/titulos/{titulo}/baixa` | Marcelo | form | pending | Form, Money Input, Date Picker | Titulo financeiro, Pagamento |
| SCR-E08-004 | E08 | Conciliacao manual | `/financeiro/conciliacao` | Marcelo | list | pending | Table, Filter Chips, Bulk Actions | Titulo financeiro |
| SCR-E08-005 | E08 | Exportacao contabil | `/financeiro/exportacoes` | Marcelo | report | pending | Date Range, Export Button, Table | Titulo financeiro, NFS-e, OS |
| SCR-E09-001 | E09 | Login do portal | `/portal/login` | Rafael | form | pending | Public Form, Button, Alert Banner | Usuario externo |
| SCR-E09-002 | E09 | Home do portal | `/portal` | Rafael | dashboard | pending | Card, Stat, Alert Banner | Certificado, Instrumento |
| SCR-E09-003 | E09 | Certificados do cliente | `/portal/certificados` | Rafael | list | pending | Card List, Search, Pagination | Certificado |
| SCR-E09-004 | E09 | Detalhe do certificado no portal | `/portal/certificados/{certificado}` | Rafael | detail | pending | PDF Viewer, Download Button, Badge | Certificado |
| SCR-E09-005 | E09 | Instrumentos do cliente | `/portal/instrumentos` | Rafael | list | pending | Card List, Badge, Search | Instrumento |
| SCR-E09-006 | E09 | Detalhe do instrumento no portal | `/portal/instrumentos/{instrumento}` | Rafael | detail | pending | Timeline, Badge, Download Button | Instrumento, Calibracao |
| SCR-E09-007 | E09 | Perfil do portal | `/portal/perfil` | Rafael | form | pending | Form, Toggle Switch | Usuario externo, Consentimento |
| SCR-E10-001 | E10 | Documentos GED | `/documentos` | Marcelo | list | pending | Table, File Upload, Search | Documento GED |
| SCR-E10-002 | E10 | Detalhe do documento | `/documentos/{documento}` | Marcelo | detail | pending | Metadata Card, Timeline, Download Button | Documento GED, Audit Log |
| SCR-E10-003 | E10 | Upload de documento | `/documentos/novo` | Marcelo | form | pending | File Upload, Form, Select | Documento GED |
| SCR-E11-000 | E11 | Dashboard inicial | `/app` | Marcelo | dashboard redirect | pending | Stat, Alert Banner, Shortcut Cards | OS, Calibracao, Titulo financeiro |
| SCR-E11-001 | E11 | Dashboard operacional | `/app/dashboard` | Marcelo | dashboard | pending | Stat, Table, Chart, Alert Banner | OS, Calibracao, Titulo financeiro |
| SCR-E11-002 | E11 | Relatorios | `/relatorios` | Marcelo | report | pending | Date Range, Table, Export Button | OS, Calibracao, Financeiro |
| SCR-E12-004 | E12 | Notificacoes | `/notificacoes` | Marcelo, Juliana | inbox | pending | List, Badge, Filter Chips, Empty State | Notificacao |
| SCR-E13-001 | E13 | Fornecedores | `/fornecedores` | Marcelo | list | pending | Table, Badge, Search | Fornecedor |
| SCR-E13-002 | E13 | Requisicoes de compra | `/compras/requisicoes` | Marcelo | list | pending | Table, Badge, Approval Modal | Requisicao de compra |
| SCR-E13-003 | E13 | Pedidos de compra | `/compras/pedidos` | Marcelo | list | pending | Table, Badge, Detail Drawer | Pedido de compra |
| SCR-E13-004 | E13 | Detalhe do fornecedor | `/fornecedores/{fornecedor}` | Marcelo | detail | pending | Tabs, Timeline, Badge | Fornecedor |
| SCR-E13-005 | E13 | Cotacoes | `/compras/cotacoes` | Marcelo | list | pending | Table, Badge, Approval Modal | Cotacao |
| SCR-E14-001 | E14 | Habilitacoes tecnicas | `/habilitacoes` | Marcelo | list | pending | Table, Badge, Alert Banner | Habilitacao tecnica |
| SCR-E14-002 | E14 | Matriz de competencias | `/habilitacoes/matriz` | Marcelo | matrix | pending | Table, Badge, Export Button | Colaborador, Habilitacao |
| SCR-E14-003 | E14 | Treinamentos | `/treinamentos` | Marcelo | list | pending | Table, File Upload, Badge | Treinamento |
| SCR-ADM-001 | E02 | Tenants | `/admin/tenants` | Suporte Kalibrium | list | pending | Table, Badge, Search | Tenant, Assinatura |
| SCR-ADM-002 | E02 | Auditoria de suporte | `/admin/support-audit` | Suporte Kalibrium | audit list | pending | Table, Date Range, Badge | Support Audit Log |

---

## 3. Regras de Manutencao

- Toda nova tela precisa de ID `SCR-E##-NNN` antes de wireframe.
- A URL precisa existir em `docs/product/sitemap.md`.
- A role minima precisa aparecer em `docs/product/rbac-screen-matrix.md`.
- O status `approved` so pode ser usado depois de revisao PM do wireframe correspondente.
- Se uma tela consumir entidade nao listada em `docs/product/domain-model.md`, o dominio precisa ser revisado antes da implementacao.
