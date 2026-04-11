# Módulos proibidos para IA — Kalibrium

> **Status:** ativo. Item T2.15 da Trilha #2 (gap novo de cobertura). **Semanticamente distinto** de `out-of-scope.md` (que é "fora do MVP"). Este arquivo lista módulos que o Kalibrium **pode** atender em algum momento, mas cuja implementação **não pode** ser feita pelo agente de IA mesmo com consultor, e precisa de integrador humano ou terceirização. A distinção existe porque apostar em IA para cobrir tudo foi um dos alertas das 3 auditorias externas.

## Regra

Módulo listado aqui **bloqueia** qualquer slice que tente implementá-lo via agente. A resposta é sempre: terceirizar com provedor especializado, adiar, ou integrar via gateway pronto. Se um slice for aberto violando esta regra, o verifier reprova automaticamente e o slice volta para discovery.

## Lista de módulos proibidos

### 1. Integração SEFAZ via webservice por UF

**Por que não pode por IA.** A integração com SEFAZ para NF-e/NFS-e exige contato SOAP com certificado A1, tratamento de erro por código específico de cada UF, reprocessamento de lote, cancelamento, inutilização, consulta, carta de correção. Regras mudam por UF e por versão do layout. Um erro de implementação pode resultar em rejeição de lote, passivo fiscal, processo administrativo. O mercado tem especialistas humanos que dedicam anos só a isso.

**Caminho alternativo.** Terceirizar com provedor de NFS-e listado em `vendor-matrix.md`. O Kalibrium integra com o provedor via API REST simples. O provedor assume o risco de homologação.

**Decisão de escopo.** Dentro do MVP, mas via integração, não implementação direta.

### 2. Assinatura ICP-Brasil A3 em HSM

**Por que não pode por IA.** A3 em HSM exige driver específico por fabricante do hardware, integração PKCS#11 ou equivalente, presença física da pessoa titular no momento da assinatura, política de custódia do token. Erro de implementação pode invalidar a assinatura ou vazar a chave privada.

**Caminho alternativo.** Se o cliente exigir, terceirizar com serviço de assinatura remota qualificada (RA) via API. A chave permanece no provedor certificado, o Kalibrium só chama.

**Decisão de escopo.** Fora do MVP (ver `out-of-scope.md`). Retorno condicional à demanda de cliente pagante.

### 3. Leitura direta do eSocial

**Por que não pode por IA.** O eSocial tem centenas de leiautes, regras de envio, tabelas de dependência, formato XML específico. É o tipo de sistema que mesmo empresas grandes terceirizam.

**Caminho alternativo.** O Kalibrium **não** entra no eSocial no MVP ou depois. Se surgir, é via integração com sistema de folha de pagamento que já fale eSocial.

**Decisão de escopo.** Fora do MVP permanentemente.

### 4. Conexão com AFD do REP-P

**Por que não pode por IA.** REP-P exige dispositivo certificado, comunicação por protocolo proprietário, layout do Arquivo-Fonte de Dados (AFD) regulamentado. O Kalibrium não registra ponto (ver `out-of-scope.md`).

**Caminho alternativo.** Integração com sistema especializado quando o laboratório pedir, via API do sistema de terceiro — nunca leitura direta do hardware.

**Decisão de escopo.** Fora do MVP permanentemente.

### 5. Cálculo de orçamento de incerteza customizado além do GUM básico

**Por que não pode por IA sem consultor.** O cálculo de incerteza segue o GUM/JCGM 100:2008, mas a aplicação do GUM a um procedimento específico do laboratório envolve decisões de engenharia metrológica (fontes de incerteza relevantes, distribuição assumida, grau de liberdade efetivo) que IA sem consultor pode propor, mas não validar. Erro aqui emite certificado numericamente errado.

**Caminho alternativo.** Consultor de metrologia valida cada procedimento. IA pode gerar rascunho a partir de template, mas a assinatura do consultor é obrigatória.

**Decisão de escopo.** Dentro do MVP, mas toda emissão de novo procedimento exige validação humana externa antes de entrar em produção.

### 6. Parecer jurídico sobre cláusula contratual

**Por que não pode por IA.** Contratos de operador, NDAs, acordos com consultor, termos de uso — tudo isso exige parecer de advogado humano. IA pode redigir rascunho, nunca assinar.

**Caminho alternativo.** Advogado LGPD (item do `procurement-tracker.md`) + advogado empresarial para NDA.

**Decisão de escopo.** Processo permanente. Qualquer contrato exige assinatura humana qualificada.

## Como este arquivo bloqueia

- O `pre-push-gate.sh` (ou gate equivalente) vai, no Bloco 3, inspecionar o slice sendo enviado. Se o slice tocar em arquivo sob um dos caminhos listados aqui (`src/integration/sefaz/**`, por exemplo), o gate bloqueia e exige entrada em `docs/decisions/no-go-override-NNN.md` com aprovação do PM.
- O `/guide-check` verifica se os caminhos listados aqui continuam marcados como no-go a cada rodada.

## Manutenção

- **Adicionar módulo:** criar seção numerada nova seguindo o template (por que não pode, caminho alternativo, decisão de escopo).
- **Retirar módulo:** justificar com ADR em `docs/adr/` e rodar retrospectiva antes de remover.
- **Revisão periódica:** a cada 90 dias verificar se o estado do mercado e da regulação mudou o suficiente para retirar algum item.
