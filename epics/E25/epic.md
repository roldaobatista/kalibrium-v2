# E25 — Reforma Tributária 2026 (IBS/CBS/cIndOp)

## Objetivo

Implementar o suporte completo à Reforma Tributária Brasileira 2026 (Emenda Constitucional 132/2023 + regulamentação RFB): cálculo correto de IBS (Imposto sobre Bens e Serviços), CBS (Contribuição sobre Bens e Serviços) e cIndOp (Contribuição de Intervenção no Domínio Econômico), transmissão nos campos corretos da NFS-e nacional e exibição transparente ao cliente — com prazo fixo antes de 2026-01-01.

## Valor entregue

Laboratório em produção em 2026 não recebe auto de infração por emitir NFS-e sem os novos tributos IBS/CBS/cIndOp. O Kalibrium calcula automaticamente os novos tributos conforme o regime e código de serviço do laboratório, preenche os campos da NFS-e nacional e exibe memória de cálculo clara ao cliente. Sem este épico, o produto não pode ser usado por nenhum cliente em produção a partir de 2026-01-01.

**Prazo fixo:** sistema deve estar operacional com IBS/CBS/cIndOp antes de **2026-01-01**. Este é o épico com maior urgência de tempo de todo o roadmap.

## Escopo

### Configuração tributária RTC 2026 (REQ-FIS-009)
- Configuração por tenant: código de serviço NBS (Nomenclatura Brasileira de Serviços), CNAE principal, alíquotas de IBS e CBS (federais + estaduais + municipais) conforme tabelas RFB
- Alíquotas atualizadas conforme portarias RFB publicadas no DOU (processo manual com aviso de atualização pendente)
- Entidade: `ConfiguracaoTributariaRTC`

### Cálculo de IBS e CBS (REQ-FIS-009)
- IBS = soma das alíquotas federal + estadual + municipal aplicada sobre o valor do serviço
- CBS = alíquota federal previdenciária aplicada sobre o valor do serviço
- Cálculo sobre preço efetivo (base de cálculo = preço do serviço - deduções legais, quando aplicável)
- Half-even rounding em todos os campos fiscais
- Memória de cálculo: exibida na tela de emissão de NFS-e e no rodapé do PDF da nota

### cIndOp (REQ-FIS-009)
<!-- TBD: refinar com PM antes de /start-story — regulamentação da cIndOp para serviços de calibração/metrologia pode não estar publicada até a implementação. Monitorar Diário Oficial. Se não publicada, implementar campo como opcional com aviso "aguardando regulamentação". -->
- cIndOp aplicável a serviços de metrologia e calibração conforme regulamentação específica (a confirmar no DOU)
- Campo reservado na NFS-e nacional; preenchido quando regulamentação for publicada

### Transmissão NFS-e nacional com campos RTC (REQ-FIS-009)
- Campos obrigatórios novos na NFS-e nacional (Padrão ABRASF 3.0+): `valorIBS`, `valorCBS`, `valorCIndOp`, `aliquotaIBS`, `aliquotaCBS`, `codigoNBS`
- Validação local dos campos antes de transmitir (evitar rejeição por campo faltante)
- Compatibilidade retroativa: NFS-e emitidas antes de 2026-01-01 não são alteradas
- Histórico de versão do schema NFS-e: qual versão ABRASF foi usada em cada emissão

### Exibição e transparência ao cliente
- PDF da NFS-e e tela de visualização exibem IBS, CBS e cIndOp separados do valor base
- Descrição dos tributos em linguagem acessível (tooltip/legenda)
- Relatório fiscal por período: breakdown de IBS, CBS, cIndOp, ISS, IR, INSS para DAS/DCTF Web

## Fora de escopo
- NF-e de mercadoria (Kalibrium é prestador de serviço)
- Escrituração fiscal digital completa (SPED EFD-ICMS/IPI) — não se aplica a prestadores de serviço
- GNRE (recolhimento inter-estadual de IBS) — complexidade regulatória; será tratado em atualização pós-regulamentação completa
- Contabilidade integrada (DRE, balanço patrimonial) — fora do escopo do Kalibrium
- Parcelamento e compensação de tributos — pós-MVP

## Acceptance Criteria do épico

