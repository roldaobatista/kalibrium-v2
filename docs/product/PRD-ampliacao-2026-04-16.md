# PRD Kalibrium — Ampliação de 2026-04-16

> **Status:** complemento canônico do `PRD.md`. Este arquivo **não substitui** o PRD — complementa-o, adicionando capítulos e requisitos que estavam ausentes na descoberta original. Tudo no `PRD.md` continua válido; este documento cobre o que faltava. Quando o PRD for re-congelado (próxima passagem por `/freeze-prd`), este conteúdo será incorporado inline ao documento principal. Até lá, os dois devem ser lidos juntos.
>
> **Princípio operacional:** PRD é aditivo — só amplia, nunca diminui. Ver `feedback_prd_only_grows.md` na memória do agente e `docs/incidents/discovery-gap-offline-2026-04-16.md` para o histórico do que motivou esta ampliação.

---

## 0. Sumário executivo da ampliação

Descoberta de 2026-04-16 revelou que a operação do cliente-alvo do Kalibrium é **90% offline em campo**, envolvendo múltiplos papéis com conectividade intermitente (até 4 dias sem sinal). A versão original do PRD capturou apenas o recorte de **laboratório de bancada online** — está correta, mas incompleta.

Esta ampliação adiciona:

1. **Operação de campo sistêmica** (modo bancada + modo campo-veículo + modo campo-UMC).
2. **UMC (Unidade Móvel de Calibração) e frota operacional** (veículos assinados/pool).
3. **Offline-first sistêmico** (8 personas, janelas de até 4 dias offline, todos os dispositivos).
4. **Caixa de despesa por OS** (foto obrigatória, 3 origens de dinheiro, triagem + aprovação).
5. **Estoque multinível** (4 locais: laboratório + UMC + veículo + carro pessoal).
6. **CRM offline do vendedor externo** (500 clientes offline, orçamento em campo).
7. **Segurança em dispositivo móvel** (biometria, wipe remoto, criptografia local, device binding).
8. **Sincronização** (merge por campo, detecção de conflito, tela de resolução, audit log).
9. **6 épicos novos no roadmap** (E15-E20), zero épicos removidos.
10. **Stack cliente offline-first** via ADR-0015 (PWA + Capacitor + SQLite criptografado).

Total de requisitos novos: **33** (`REQ-FLD-001..006`, `REQ-UMC-001..004`, `REQ-VHL-001..003`, `REQ-DSP-001..008`, `REQ-INV-001..004`, `REQ-CRM-001..007`, `REQ-SEC-001..005`, `REQ-SYN-001..006`, ampliação de existentes). Ver `mvp-scope.md` ampliado para lista completa.

---

## 1. Posicionamento ampliado do produto

### 1.1 O que o Kalibrium sempre foi (reafirmação)

O Kalibrium é uma plataforma SaaS B2B multi-tenant para empresas de **serviços técnicos, calibração, metrologia, inspeção e operação de campo**. Substitui a combinação disfuncional de ERP genérico + CRM desacoplado + planilhas + sistemas setoriais isolados por uma única plataforma que integra comercial, operação técnica, laboratório, financeiro, fiscal, RH e qualidade em fluxos orientados a eventos.

Este posicionamento sempre esteve no PRD original (§Sumário Executivo). O que faltava era o reconhecimento explícito de que **"operação de campo"** é dimensão crítica, não acessória — e que a maior parte do trabalho operacional acontece em contextos sem conectividade confiável.

### 1.2 Cliente-alvo (ampliado)

Empresas brasileiras que executam serviços técnicos em campo **e/ou** operam laboratórios de calibração de bancada. Muitas operam os dois modelos simultaneamente. Porte pequeno a médio. Características:

- Frota própria: 1 ou várias UMC (para calibração de balança rodoviária e industrial grande) + veículos operacionais (caminhonetes, carros) assinados aos técnicos ou em pool.
- Equipe típica: 1 sócio-gerente, 3-10 técnicos (bancada + campo), 1 motorista UMC, 1-3 vendedores externos, 1-2 administrativos.
- Operação geográfica: território nacional com forte presença no interior (MG, GO, MT, MS) para calibração rodoviária.
- Conectividade: cidade = boa, área industrial/zona rural = ruim, mina/usina/obra = zero.

