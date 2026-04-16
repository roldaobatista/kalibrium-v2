# Seeds de Desenvolvimento — E03 Cadastro Core

> **Ambiente:** desenvolvimento e testes automatizados (não rodar em produção)
> **Data:** 2026-04-15
> **Pré-requisito:** seeds de E02 aplicados (tenants A e B, tenant_users com roles)

---

## 1. Estrutura dos seeds

```
database/seeders/
├── E03Seeder.php                    ← orquestrador, chamado por DatabaseSeeder
├── ClienteSeeder.php
├── ContatoSeeder.php
├── ConsentimentoContatoSeeder.php
├── InstrumentoSeeder.php
├── PadraoReferenciaSeeder.php
└── ProcedimentoCalibracaoSeeder.php
```

Invocar via:
```bash
php artisan db:seed --class=E03Seeder
```

---

## 2. Tenants de referência (criados no E02 Seeder)

| ID | Nome | Perfil |
|---|---|---|
| 1 | Lab Precision Ltda | Laboratório de calibração PJ |
| 2 | Metrologia Express ME | Laboratório de campo PJ |

---

## 3. Clientes

**5 clientes por tenant (mix PJ/PF):**

### Tenant 1 — Lab Precision Ltda

| # | tipo_pessoa | documento | razao_social | regime_tributario | ativo |
|---|---|---|---|---|---|
| 1 | PJ | 12.345.678/0001-90 | Indústria Metálica SA | real | true |
| 2 | PJ | 98.765.432/0001-10 | Farmacêutica Brasil Ltda | presumido | true |
| 3 | PJ | 11.222.333/0001-44 | Petroquímica Norte SA | real | true |
| 4 | PF | 123.456.789-09 | Carlos Ferreira (MEI) | mei | true |
| 5 | PJ | 55.666.777/0001-88 | Alimentos Sul Ltda | simples | false |

### Tenant 2 — Metrologia Express ME

| # | tipo_pessoa | documento | razao_social | regime_tributario | ativo |
|---|---|---|---|---|---|
| 6 | PJ | 22.333.444/0001-55 | AutoPeças Rio Ltda | simples | true |
| 7 | PJ | 77.888.999/0001-22 | Construção Verde SA | presumido | true |
| 8 | PF | 987.654.321-00 | Ana Costa (autônoma) | isento | true |
| 9 | PJ | 33.444.555/0001-66 | Têxtil Centro Ltda | simples | true |
| 10 | PJ | 66.777.888/0001-33 | Eletrônicos Leste SA | real | false |

> Clientes 5 e 10 criados com `ativo = false` para testar filtros de listagem.

---

## 4. Contatos

**3 contatos por cliente (15 por tenant, 30 total):**

Padrão por cliente:
- Contato 1: papel `comprador`, `principal = true`
- Contato 2: papel `responsavel_tecnico`, `principal = false`
- Contato 3: papel `financeiro`, `principal = false`

### Exemplo para Cliente 1 (Indústria Metálica SA, tenant 1):

| nome | email | whatsapp | papel | principal | ativo |
|---|---|---|---|---|---|
| João Silva | joao.silva@metalica.com.br | 11988880001 | comprador | true | true |
| Maria Engenheira | maria.eng@metalica.com.br | 11988880002 | responsavel_tecnico | false | true |
| Pedro Financeiro | pedro.fin@metalica.com.br | 11988880003 | financeiro | false | true |

> O seeder repete este padrão para todos os clientes, ajustando nomes e contatos.
> Contato 3 do cliente 5 (inativo) criado com `ativo = false` para testar soft delete.

---

## 5. Consentimentos LGPD

**3 registros por contato (variando status e canal):**

Padrão por contato:
1. `canal = email`, `status = concedido`, `concedido_em = now() - 60 dias`
2. `canal = whatsapp`, `status = concedido`, `concedido_em = now() - 30 dias`
3. `canal = email`, `status = revogado`, `revogado_em = now() - 10 dias`, `motivo_revogacao = solicitacao_titular`

