# Registro de Operações de Tratamento (ROT) — Kalibrium

> **Status:** `draft-awaiting-dpo`. Item T2.4 da Trilha #2. Cumpre o Art. 37 da Lei 13.709/2018. Entradas iniciais, uma por finalidade listada em `lgpd-base-legal.md` (T2.2). Revisão pelo DPO obrigatória antes do primeiro tenant real.

## Estrutura

Cada entrada é uma linha do registro com os campos exigidos pelo Art. 37:

- **Finalidade**
- **Descrição do tratamento**
- **Categorias de titulares**
- **Categorias de dados**
- **Destinatários ou categorias de destinatários**
- **Transferências internacionais** (aplicável?)
- **Prazo de retenção**
- **Medidas de segurança**

## Entradas

### ROT-001 — Cadastro de cliente do laboratório

- **Descrição:** criação e manutenção do cadastro da empresa cliente do laboratório e dos contatos responsáveis pela relação comercial e técnica.
- **Categorias de titulares:** pessoa de contato do cliente (comprador técnico, responsável pela qualidade, representante legal).
- **Categorias de dados:** nome, e-mail corporativo, telefone, cargo, vínculo com CNPJ do cliente.
- **Destinatários:** usuários do tenant (laboratório), subcontratados listados em `vendor-matrix.md` para funções operacionais (e-mail transacional).
- **Transferências internacionais:** não.
- **Prazo de retenção:** 5 anos após último serviço (cross-ref `lgpd-base-legal.md` linha 1).
- **Medidas de segurança:** RLS por tenant, TLS em trânsito, audit log de alterações, hash de senha Argon2id, MFA obrigatório para gerente.

### ROT-002 — Cadastro de colaborador do laboratório

- **Descrição:** cadastro dos usuários do laboratório com seu papel (gerente, técnico, administrativo, visualizador).
- **Categorias de titulares:** colaboradores do laboratório.
- **Categorias de dados:** nome, e-mail corporativo, papel, histórico de acesso.
- **Destinatários:** apenas usuários do próprio tenant.
- **Transferências internacionais:** não.
- **Prazo de retenção:** enquanto ativo + 5 anos.
- **Medidas de segurança:** idem ROT-001.

### ROT-003 — Execução técnica da calibração

- **Descrição:** registro do processo de calibração incluindo condições ambientais, padrões utilizados, medidas e cálculo de incerteza.
- **Categorias de titulares:** colaborador (técnico) como autor do registro.
- **Categorias de dados:** identificação do técnico, timestamp, valores lançados.
- **Destinatários:** gerente (para aprovação), auditor da Cgcre (em auditoria), cliente final (no certificado emitido).
- **Transferências internacionais:** não.
- **Prazo de retenção:** 10 anos (obrigação legal RBC, RNF-009).
- **Medidas de segurança:** append-only após emissão, audit log, assinatura visual do técnico armazenada com hash.

### ROT-004 — Emissão de certificado de calibração

- **Descrição:** geração do PDF definitivo compatível com a RBC, numeração fiscal do documento, armazenamento imutável.
- **Categorias de titulares:** contato do cliente (quando aparece no cabeçalho do certificado) e colaborador (como responsável técnico).
- **Categorias de dados:** nome, empresa, identificação do instrumento, resultados.
- **Destinatários:** cliente final por e-mail, armazenamento interno, auditor da Cgcre em auditoria.
- **Transferências internacionais:** não.
- **Prazo de retenção:** 10 anos.
- **Medidas de segurança:** imutabilidade, hash armazenado separadamente, link assinado de acesso público com TTL.

### ROT-005 — Emissão fiscal (NFS-e)

- **Descrição:** emissão da nota fiscal de serviço na prefeitura do município do laboratório, vinculada ao certificado emitido.
- **Categorias de titulares:** contato do cliente como representante fiscal.
- **Categorias de dados:** CNPJ/CPF do cliente, razão social, endereço fiscal, valor, descrição do serviço.
- **Destinatários:** Prefeitura Municipal de destino, provedor de NFS-e listado em `vendor-matrix.md`.
- **Transferências internacionais:** não.
- **Prazo de retenção:** prazo legal tributário (5-10 anos conforme tributo).
- **Medidas de segurança:** comunicação exclusiva via TLS com o provedor, log de cada envio, tratamento de erro de rejeição em estado não destrutivo.

### ROT-006 — Cobrança e contas a receber

- **Descrição:** registro do valor devido pelo cliente ao laboratório e da baixa quando pago.
- **Categorias de titulares:** contato do cliente.
- **Categorias de dados:** CNPJ/CPF, valor, prazo, status de pagamento.
- **Destinatários:** usuários do tenant (laboratório).
- **Transferências internacionais:** não.
- **Prazo de retenção:** prazo legal contábil (5-10 anos).
- **Medidas de segurança:** idem ROT-001.

### ROT-007 — Portal do cliente final

- **Descrição:** acesso do cliente externo ao histórico dos próprios certificados, via login ou link assinado.
- **Categorias de titulares:** contato do cliente.
- **Categorias de dados:** e-mail, hash de senha, histórico de consulta, consentimento opcional para notificação via WhatsApp.
- **Destinatários:** apenas o próprio titular e o usuário do tenant correspondente.
- **Transferências internacionais:** não.
- **Prazo de retenção:** enquanto cliente ativo + 5 anos.
- **Medidas de segurança:** TLS, Argon2id, link assinado com TTL de até 72h, rate limit, log de cada acesso.

### ROT-008 — Suporte ao usuário

- **Descrição:** atendimento a dúvidas e problemas relatados por usuários do laboratório e por contato do cliente externo.
- **Categorias de titulares:** usuário que abre o chamado.
- **Categorias de dados:** e-mail, histórico do chamado, anexos opcionais.
- **Destinatários:** equipe de suporte do Kalibrium.
- **Transferências internacionais:** não.
- **Prazo de retenção:** 2 anos após fechamento do chamado.
- **Medidas de segurança:** acesso restrito por papel, log de acesso ao chamado.

### ROT-009 — Auditoria e rastreabilidade (logs de acesso)

- **Descrição:** registro imutável de quem acessou qual informação e quando, para defesa do laboratório em auditoria ou processo.
- **Categorias de titulares:** colaborador e contato do cliente.
- **Categorias de dados:** `user_id`, `tenant_id`, timestamp, ação, recurso acessado.
- **Destinatários:** usuários com papel de gerente e auditor da Cgcre.
- **Transferências internacionais:** não.
- **Prazo de retenção:** 10 anos.
- **Medidas de segurança:** imutabilidade, hash em cadeia opcional, acesso apenas leitura.

## Atualização do ROT

- **Trigger:** qualquer nova finalidade ou mudança material em uma existente.
- **Responsável:** DPO (quando contratado) + PM.
- **Versionamento:** append-only — nunca reescrever entrada antiga, criar versão nova.

## Pendências que dependem do DPO

1. Ajuste dos prazos de retenção por UF quando houver diferença.
2. Formalização do texto visível ao titular.
3. Reavaliação do ROT-005 após decisão da reforma tributária.
4. Publicação do ROT em forma acessível à ANPD quando exigido.