### 1.3 Papéis do sistema (RBAC ampliado)

Ver `personas.md` ampliado. Lista resumida dos papéis com permissões distintas:

- `gerente` (Marcelo): full admin, aprova despesa alta alçada, decide frota.
- `tecnico` (Juliana bancada, Carlos campo): executa calibração bancada e/ou campo, registra despesa.
- `motorista-umc` (Lúcio): opera UMC, diário de bordo, registra despesa UMC.
- `vendedor-externo` (Patrícia): CRM, orçamento, visita.
- `gestor-campo` (Diego): acompanha OS grandes, emite NFS-e/boleto em campo com celular online.
- `administrativo` (Cláudia): cadastro, NFS-e, triagem de despesa, conciliação.
- `visualizador` (auditor interno, contador): leitura sem edição.
- `cliente-final` (Rafael): portal do cliente final, só vê certificados dele.

---

## 2. Modos de operação (novo capítulo)

O MVP suporta três modos de serviço, e empresas-cliente podem operar em qualquer combinação:

### 2.1 Modo Bancada

Idêntico ao PRD original. Instrumento chega ao laboratório (Correios, entregue pelo cliente, coletado pela empresa). Calibração em bancada física. Técnico de bancada (Juliana). Certificado emitido. Jornadas 1-5.

### 2.2 Modo Campo — Veículo Operacional (novo)

Técnico vai ao cliente com caminhonete/carro pequeno (estoque pessoal a bordo com padrões leves). Calibração no local (balança industrial média, manutenção, aferição simples). 100% offline-capable. Técnico de campo (Carlos). Jornada 6.

### 2.3 Modo Campo — UMC (novo)

Caminhão especializado com guindaste + massas-padrão pesadas + motorista/operador de guindaste. Usado para balança rodoviária e industrial grande. OS envolve equipe multi-pessoa (técnico + motorista + opcionalmente auxiliares). 100% offline-capable. Jornada 7.

---

## 3. Offline-first sistêmico (novo capítulo)

### 3.1 Requisito fundamental

O sistema opera igual com ou sem conexão de internet. Todos os papéis que trabalham em campo (técnico, motorista UMC, vendedor externo, gestor em campo) têm um app que funciona 100% offline, aguenta até **4 dias de trabalho acumulado sem sincronizar** e sincroniza silenciosamente em background quando pegar sinal. Papéis de escritório operam online normalmente.

### 3.2 Requisitos específicos

- `REQ-SYN-001` Sync silencioso em background quando o dispositivo pega sinal.
- `REQ-SYN-002` Merge por campo — cada campo mantém o último editor (last-write-wins por campo).
- `REQ-SYN-003` Detecção de conflito real — quando dois usuários editaram o mesmo campo offline, o sistema sinaliza "conflito detectado" e apresenta os dois valores + timestamps + autores para resolução manual pelo responsável da OS.
- `REQ-SYN-004` Tempo real online — quando todos os membros de uma OS estão conectados, as edições aparecem em tempo real nos outros dispositivos.
- `REQ-SYN-005` Audit log de sync — cada sync registra o delta enviado/recebido, timestamp, dispositivo, usuário.
- `REQ-SYN-006` Modo avião forçado (botão de teste) — técnico pode simular offline antes de viajar pra testar o app.

### 3.3 Volume e dispositivos

Ver `mvp-scope.md` §2-ter.

- Vendedor: ~500 clientes no celular.
- Técnico: ~8 OS pendentes no pior caso (2/dia × 4 dias).
- Dispositivos: Android + iPhone + tablet + notebook + desktop, todos pelo mesmo app (PWA + Capacitor).

---

## 4. UMC e Frota Operacional (novo capítulo)

### 4.1 UMC (Unidade Móvel de Calibração)

Caminhão especializado com guindaste hidráulico e massas-padrão pesadas a bordo (500 kg, 1.000 kg, etc). Empresa pode ter 1 ou várias UMC. Cadastro inclui:

