# Slice 008 — TEN-001 - Primeiro laboratorio isolado

**Status:** draft
**Data de criação:** 2026-04-14
**Autor:** roldaobatista
**Depende de:** slice-007

---

## Contexto

Este slice continua o E02 depois do login seguro. Ele existe para permitir que Marcelo, como gerente do laboratório, configure os dados mínimos do primeiro laboratório dentro do Kalibrium: razão social, CNPJ, nome fantasia, contato principal, perfil operacional e emissão de certificado metrológico.

O resultado esperado é que o laboratório deixe de ser apenas um vínculo de login e passe a ter uma base própria de operação. Essa base precisa criar ou atualizar o tenant, a empresa raiz e a filial raiz com isolamento verificável, para que os próximos slices possam cadastrar usuários, clientes, instrumentos e ordens de serviço sem misturar dados de laboratórios diferentes.

## Jornada alvo

Marcelo entra no sistema pelo fluxo do slice 007, acessa `/settings/tenant` e vê um formulário de configurações do laboratório. Ele preenche os dados cadastrais e salva. O sistema valida os campos obrigatórios, grava as informações no laboratório atual, cria ou atualiza a empresa raiz e a filial raiz, registra a alteração e mostra uma confirmação.

Se outro laboratório existir no mesmo ambiente, Marcelo não vê nem altera dados desse outro laboratório. Se o usuário não for gerente, se o laboratório estiver em modo somente leitura, ou se os dados forem inválidos, o sistema bloqueia a alteração com uma mensagem segura e não grava dados parciais.

## Acceptance Criteria

**Regra:** cada AC vira pelo menos um teste automatizado (P2). Para cada happy path, ha pelo menos um edge case ou erro correspondente.

### Happy path

- **AC-001:** Dado um usuário gerente autenticado em um tenant `active`, quando acessar `GET /settings/tenant`, então o sistema retorna HTTP 200 e exibe os campos razão social, CNPJ, nome fantasia, e-mail principal, telefone, perfil operacional e emissão de certificado metrológico.
- **AC-002:** Dado um usuário gerente autenticado em um tenant `active` sem empresa raiz e filial raiz completas, quando salvar dados válidos em `/settings/tenant`, então o sistema atualiza o tenant atual, cria a empresa raiz e cria a filial raiz vinculadas ao mesmo tenant.
- **AC-003:** Dado um usuário gerente autenticado em um tenant `active` que já possui empresa raiz e filial raiz, quando salvar novos dados válidos em `/settings/tenant`, então o sistema atualiza os registros existentes e não cria empresa raiz ou filial raiz duplicadas.
- **AC-004:** Dado um usuário gerente autenticado em um tenant `trial`, quando salvar dados válidos em `/settings/tenant`, então o sistema permite a configuração inicial do laboratório e mantém os registros vinculados ao tenant atual.
- **AC-005:** Dado dois tenants com gerentes diferentes e dados cadastrados, quando o gerente do tenant A acessar ou salvar `/settings/tenant`, então o sistema mostra e altera somente dados do tenant A, sem expor nomes, CNPJ, empresa ou filial do tenant B.
- **AC-006:** Dado uma alteração bem-sucedida em `/settings/tenant`, quando a operação terminar, então o sistema registra auditoria com usuário, tenant, ação e campos alterados, sem persistir senha, token de reset, segredo TOTP ou código de recuperação.

### Edge cases e erros

- **AC-007:** Dado CNPJ ausente, inválido ou já usado por outro tenant raiz, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação, mantém o formulário preenchido e não altera tenant, empresa raiz ou filial raiz.
- **AC-008:** Dado razão social vazia, e-mail principal inválido ou perfil operacional inválido, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação específico do campo e não grava dados parciais.
- **AC-009:** Dado um usuário autenticado sem papel `gerente`, quando acessar ou salvar `/settings/tenant`, então o sistema bloqueia a ação, não exibe o formulário editável e não altera dados do laboratório.
- **AC-010:** Dado um usuário gerente autenticado em tenant `suspended` com sessão em modo somente leitura, quando acessar `/settings/tenant`, então o sistema permite visualizar os dados do laboratório, mas quando tentar salvar retorna bloqueio de somente leitura e não altera registros.
- **AC-011:** Dado um usuário gerente autenticado cujo vínculo de tenant foi removido, suspenso ou trocado antes do salvamento, quando enviar `/settings/tenant`, então o sistema revalida o acesso, bloqueia a alteração e não grava no tenant anterior.
- **AC-012:** Dado falha ao criar ou atualizar empresa raiz ou filial raiz, quando o gerente salvar `/settings/tenant`, então o sistema desfaz a operação inteira e não deixa tenant, empresa e filial em estados divergentes.

### Segurança

- **AC-SEC-001:** Dado payload com ID de empresa, filial ou tenant pertencente a outro laboratório, quando o gerente salvar `/settings/tenant`, então o sistema ignora ou rejeita o ID externo e não altera registros fora do tenant atual.
- **AC-SEC-002:** Dado input contendo HTML, JavaScript ou payload SQL comum em campos textuais como razão social, nome fantasia ou e-mail, quando o gerente salvar e depois visualizar `/settings/tenant`, então o sistema trata o input como dado, valida o que for inválido e não executa nem reflete conteúdo sem escape.
- **AC-SEC-003:** Dado uma tentativa bloqueada por permissão, tenant somente leitura ou vínculo inválido, quando o sistema responder ao usuário, então a mensagem não revela dados de outro tenant nem detalhes internos de verificação.

## Fora de escopo

- Registro público de novo laboratório por visitante anônimo.
- Convite de usuários, alteração de papéis e remoção do último gerente.
- Tela de planos e limites.
- Tela de privacidade, base legal e consentimentos LGPD.
- Portal do cliente final.
- Gestão de clientes, instrumentos, padrões ou ordens de serviço.
- Cobrança real, troca de plano e emissão de nota fiscal.
- Suporte interno Kalibrium em `/admin/tenants`.

## Dependências externas

- slice-007: login seguro, sessão autenticada, vínculo de usuário ao tenant e modo somente leitura para tenant `suspended`.
- ADR-0004: Laravel Fortify + Sanctum como estratégia de identidade do MVP.
- ADR-0001: Laravel 13, Livewire 4, PostgreSQL 18 e PHP 8.4+.
- Gate documental E02: `docs/design/wireframes/wireframes-e02-auth.md`, `docs/architecture/api-contracts/api-e02-auth.md`, `docs/architecture/data-models/erd-e02-auth.md`, `docs/architecture/data-models/migrations-e02-auth.md` e `docs/product/flows/flows-e02-auth.md`.
- PostgreSQL para isolamento por tenant e consistência transacional.

## Riscos conhecidos

- Cadastro parcial pode deixar tenant atualizado sem empresa ou filial raiz -> mitigação: AC-012 exige operação atômica.
- Usuário sem papel de gerente pode alterar dados se a proteção ficar só na tela -> mitigação: AC-009 exige bloqueio no acesso e no salvamento.
- Tenant suspenso pode receber alteração indevida se o modo somente leitura não for revalidado no salvamento -> mitigação: AC-010 exige bloqueio de escrita.
- IDs enviados pelo formulário podem apontar para outro laboratório -> mitigação: AC-SEC-001 exige rejeição ou ignorar IDs externos.
- Mensagem de erro pode revelar dados de outro tenant quando CNPJ já existir -> mitigação: AC-007 e AC-SEC-003 exigem erro seguro.

## Notas do PM

Este slice entrega a primeira configuração do laboratório dentro do sistema. Depois dele, a sequência recomendada continua com usuários, papéis e plano do laboratório.
