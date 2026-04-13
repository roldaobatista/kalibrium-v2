# E06 — Certificado de Calibração

## Objetivo
Implementar a geração do certificado de calibração em PDF no formato compatível com RBC, com numeração controlada, QR code de autenticidade, fluxo de dual sign-off (aprovação pelo gerente) e entrega ao cliente. É o produto final da operação de calibração — o documento que o cliente paga para receber.

## Valor entregue
Gerente revisa a calibração concluída pelo técnico, aprova, e o sistema gera automaticamente o certificado em PDF com cabeçalho do laboratório, tabela de medidas, incerteza declarada, validade e QR code de autenticidade. Cliente recebe notificação por e-mail com link de download. Laboratório tem numeração serial sem lacunas.

## Escopo

### Geração de certificado (REQ-MET-006)
- Geração de PDF a partir dos dados da calibração aprovada
- Formato compatível com RBC para laboratório não-acreditado (primeiro cliente-âncora: Tipo 2)
- Campos obrigatórios do certificado: número serial, data, laboratório emissor, cliente, instrumento (modelo, série, faixa, resolução), padrões usados (com número de certificado e validade de cada um), pontos medidos, incerteza expandida U, k e nível de confiança, condições ambientais, observações, assinatura do responsável técnico
- QR code de autenticidade vinculado ao certificado (URL pública de verificação)
- Numeração serial controlada: sem duplicidade, sem lacuna (sequência por tenant/empresa)
- Versões de certificado: rascunho → emitido (imutável após emissão)
- Entidades: Certificado de calibração

### Fluxo de aprovação (dual sign-off)
- Estado: `rascunho → revisão técnica → dual sign-off → emitido → entregue ao cliente`
- Técnico submete calibração → gerente recebe alerta de aprovação pendente
- Gerente revisa: aprova ou devolve com comentário (gera nova versão do rascunho)
- Após aprovação do gerente: certificado se torna imutável e é numerado
- Registro de quem aprovou e quando (audit trail)

### Motor de templates de certificado
- Template configurável por tipo de laboratório (Tipo 1 acreditado RBC × Tipo 2 não-acreditado)
- Para Tipo 2 (primeiro cliente-âncora): cabeçalho sem logotipos RBC/ILAC, texto de rastreabilidade adequado
- Para Tipo 1: cabeçalho com selos Cgcre/ILAC-MRA (pós-MVP se necessário, mas modelo de template preparado)
- Motor de geração: `barryvdh/laravel-dompdf` ou `tecnickcom/tcpdf` (decisão fina em slice)

### Entrega e histórico
- Evento `Certificado.emitido` dispara: envio ao portal do cliente (E09), notificação por e-mail (E12), atualização do histórico do instrumento (E05)
- URL de download segura com token de acesso (sem necessidade de login para o cliente externo)
- Histórico de certificados emitidos por instrumento e por cliente
- Log de download (quem baixou, quando — REQ-CMP-002)

### Compliance (REQ-CMP-001, REQ-CMP-003)
- Certificado emitido é append-only: nunca pode ser alterado ou excluído
- Cancelamento de certificado: gera novo certificado de cancelamento (documento referenciando o original)
- Retenção de 10 anos garantida (REQ-CMP-003): armazenamento em storage duradouro (MinIO/S3)

## Fora de escopo
- Assinatura digital ICP-Brasil (explicitamente fora do MVP per mvp-scope.md)
- Emissão de NFS-e (E07)
- Portal público de consulta de certificado por QR code — apenas URL de download segura no MVP

## Critérios de entrada
- E05 completo (calibração com cálculo de incerteza aprovado)

## Critérios de saída
- Certificado PDF gerado com todos os campos obrigatórios RBC preenchidos
- Numeração serial: dois certificados consecutivos têm números sequenciais sem lacuna
- QR code presente no PDF apontando para URL de verificação
- Após aprovação, certificado não pode ser alterado (verificado por teste de imutabilidade)
- Evento `Certificado.emitido` dispara notificação por e-mail ao cliente (verificado por teste de evento)
- Log de download registrado ao acessar URL de download
- PDF gerado em até 5 segundos para calibração com até 20 pontos (verificado por teste de performance)

## Stories previstas
- E06-S01 — Entidade Certificado de calibração com migrations e model
- E06-S02 — Motor de template de certificado (Tipo 2 — não-acreditado)
- E06-S03 — Geração de PDF (dompdf/tcpdf) com todos os campos obrigatórios
- E06-S04 — Numeração serial controlada (sem lacuna, sem duplicidade)
- E06-S05 — Fluxo de dual sign-off (revisão técnica + aprovação gerente)
- E06-S06 — QR code de autenticidade + URL de verificação pública
- E06-S07 — Entrega ao cliente: evento, storage duradouro, log de download

## Dependências
- E05 (calibração concluída com orçamento de incerteza)

## Riscos
- Geração de PDF pode ser lenta para certificados com muitos pontos — job assíncrono via fila resolve
- Template de certificado pode precisar de ajuste fino após revisão do primeiro cliente real — motor de template flexível é pré-requisito
- Numeração serial em ambiente de alta concorrência pode gerar race condition — usar sequence do PostgreSQL (atômico por natureza)

## Complexidade estimada
- Stories: 7
- Complexidade relativa: alta
- Duração estimada: 1-2 semanas