- `REQ-UMC-001` Placa, chassi, modelo, ano, capacidade do guindaste, motorista principal.
- `REQ-UMC-002` Massas-padrão a bordo com certificado vigente, número de série, validade.
- `REQ-UMC-003` Agenda da UMC bloqueia por OS + manutenção preventiva (por KM ou tempo).
- `REQ-UMC-004` Diário de bordo do motorista (KM, abastecimento com foto, pedágio com foto).

UMC só opera em 1 OS ativa por vez. Manutenção preventiva bloqueia agenda automaticamente.

### 4.2 Veículo operacional

Caminhonete ou carro pequeno usado pelos técnicos em visitas comuns (sem UMC). Modos de uso:

- `REQ-VHL-001` Cadastro com `modo_uso` = `assinado` (fixo a um técnico) OU `compartilhado` (pool).
- `REQ-VHL-002` Reserva de veículo do pool (quando compartilhado).
- `REQ-VHL-003` Diário de bordo do veículo operacional.

---

## 5. Caixa de Despesa por OS (novo capítulo)

### 5.1 Princípio

Toda despesa de campo é registrada com **foto obrigatória do cupom/nota** e **atrelada obrigatoriamente a uma OS específica**. Visibilidade de saldo otimista em tempo real; aprovação posterior.

### 5.2 Requisitos

- `REQ-DSP-001` Registro de despesa com foto + valor + tipo + OS obrigatória.
- `REQ-DSP-002` Três origens de dinheiro: cartão corporativo, adiantamento em dinheiro, próprio bolso (reembolso).
- `REQ-DSP-003` Saldo otimista em tempo real.
- `REQ-DSP-004` Triagem pelo escritório (aprovar/rejeitar/reclassificar).
- `REQ-DSP-005` Aprovação final em alçada (escritório até X, gerente acima).
- `REQ-DSP-006` Reembolso por PIX/transferência em lote.
- `REQ-DSP-007` Conciliação com fatura do cartão corporativo (CSV + matching linha-a-linha).
- `REQ-DSP-008` Relatório de custo real por OS.

### 5.3 Regras duras

- Despesa sem foto é rejeitada pelo app localmente (não chega ao servidor).
- Despesa órfã (sem OS) não existe.
- Conciliação mensal com fatura do cartão corporativo é obrigatória.

---

## 6. Estoque Multinível (novo capítulo)

### 6.1 Quatro locais de estoque

1. **Laboratório central** — garagem/almoxarifado principal.
2. **UMC** — estoque a bordo do caminhão (massas-padrão, ferramentas especiais).
3. **Veículo operacional** — estoque a bordo da caminhonete/carro (padrões leves, kit de campo).
4. **Carro pessoal do técnico** — mini-estoque portátil (ferramentas básicas, padrões de referência).

### 6.2 Requisitos

- `REQ-INV-001` Quatro locais de estoque distintos, cada um com saldo próprio.
- `REQ-INV-002` Movimentação entre locais com responsável + timestamp.
- `REQ-INV-003` Consulta offline do estoque local no dispositivo do usuário.
- `REQ-INV-004` Alerta de padrão vencendo (30 dias antes) por local.

### 6.3 Regra dura

Técnico em campo só pode usar padrão que está no estoque acessível ao dispositivo dele (veículo ou carro pessoal ou UMC em operação com ele). App valida localmente offline.

---

## 7. CRM Offline do Vendedor (novo capítulo)

### 7.1 Persona-alvo

Patrícia (Persona 5) — vendedora externa com carteira de ~500 clientes, 80% campo, BYOD (celular pessoal).

### 7.2 Requisitos

- `REQ-CRM-001` Carteira de clientes por vendedor (vendedor vê só a dele; gerente vê tudo).
- `REQ-CRM-002` Ficha completa do cliente disponível offline (até 500 clientes).
- `REQ-CRM-003` Registro de visita (nota de voz + transcrição, foto da fachada/crachá, timestamp, GPS opcional).
- `REQ-CRM-004` Criação de orçamento em campo offline (PDF local + envio imediato via WhatsApp/link).
- `REQ-CRM-005` Conversão de orçamento aceito em OS (sync quando conectar).
- `REQ-CRM-006` Follow-up automático (lembrete para o vendedor).
- `REQ-CRM-007` Visão de pipeline em tempo real pro gerente (conforme vendedor sincroniza).