- **AC-E25-01:** NFS-e emitida após 2026-01-01 contém campos `valorIBS`, `valorCBS` e `codigoNBS` preenchidos corretamente conforme configuração do tenant.
- **AC-E25-02:** Memória de cálculo de IBS e CBS exibida na tela de emissão e no PDF da nota: alíquotas usadas, base de cálculo, valores calculados com half-even rounding.
- **AC-E25-03:** NFS-e emitida antes de 2026-01-01 (historico) não é alterada retroativamente pela implementação do RTC.
- **AC-E25-04:** Relatório fiscal por período exibe breakdown separado de IBS, CBS, cIndOp, ISS, IR, INSS.
- **AC-E25-05:** Validação local bloqueia transmissão de NFS-e com campos RTC faltantes antes de chamar webservice da prefeitura.
- **AC-E25-06:** Isolamento multi-tenant: configuração tributária de tenant A não impacta cálculo de tenant B.

## Dependências

### Diretas (bloqueiam início)
- E07 merged (NFS-e base — RTC 2026 é extensão do ciclo existente de NFS-e)
- ADR-0016 aceita (ConfiguracaoTributariaRTC tenant-scoped)

### Transitivas
- E21 merged (retransmissão de NFS-e rejeitada — nova rejeição possível por campos RTC faltantes)

## ADRs relacionadas
- ADR-0016 — Isolamento multi-tenant
- EC 132/2023 (Reforma Tributária Brasileira)
- Regulamentação RFB IBS/CBS (Lei Complementar 214/2025 + portarias)
- Padrão ABRASF NFS-e Nacional v3.0+

## Definition of Done

**Prazo crítico: operacional antes de 2026-01-01.**

- Cálculo de IBS + CBS validado contra tabela oficial RFB para os 10 municípios mais frequentes do setor
- NFS-e com campos RTC transmitida com sucesso em ambiente de homologação da prefeitura-piloto
- PDF da nota exibindo novos tributos aprovado pelo PM (R12)
- Relatório fiscal com breakdown RTC funcional
- Teste de não-regressão: NFS-e pré-2026 não afetada
- Testes: unit (Pest) + integração (mock ABRASF 3.0) — verdes no CI
- `docs/fiscal/rtc-2026-configuracao.md` com guia de configuração de alíquotas para PM/admin

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E25-S01 | Configuração tributária RTC 2026 por tenant (NBS, CNAE, alíquotas IBS/CBS) | média |
| E25-S02 | Cálculo de IBS e CBS com half-even rounding + memória de cálculo | alta |
| E25-S03 | Campos RTC na NFS-e nacional (ABRASF 3.0+) + validação local pré-transmissão | alta |
| E25-S04 | Exibição no PDF + tela de emissão + relatório fiscal breakdown RTC | média |
| E25-S05 | cIndOp: campo reservado + implementação condicionada à regulamentação | baixa |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Regulamentação RFB do IBS/CBS incompleta até dezembro 2025 | crítico | Iniciar E25-S01 e E25-S02 com base na LC 214/2025 já publicada; manter ponto de atualização para portarias finais |
| cIndOp para calibração/metrologia não regulamentada a tempo | alto | E25-S05 implementa campo como opcional com aviso; não bloqueia emissão de NFS-e |
| Esquema ABRASF 3.0 com campos RTC pode variar por prefeitura | alto | Testar em pelo menos 3 prefeituras-piloto antes de go-live; manter abstração de campos por versão de schema |
| Prazo fixo (2026-01-01) não é negociável — RFB não aceita prorrogação | crítico | E25 é o épico de maior prioridade temporal; deve entrar em execução antes de E24 se necessário; monitorar DOU semanalmente |

## Estimativa
- Stories: 5
- Complexidade relativa: alta (fiscal + prazo fixo + regulamentação em evolução)
- Duração estimada: 2-3 semanas (deve iniciar com antecedência suficiente para testes em staging antes de 2025-11-01)

## Referências
- PRD-ampliacao-2026-04-16-v3.md §1.3 (Pacote C — Fiscal RTC 2026, REQ-FIS-009)
- EC 132/2023 — Reforma Tributária Constitucional
- Lei Complementar 214/2025 — IBS e CBS
- Padrão ABRASF NFS-e Nacional v3.0+
- docs/product/journeys.md Jornada 1 (emissão de NFS-e ampliada com RTC 2026)