> O terceiro registro simula revogação posterior para testar consulta do consentimento mais recente por canal.

**Cenário especial (1 contato por tenant):**
- Contato sem nenhum consentimento — para testar validação de consentimento ausente.

---

## 6. Instrumentos

**10 instrumentos por tenant (2-3 por domínio metrológico), distribuídos nos clientes:**

### Tenant 1 — Lab Precision Ltda

| # | cliente | descricao | fabricante | modelo | numero_serie | dominio | faixa_min | faixa_max | unidade | ativo |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Cliente 1 | Paquímetro digital | Mitutoyo | 530-104 | MT-2024-0001 | dimensional | 0 | 150 | mm | true |
| 2 | Cliente 1 | Micrômetro externo | Mitutoyo | 103-137 | MT-2024-0002 | dimensional | 0 | 25 | mm | true |
| 3 | Cliente 2 | Manômetro glicerina | Wika | 111.10 | WK-2024-0001 | pressao | 0 | 10 | bar | true |
| 4 | Cliente 2 | Transdutor pressão | Emerson | 3051C | EM-2024-0001 | pressao | 0 | 100 | bar | true |
| 5 | Cliente 3 | Balança analítica | Mettler | ME204 | ME-2024-0001 | massa | 0 | 220 | g | true |
| 6 | Cliente 3 | Balança de bancada | Shimadzu | BX6200H | SH-2024-0001 | massa | 0 | 6200 | g | true |
| 7 | Cliente 4 | Termômetro digital | Fluke | 54-II | FL-2024-0001 | temperatura | -200 | 1372 | °C | true |
| 8 | Cliente 4 | Termopar tipo K | Omega | KQSS-14U | OM-2024-0001 | temperatura | -200 | 1260 | °C | true |
| 9 | Cliente 5 | Paquímetro analógico | Starrett | 120A | ST-2024-0001 | dimensional | 0 | 200 | mm | false |
| 10 | Cliente 3 | Manômetro seco | Wika | 312.20 | WK-2024-0002 | pressao | -1 | 15 | bar | true |

### Tenant 2 — Metrologia Express ME

Mesma distribuição de domínios, com número de série prefixo `T2-` para diferenciar:
- 3 instrumentos dimensionais (clientes 6 e 7)
- 2 instrumentos de pressão (cliente 7)
- 2 instrumentos de massa (cliente 8)
- 2 instrumentos de temperatura (cliente 9)
- 1 instrumento inativo (cliente 10, `ativo = false`)

> Séries do tenant 2 nunca colidem com tenant 1 — validação de unicidade é por `(tenant_id, numero_serie)`.

---

## 7. Padrões de referência

**5 padrões por tenant, formando cadeia de rastreabilidade de 2 níveis:**

### Tenant 1 — Lab Precision Ltda

| # | descricao | numero_serie | dominio | data_validade | vigente | padrao_anterior_id | nota |
|---|---|---|---|---|---|---|---|
| 1 | Bloco padrão grau 1 | BP-RBC-0001 | dimensional | hoje + 365 dias | true | null | topo da cadeia (RBC) |
| 2 | Paquímetro padrão | PP-LAB-0001 | dimensional | hoje + 180 dias | true | 1 | nível 2 |
| 3 | Manômetro de referência | MR-RBC-0001 | pressao | hoje - 5 dias | false | null | **vencido** — usado para testar bloqueio |
| 4 | Manômetro de trabalho | MT-LAB-0001 | pressao | hoje + 270 dias | true | 3 | nível 2 (padrão anterior vencido) |
| 5 | Balança padrão F2 | BA-RBC-0001 | massa | hoje + 400 dias | true | null | topo da cadeia |

> Padrão 3 com `data_validade < hoje` e `vigente = false` testa o Job de vencimento e o bloqueio.
> Padrão 4 com `padrao_anterior_id = 3` (vencido) testa a navegação da cadeia mesmo com padrão anterior inválido.

### Tenant 2 — Metrologia Express ME