### 7.3 Regra de carteira

Cliente pertence a um vendedor. Outro vendedor não vê nem mexe na ficha. Gerente vê tudo. Transferência de carteira é ação explícita do gerente.

---

## 8. Segurança em Dispositivo Móvel (novo capítulo)

### 8.1 Requisitos

- `REQ-SEC-001` Biometria obrigatória para abrir o app em smartphone/tablet (digital ou face). PIN como fallback.
- `REQ-SEC-002` Criptografia local dos dados offline (AES-256, chave derivada do login).
- `REQ-SEC-003` Wipe remoto autorizado pela empresa — próximo sync ou abertura dispara limpeza total dos dados locais.
- `REQ-SEC-004` Device binding — dispositivo registrado na primeira login. Login em dispositivo novo exige reaprovação.
- `REQ-SEC-005` Sessão longa (token válido para cobrir 4 dias offline) com refresh transparente.

### 8.2 Conformidade LGPD

Dados pessoais residem em dispositivo móvel por até 4 dias. Exige:

- Base legal registrada para cada categoria (já coberto por `REQ-CMP-004` no PRD original).
- Criptografia em repouso no dispositivo (REQ-SEC-002).
- Direito ao esquecimento: wipe remoto + exclusão server-side propagam (com prazo documentado, dado que pode haver dispositivo offline que só atualiza quando reconectar).
- Incidente de perda/roubo: dispositivo vira alvo de wipe remoto imediato.

---

## 9. Jornadas ampliadas

Ver `journeys.md` ampliado. Jornadas 1-5 permanecem; foram adicionadas:

- **Jornada 6 — Visita em campo com veículo operacional** (técnico solo, balança industrial média).
- **Jornada 7 — Visita em campo com UMC** (equipe, balança rodoviária).
- **Jornada 8 — Caixa de despesa por OS** (ciclo completo do dinheiro).
- **Jornada 9 — Vendedor externo offline** (CRM mobile).
- **Jornada 10 — Colaboração multi-pessoa offline** (mecanismo transversal).
- **Jornada 11 — Administração de UMC e frota** (gerente + admin).

---

## 10. Personas ampliadas

Ver `personas.md` ampliado. Personas 1-3 permanecem (Marcelo ampliado; Juliana e Rafael intactos). Foram adicionadas:

- **2B — Carlos (técnico de campo)** — contraparte de Juliana, mesmo papel de sistema.
- **4 — Lúcio (motorista/operador de guindaste UMC)**.
- **5 — Patrícia (vendedora externa)**.
- **6 — Diego (gestor em campo)**.
- **7 — Cláudia (atendente/administrativa do escritório)**.

Total: 8 personas primárias (era 3).

---

## 11. Stack técnica — cliente mobile e web (novo capítulo)

Ver `docs/adr/0015-stack-offline-first-mobile.md` — ADR nova que complementa ADR-0001.

### 11.1 Decisões-chave

- **Backend:** Laravel + PostgreSQL mantidos (ADR-0001 preservada). Ajuste necessário: endpoints API-only (Sanctum + JWT), suporte a batch sync.
- **Frontend/app:** React + TypeScript + Ionic + **Capacitor** (wrapper híbrido para iOS/Android).
- **Banco local:** SQLite via Capacitor + SQLCipher (criptografia AES-256). Fallback IndexedDB (Dexie) no desktop web.
- **Sync engine:** escolha entre PowerSync, ElectricSQL ou custom — decisão formal em ADR-0016 após PoC técnico no épico E16.
- **Autenticação:** JWT long-lived + refresh + device binding + biometria Capacitor.
- **Push:** Firebase Cloud Messaging via Capacitor plugin.
- **Distribuição:** PWA instalável (desktop/notebook) + App Store + Play Store (iOS/Android) com live updates para mudanças JS/CSS.

