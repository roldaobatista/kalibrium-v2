# Contrato de operador LGPD — template

> **Status:** `draft-awaiting-dpo` — item T2.9 da Trilha #2 da meta-auditoria #2. Template produzido pelo agente; revisão jurídica obrigatória por advogado especialista em LGPD **antes** do primeiro tenant real. Este arquivo **não é** um contrato assinado — é o esqueleto com cláusulas obrigatórias previstas no Art. 39 da Lei 13.709/2018.

## Identificação das partes

- **Operador** (tratamento por conta e em nome do controlador): Kalibrium Tecnologia Ltda (quando constituída), CNPJ a ser registrado, com sede a ser registrada.
- **Controlador** (decide sobre finalidade e meios do tratamento): laboratório contratante (razão social, CNPJ, endereço completo, responsável legal).

## Cláusula 1 — Objeto

O Operador compromete-se a realizar o tratamento de dados pessoais que lhe forem encaminhados pelo Controlador, exclusivamente para as finalidades previstas na cláusula 3, observando as instruções escritas do Controlador e o disposto na Lei 13.709/2018 (LGPD).

## Cláusula 2 — Natureza dos dados tratados

Categorias de dados tratadas pelo Operador no escopo do Kalibrium:

- Dados cadastrais de pessoa jurídica cliente do laboratório (razão social, CNPJ, endereço).
- Dados cadastrais de pessoa física contato do cliente (nome, e-mail corporativo, telefone, cargo).
- Dados cadastrais de colaborador do laboratório (nome, e-mail, papel operacional).
- Dados técnicos de instrumentos (número de série, modelo, calibrações prévias).
- Dados de certificados emitidos (cliente, instrumento, padrões utilizados, incerteza).
- Logs de acesso com `user_id` e timestamp.

Nenhum dado pessoal sensível (saúde, biométrico, racial, político) é tratado no escopo do MVP.

## Cláusula 3 — Finalidades

Exclusivamente:

1. Permitir que o Controlador execute o fluxo de calibração no Kalibrium.
2. Armazenar os dados conforme prazo legal e regulatório.
3. Emitir certificados de calibração e documentos fiscais.
4. Atender requisições legítimas de titulares e autoridades.
5. Manter logs de auditoria exigidos pela RBC.

Qualquer uso fora destas finalidades exige aditivo contratual.

## Cláusula 4 — Duração

Este contrato vigora enquanto o Controlador for assinante ativo do Kalibrium. Após encerramento, o Operador tem 30 dias corridos para devolver ou eliminar os dados conforme cláusula 9.

## Cláusula 5 — Obrigações do Operador

- Tratar os dados somente conforme instruções do Controlador.
- Implementar e manter medidas técnicas e organizacionais adequadas de segurança.
- Manter confidencialidade sobre os dados tratados.
- Notificar o Controlador em até 24 horas sobre incidente de segurança que envolva dados pessoais.
- Auxiliar o Controlador no atendimento de direitos de titular.
- Não transferir dados a terceiros sem autorização escrita do Controlador.
- Cumprir requisitos de eliminação ou devolução ao fim do contrato.

## Cláusula 6 — Obrigações do Controlador

- Informar o Operador sobre finalidades e bases legais aplicáveis.
- Obter o consentimento do titular quando exigido.
- Responder aos titulares em nome próprio como controlador.
- Comunicar ao Operador sobre mudanças relevantes de instrução.

## Cláusula 7 — Confidencialidade

Tanto o Operador quanto seus prepostos mantêm confidencialidade sobre os dados tratados. A obrigação sobrevive ao término do contrato por prazo indeterminado.

## Cláusula 8 — Subcontratação

O Operador pode subcontratar outros operadores (fornecedores de infraestrutura, e-mail transacional, backup) mediante notificação prévia ao Controlador. Subcontratados precisam ter obrigações equivalentes. A lista vigente fica em `docs/compliance/vendor-matrix.md`.

## Cláusula 9 — Devolução ou eliminação

No encerramento do contrato, o Controlador escolhe entre devolução dos dados em formato estruturado ou eliminação definitiva. O Operador executa a escolha em até 30 dias e emite termo formal. Dados mantidos por imposição legal (por exemplo, certificados RBC com retenção obrigatória) permanecem no Operador pelo prazo legal, sob acesso restrito.

## Cláusula 10 — Foro e legislação

Legislação brasileira, foro da comarca de sede do Controlador.

## Anexos obrigatórios antes da assinatura

- Anexo A — Lista de subcontratados vigentes (espelho do `vendor-matrix.md`).
- Anexo B — DPIA inicial específico do Controlador (baseado em `docs/security/dpia.md`).
- Anexo C — Política de incidentes (`docs/security/incident-response-playbook.md`).

## Requisitos antes da primeira assinatura real

1. Revisão por advogado especialista em LGPD (não é o DPO — é a consultoria jurídica formal).
2. Ajuste das cláusulas conforme jurisprudência atual e posição da ANPD.
3. Registro da versão assinada no repositório do Kalibrium sob `docs/contracts/`.
4. Registro do aceite do Controlador em tabela imutável com hash do contrato.