| # | descricao | numero_serie | dominio | data_validade | vigente | padrao_anterior_id |
|---|---|---|---|---|---|---|
| 6 | Bloco padrão grau 2 | T2-BP-0001 | dimensional | hoje + 300 dias | true | null |
| 7 | Régua padrão | T2-RP-0001 | dimensional | hoje + 150 dias | true | 6 |
| 8 | Manômetro de referência T2 | T2-MR-0001 | pressao | hoje + 200 dias | true | null |
| 9 | Balança padrão T2 | T2-BA-0001 | massa | hoje + 365 dias | true | null |
| 10 | Termômetro padrão T2 | T2-TP-0001 | temperatura | hoje + 100 dias | true | null |

> Padrões do tenant 2 são todos vigentes — para validar isolamento (tenant 1 não enxerga tenant 2).

---

## 8. Procedimentos de calibração

**3 procedimentos por tenant, com versionamento:**

### Tenant 1 — Lab Precision Ltda

| # | nome | versao | dominio | status | data_vigencia_inicio |
|---|---|---|---|---|---|
| 1 | PC-DIM-001 Calibração de Paquímetros | 1.0 | dimensional | obsoleto | hoje - 365 dias |
| 2 | PC-DIM-001 Calibração de Paquímetros | 2.0 | dimensional | vigente | hoje - 30 dias |
| 3 | PC-PRE-001 Calibração de Manômetros | 1.0 | pressao | vigente | hoje - 60 dias |
| 4 | PC-MAS-001 Calibração de Balanças | 1.0 | massa | rascunho | null |
| 5 | PC-TEM-001 Calibração de Termômetros | 1.0 | temperatura | vigente | hoje - 90 dias |

> Registros 1 e 2 do mesmo procedimento com versões diferentes validam:
> - Partial unique index: apenas a versão 2.0 pode ter `status = vigente`
> - Transição de estado: versão 1.0 está `obsoleto`, versão 2.0 está `vigente`

### Tenant 2 — Metrologia Express ME

| # | nome | versao | dominio | status |
|---|---|---|---|---|
| 6 | PC-DIM-001 Calibração de Paquímetros | 1.0 | dimensional | vigente |
| 7 | PC-PRE-001 Calibração de Manômetros | 1.0 | pressao | rascunho |
| 8 | PC-MAS-001 Calibração de Balanças | 1.0 | massa | vigente |

> Tenant 2 tem procedimento dimensional vigente com mesmo nome que tenant 1 — validar que partial unique index é per-tenant.

---

## 9. Cenários de teste cobertos pelos seeds

| Cenário | Dados envolvidos |
|---|---|
| Unicidade de documento por tenant | Clientes tenant 1 e tenant 2 têm CNPJs/CPFs distintos; tentativa de inserir duplicado no mesmo tenant deve falhar |
| Soft delete | Cliente 5, Instrumento 9 com `ativo = false` e `deleted_at` preenchido |
| Filtro de ativos | Listagens devem excluir registros com `deleted_at IS NOT NULL` por padrão |
| Isolamento cross-tenant | Instrumentos T1 não aparecem em queries do T2 (RLS) |
| Cadeia de rastreabilidade 2 níveis | Padrão 2 → Padrão 1 (tenant 1); Padrão 7 → Padrão 6 (tenant 2) |
| Padrão vencido | Padrão 3: `data_validade < hoje`, `vigente = false` |
| Alerta de vencimento (30 dias) | Nenhum padrão exatamente em D-30 — adicionar caso isolado no teste unitário do Job |
| Partial unique index (procedimento vigente) | Apenas PC-DIM-001 v2.0 está vigente — tentar v1.0 vigente deve falhar |
| Versionamento de procedimento | PC-DIM-001 v1.0 obsoleto + v2.0 vigente no tenant 1 |
| Consentimento append-only | Tentativa de UPDATE em consentimentos_contato deve lançar exception |
| Consentimento mais recente por canal | 3 registros por contato — query deve retornar o mais recente por canal |
