# Roadmap de slices - Kalibrium MVP

**Versao:** 1 (inicial)
**Data:** 2026-04-12
**Construido por:** /next-slice wizard
**Base:** PRD congelado, escopo MVP, jornadas, personas, `epics/ROADMAP.md`, ADR-0001 e ADR-0002

## Convencoes

- Ordem reflete dependencias hard, nao preferencia subjetiva.
- Codigo `DOMAIN-NNN` e semantico; `specs/NNN/` e posicional.
- `specs/001` a `specs/005` ja foram usados para E01, setup e infraestrutura.
- E01 ainda tem um slice pendente: Frontend base (Vite 8 + Tailwind CSS 4 + Livewire 4 + Alpine.js).
- `specs/900` e smoke test de harness, nao e slice de produto.
- ADRs bloqueantes devem ser decididos antes do slice iniciar.
- Slices com UI tambem dependem do pacote documental do epico correspondente: wireframes, ERD, API contracts, user flows e migrations spec.

## Lista ordenada

### 0. INF-006 - Frontend base do sistema

- **NNN sugerido:** 006
- **Dominio:** INF
- **Epico base:** E01 - Setup e Infraestrutura
- **Depende de:** E01-S01 (`specs/001`)
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** nenhum bloqueio de produto; e slice de infraestrutura sem tela de negocio
- **Tamanho:** medio
- **O que entrega:** build de frontend, Tailwind, Livewire, Alpine e uma pagina tecnica de sanidade para confirmar que a base visual funciona
- **Por que antes do produto:** sem a base de frontend, qualquer tela real do E02 comeca em terreno instavel.

### 1. SEG-001 - Login seguro do laboratorio

- **NNN sugerido:** 007
- **Dominio:** SEG
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** E01 completo (`specs/001` a `specs/005`)
- **ADRs bloqueantes:** ADR-0004 (IdP final: Fortify/Sanctum vs Keycloak vs WorkOS)
- **Outros bloqueios:** documentacao por epico E02 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** login por e-mail e senha, recuperacao de senha e 2FA para gerente/administrativo
- **Por que primeiro:** sem acesso seguro, nenhuma tela de negocio deve existir.

### 2. TEN-001 - Primeiro laboratorio isolado

- **NNN sugerido:** 008
- **Dominio:** TEN
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** SEG-001
- **ADRs bloqueantes:** ADR-0004 se ainda nao estiver decidido
- **Outros bloqueios:** documentacao por epico E02 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** cadastro inicial do laboratorio, empresa/filial raiz e isolamento verificavel dos dados
- **Por que nessa ordem:** o laboratorio precisa existir como espaco isolado antes de clientes, instrumentos e operacao.

### 3. TEN-002 - Usuarios, papeis e plano do laboratorio

- **NNN sugerido:** 009
- **Dominio:** TEN
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** TEN-001
- **ADRs bloqueantes:** nenhum adicional apos ADR-0004
- **Outros bloqueios:** documentacao por epico E02 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** usuarios com papeis gerente, tecnico, administrativo e visualizador, alem de plano/limites basicos
- **Por que nessa ordem:** os proximos cadastros precisam respeitar permissoes desde o primeiro dia.

### 4. TEN-003 - Clientes e contatos

- **NNN sugerido:** 010
- **Dominio:** TEN
- **Epico base:** E03 - Cadastro Core
- **Depende de:** TEN-002
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** documentacao por epico E03 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** cadastro de cliente por CNPJ/CPF, contatos, e consentimentos por e-mail/WhatsApp
- **Por que nessa ordem:** a primeira jornada real comeca pelo cliente que pede a calibracao.

### 5. MET-001 - Instrumentos do cliente

- **NNN sugerido:** 011
- **Dominio:** MET
- **Epico base:** E03 - Cadastro Core
- **Depende de:** TEN-003
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** documentacao por epico E03 antes de implementar UI; PD-003/ASS-002 se a terminologia instrumento/equipamento afetar a tela
- **Tamanho:** pequeno
- **O que entrega:** cadastro de instrumentos por modelo, numero de serie, faixa, resolucao e dominio metrologico
- **Por que nessa ordem:** sem instrumento cadastrado nao existe ordem de servico de calibracao.

### 6. MET-002 - Padroes e procedimentos

- **NNN sugerido:** 012
- **Dominio:** MET
- **Epico base:** E03 - Cadastro Core
- **Depende de:** MET-001
- **ADRs bloqueantes:** ADR-0005 se houver upload de certificado do padrao neste slice
- **Outros bloqueios:** documentacao por epico E03 antes de implementar UI; PD-003/ASS-002 se a terminologia ainda estiver aberta
- **Tamanho:** medio
- **O que entrega:** cadastro de padroes de referencia, validade/rastreabilidade e procedimentos de calibracao versionados
- **Por que nessa ordem:** a calibracao so pode usar padroes vigentes e procedimentos definidos.

