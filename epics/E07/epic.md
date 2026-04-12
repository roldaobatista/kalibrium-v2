# E07 — Fiscal: NFS-e

## Objetivo
Implementar a emissão automática da NFS-e (Nota Fiscal de Serviços Eletrônica) para o município do laboratório no momento da aprovação do certificado. Para o primeiro cliente-âncora (Rondonópolis/MT), este épico resolve a integração com a prefeitura específica deste município. Elimina o processo manual atual de portal fiscal externo.

## Valor entregue
Assim que o gerente aprova o certificado de calibração, o sistema emite automaticamente a NFS-e no nome do laboratório para o município correto, sem nenhuma ação manual adicional. O cliente recebe o XML da nota por e-mail. O laboratório deixa de entrar em portal externo da prefeitura.

## Escopo

### Emissão de NFS-e (REQ-FIS-001)
- Emissão automática disparada pelo evento `Certificado.emitido`
- Integração com a prefeitura de Rondonópolis/MT (fora da lista original dos 5 municípios — validar API/webservice disponível)
- Estratégia: broker intermediário (`NFE.io` ou `Focus NFe`) como plano A para cobertura de múltiplos municípios, incluindo Rondonópolis; integração direta via `nfephp-org/sped-nfse` como plano B (decisão fina via ADR-0008)
- Transmissão da NFS-e com retorno de: número da nota, série, XML autorizado, chave de acesso
- Entidades: NFS-e

### Numeração fiscal (REQ-FIS-002)
- Numeração controlada pelo sistema: sem duplicidade, sem pulo
- Série fiscal configurável por empresa/filial
- Uso de sequence PostgreSQL para garantia de atomicidade

### Entrega do XML ao cliente (REQ-FIS-003)
- Envio automático do XML da nota por e-mail ao contato fiscal do cliente (integração com E12)
- Armazenamento do XML no GED (integração com E10)
- URL de download segura do XML e DANFS-e (quando disponível pelo município)

### Tratamento de erros fiscais
- Rejeição pela prefeitura: registro do erro, alerta ao administrativo, possibilidade de reprocessar após correção
- Estado da NFS-e: `rascunho → transmitida → autorizada | rejeitada → reprocessada → cancelada`
- Cancelamento de NFS-e: fluxo manual com justificativa, prazo máximo conforme legislação do município

### Configuração fiscal por tenant
- Dados da empresa emissora: CNPJ, razão social, inscrição municipal, regime tributário (Simples Nacional / Lucro Presumido)
- Série fiscal e numeração configuráveis
- Código de serviço municipal (CNAE / código de serviço LC 116/2003) configurável por tipo de serviço
- Alíquotas de ISS configuráveis por município e regime tributário

## Fora de escopo
- NF-e de produtos (apenas NFS-e de serviços no MVP)
- Lucro Real como regime tributário (explicitamente fora do MVP per mvp-scope.md)
- Municípios além de Rondonópolis/MT na entrega inicial — arquitetura preparada para N municípios, mas apenas Rondonópolis homologado
- SPED Fiscal (pós-MVP)

## Critérios de entrada
- E06 completo (certificado emitido, evento `Certificado.emitido` disparando)
- Credenciais de homologação do broker NFS-e ou da prefeitura disponíveis

## Critérios de saída
- NFS-e autorizada emitida automaticamente após aprovação do certificado (ambiente de homologação da prefeitura/broker)
- XML salvo no sistema e enviado por e-mail ao cliente
- Rejeição registrada com código de erro e alerta ao administrativo
- Numeração fiscal: duas NFS-e consecutivas têm números sequenciais sem lacuna (verificado por teste)
- NFS-e emitida reflete corretamente os dados do certificado aprovado (valor, cliente, serviço)

## Stories previstas
- E07-S01 — Configuração fiscal por tenant (dados da empresa, série, município)
- E07-S02 — Integração com broker NFS-e (NFE.io ou Focus NFe) para Rondonópolis/MT
- E07-S03 — Emissão automática disparada por `Certificado.emitido`
- E07-S04 — Máquina de estados da NFS-e + tratamento de rejeições
- E07-S05 — Entrega do XML ao cliente (e-mail + storage)
- E07-S06 — Cancelamento de NFS-e com fluxo justificado

## Dependências
- E06 (certificado emitido + evento disparado)
- E12 (envio de e-mail com XML)
- E10 (armazenamento do XML no GED)

## Riscos
- **Risco alto:** prefeitura de Rondonópolis/MT pode não estar na cobertura do broker escolhido — validar disponibilidade antes de iniciar a story de integração
- API de homologação de prefeituras municipais pode ser instável ou ter documentação incompleta — broker intermediário (NFE.io) reduz este risco
- Alíquotas e códigos de serviço municipais podem variar — configuração por tenant mitiga

## Complexidade estimada
- Stories: 6
- Complexidade relativa: alta
- Duração estimada: 1-2 semanas
