# E03 — Cadastro Core

## Objetivo
Implementar os cadastros fundamentais que alimentam todos os fluxos operacionais: clientes (com contatos e consentimentos LGPD), instrumentos dos clientes e padrões de referência do laboratório. Estes são os dados de entrada de toda jornada de calibração.

## Valor entregue
Atendente consegue cadastrar um cliente com seus contatos, registrar o instrumento que chegou para calibrar e verificar que o padrão de referência do laboratório está vigente e rastreável. Pré-condição para criar qualquer ordem de serviço.

## Escopo

### Clientes (REQ-TEN-003, REQ-TEN-004)
- CRUD de cliente: CNPJ/CPF, razão social, endereço, regime tributário, limite de crédito
- Validação de CNPJ (consulta Receita Federal ou validação algorítmica offline)
- CRUD de contato vinculado ao cliente: nome, e-mail, WhatsApp, papel (comprador, responsável técnico)
- Consentimento LGPD por canal (e-mail marketing, WhatsApp) registrado por contato
- Histórico de alterações em dados do cliente (audit log via `owen-it/laravel-auditing`)
- Entidades: Cliente, Contato

### Instrumentos do cliente (REQ-MET-002)
- CRUD de instrumento: modelo, número de série, faixa de medição, resolução, domínio metrológico
- Domínios metrológicos do MVP: dimensional, pressão, massa, temperatura
- Vinculação ao cliente proprietário
- Histórico de calibrações passadas do instrumento (REQ-MET-007) — leitura; escrita em E05
- Entidades: Instrumento (do cliente)

### Padrões de referência do laboratório (REQ-MET-001)
- CRUD de padrão: modelo, número de série, certificado vigente, data de validade, rastreabilidade (padrão anterior na cadeia)
- Alertas de vencimento de padrão (evento `PadrãoReferência.vencido`: bloqueia uso em calibrações)
- Cadeia de rastreabilidade: padrão → padrão anterior → ... até referência primária RBC
- Entidades: Padrão de referência

### Procedimentos de calibração (base para E05)
- CRUD de procedimento: nome, versão, domínio metrológico, validade
- Versionamento de procedimento (apenas CRUD nesta fase; orçamento de incerteza em E05)
- Entidades: Procedimento de calibração

## Fora de escopo
- Execução de calibração (E05)
- Cálculo de incerteza (E05)
- Orçamento de incerteza (E05)
- GED de documentos dos cadastros (E10 — mas o hook de vinculação ao GED é preparado aqui)
- Importação em massa de clientes/instrumentos (pós-MVP)

## Critérios de entrada
- E02 completo (auth, RBAC, multi-tenancy)

## Critérios de saída
- Cliente cadastrado com ao menos um contato e consentimentos LGPD registrados
- Instrumento cadastrado e vinculado ao cliente, com domínio metrológico selecionável
- Padrão de referência cadastrado com cadeia de rastreabilidade de 2 níveis
- Alerta de vencimento de padrão disparado quando `data_validade < hoje + 30 dias`
- Padrão vencido bloqueado para uso em novas OS (verificado por teste)
- Audit log registrando alterações em dados de cliente

## Stories previstas
- E03-S01 — CRUD de cliente com validação CNPJ
- E03-S02 — CRUD de contato + consentimentos LGPD
- E03-S03 — CRUD de instrumento do cliente (4 domínios metrológicos)
- E03-S04 — CRUD de padrão de referência + cadeia de rastreabilidade
- E03-S05 — Alertas de vencimento de padrão + bloqueio de uso
- E03-S06 — CRUD de procedimento de calibração (versionado)
- E03-S07 — Audit log de cadastros (owen-it/laravel-auditing)

## Dependências
- E02 (auth, RBAC, multi-tenancy)

## Riscos
- Validação de CNPJ via Receita Federal pode ser lenta ou indisponível — fallback para validação algorítmica local, risco baixo
- Cadeia de rastreabilidade de padrões pode ter profundidade variável — modelo recursivo simples é suficiente para MVP

## Complexidade estimada
- Stories: 7
- Complexidade relativa: média
- Duração estimada: 1-2 semanas