### 7. FLX-001 - Nova ordem de servico

- **NNN sugerido:** 013
- **Dominio:** FLX
- **Epico base:** E04 - Ordens de Servico e Fluxo Operacional
- **Depende de:** MET-002
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** documentacao por epico E04 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** criacao de OS a partir de cliente, instrumento, procedimento, prazo e tecnico responsavel
- **Por que nessa ordem:** este e o primeiro ponto em que o laboratorio registra um pedido real.

### 8. FLX-002 - Agenda, fila e status da OS

- **NNN sugerido:** 014
- **Dominio:** FLX
- **Epico base:** E04 - Ordens de Servico e Fluxo Operacional
- **Depende de:** FLX-001
- **ADRs bloqueantes:** ADR-0003 se notificacoes assíncronas forem implementadas neste slice
- **Outros bloqueios:** documentacao por epico E04 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** agendamento, fila do tecnico, status da OS e checklist operacional
- **Por que nessa ordem:** depois de criar o pedido, o laboratorio precisa saber quem faz o que e quando.

### 9. MET-003 - Execucao de calibracao na bancada

- **NNN sugerido:** 015
- **Dominio:** MET
- **Epico base:** E05 - Laboratorio e Calibracao
- **Depende de:** FLX-002
- **ADRs bloqueantes:** nenhum tecnico adicional conhecido
- **Outros bloqueios:** documentacao por epico E05 antes de implementar UI; PD-003/ASS-018 antes de usar planilha de procedimento como base formal
- **Tamanho:** grande
- **O que entrega:** tela de bancada para registrar pontos medidos, condicoes ambientais e padroes usados
- **Por que nessa ordem:** e o primeiro slice que tira o tecnico da anotacao em papel/planilha solta.

### 10. MET-004 - Incerteza, historico tecnico e lacres/selos

- **NNN sugerido:** 016
- **Dominio:** MET
- **Epico base:** E05 - Laboratorio e Calibracao
- **Depende de:** MET-003
- **ADRs bloqueantes:** nenhum tecnico adicional conhecido
- **Outros bloqueios:** PD-003/ASS-018 para calculo de incerteza via planilha; consultor de metrologia para casos de referencia
- **Tamanho:** grande
- **O que entrega:** calculo de incerteza, historico do instrumento, lacres/selos e bloqueio de padrao vencido
- **Por que nessa ordem:** sem incerteza e rastreabilidade, o certificado nao tem valor metrologico.

### 11. CMP-001 - Aprovacao, certificado e entrega ao cliente

- **NNN sugerido:** 017
- **Dominio:** CMP
- **Epico base:** E06 - Certificado de Calibracao; E09 - Portal do Cliente Final; E12 - Comunicacao
- **Depende de:** MET-004
- **ADRs bloqueantes:** ADR-0003 (filas/background jobs), ADR-0005 (storage de documentos)
- **Outros bloqueios:** PD-003/ASS-012 para regra final de cancelamento/substituicao do certificado; consultor de metrologia para formato de certificado
- **Tamanho:** grande
- **O que entrega:** aprovacao pelo gerente, certificado PDF numerado, link seguro de download e log de acesso
- **Por que nessa ordem:** o cliente paga pelo certificado; esse e o documento final da operacao tecnica.

### 12. FIS-001 - NFS-e, contas a receber e painel minimo

- **NNN sugerido:** 018
- **Dominio:** FIS
- **Epico base:** E07 - Fiscal; E08 - Financeiro; E11 - Dashboard Operacional
- **Depende de:** CMP-001
- **ADRs bloqueantes:** ADR-0003 (filas/background jobs), ADR-0009 (provedor fiscal)
- **Outros bloqueios:** consultor fiscal para Rondonopolis/MT antes de go-live fiscal
- **Tamanho:** grande
- **O que entrega:** NFS-e apos certificado aprovado, titulo a receber, baixa manual e painel minimo de pedidos/recebiveis
- **Por que nessa ordem:** fecha a jornada de receita do MVP: calibracao concluida, certificado entregue, nota emitida e cobranca registrada.

## Proximo slice recomendado agora

O proximo slice recomendado agora e **INF-006 - Frontend base do sistema**, em `specs/006`. Ele fecha o E01 antes de iniciar as telas reais do produto.

Depois dele, o primeiro slice de produto recomendado e **SEG-001 - Login seguro do laboratorio**, em `specs/007`, mas ele fica bloqueado ate a decisao **ADR-0004** sobre a estrategia de identidade.

Recomendacao forte para ADR-0004: escolher **Laravel Fortify/Sanctum** para o MVP, porque o produto precisa de login seguro, 2FA e recuperacao de senha sem carregar custo operacional de Keycloak/WorkOS antes do primeiro cliente.