### 11.2 Impacto em E01/E02/E03 merged

- **E01:** backend setup permanece. Frontend Livewire/Blade descartado.
- **E02:** core de auth permanece. Ampliação: JWT long-lived + refresh + device binding + biometria + wipe remoto.
- **E03:** modelo de dados e regras de negócio permanecem. Frontend refeito no novo stack.

---

## 12. Roadmap ampliado

Ver `epics/ROADMAP.md` ampliado e `docs/product/roadmap.md` ampliado.

- **14 épicos originais preservados.**
- **6 épicos novos inseridos** (E15-E20):
  - E15 — PWA Shell Offline-First + Capacitor Wrapper.
  - E16 — Sync Engine + Merge por Campo + Conflito.
  - E17 — UMC e Frota Operacional.
  - E18 — Caixa de Despesa por OS.
  - E19 — Estoque Multinível.
  - E20 — CRM Offline do Vendedor.
- **E15 e E16 entram antes de E04+** (dependência técnica obrigatória).
- Stories totais estimadas do MVP: ~125 (eram ~63).

---

## 13. Critério de sucesso do MVP (ampliado)

O MVP está "no ar" quando um único cliente real (primeira empresa pagante) consegue, dentro do Kalibrium, executar:

1. Uma jornada completa **modo bancada** (Jornada 1 do PRD original).
2. Uma jornada completa **modo campo-veículo** (Jornada 6 nova).
3. Uma jornada completa **modo campo-UMC** (Jornada 7 nova).
4. Com operação de campo **100% offline-capable** (técnico trabalha 2 dias sem sinal e sincroniza quando voltar).
5. Com caixa de despesa fechada (foto + OS + triagem + aprovação + reembolso).
6. Com vendedor externo operando CRM offline e gerando orçamento em campo.

Sem usar planilha, software legado ou portal externo. Tempo total medido em dias úteis deve ser menor que a linha de base atual do mesmo cliente.

---

## 14. O que fica FORA da ampliação (por enquanto)

Ver `mvp-scope.md` §4 ampliado. Principais exclusões da ampliação:

- Sincronização peer-to-peer via Bluetooth/Wi-Fi local entre dispositivos da equipe quando todos offline. Fica pós-MVP.
- Emissão de NFS-e direto pelo técnico em campo. Fica por conta do escritório ou gestor em campo com celular online.
- Agenda consolidada cross-UMC (se cliente tiver 3+ UMC em rotação). Fica pós-MVP.
- Reserva avançada de pool de veículos com fila de espera. Fica pós-MVP.

---

## 15. Referências cruzadas

- `docs/product/PRD.md` — documento base (continua canônico).
- `docs/product/mvp-scope.md` — ampliado com os módulos novos.
- `docs/product/personas.md` — ampliado com 5 personas novas.
- `docs/product/journeys.md` — ampliado com 6 jornadas novas.
- `docs/product/domain-model.md` — ampliado com entidades de campo, UMC, despesa, estoque, sync.
- `docs/product/glossary-domain.md` — ampliado com termos novos.
- `epics/ROADMAP.md` — ampliado com E15-E20.
- `docs/product/roadmap.md` — ampliado com slices novos.
- `docs/adr/0015-stack-offline-first-mobile.md` — ADR nova.
- `docs/incidents/discovery-gap-offline-2026-04-16.md` — incidente que motivou esta ampliação.
- Memórias do agente:
  - `project_offline_first_systemic.md`
  - `feedback_prd_only_grows.md`
  - `feedback_intake_must_ask_connectivity.md`

---

## 16. Consolidação futura

Quando a execução dos épicos E15-E20 estabilizar (ou quando o PM decidir), este arquivo será **incorporado inline ao `PRD.md`** em uma próxima passagem por `/freeze-prd`. Neste momento, este documento existe para que nenhuma informação se perca enquanto o PRD principal (7700+ linhas) não é reorganizado. O backup do PRD original antes desta ampliação está em `PRD-compactado-backup-2026-04-11.md`. Nada foi deletado.
