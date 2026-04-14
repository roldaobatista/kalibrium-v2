# Slice 008 — TEN-001 - Primeiro laboratorio isolado

**Status:** revisão aprovada; aguardando gates finais
**Data:** 2026-04-14
**Slice:** 008

---

## O que foi feito

Esta entrega cobre os seguintes critérios:

- **AC-001** — Dado um usuário gerente autenticado em um tenant `active`, quando acessar `GET /settings/tenant`, então o sistema retorna HTTP 200 e exibe os campos razão social, CNPJ, nome fantasia, e-mail principal, telefone, perfil operacional e emissão de certificado metrológico.
- **AC-002** — Dado um usuário gerente autenticado em um tenant `active` sem empresa raiz e filial raiz completas, quando salvar dados válidos em `/settings/tenant`, então o sistema atualiza o tenant atual, cria a empresa raiz e cria a filial raiz vinculadas ao mesmo tenant.
- **AC-003** — Dado um usuário gerente autenticado em um tenant `active` que já possui empresa raiz e filial raiz, quando salvar novos dados válidos em `/settings/tenant`, então o sistema atualiza os registros existentes e não cria empresa raiz ou filial raiz duplicadas.
- **AC-004** — Dado um usuário gerente autenticado em um tenant `trial`, quando salvar dados válidos em `/settings/tenant`, então o sistema permite a configuração inicial do laboratório e mantém os registros vinculados ao tenant atual.
- **AC-005** — Dado dois tenants com gerentes diferentes e dados cadastrados, quando o gerente do tenant A acessar ou salvar `/settings/tenant`, então o sistema mostra e altera somente dados do tenant A, sem expor nomes, CNPJ, empresa ou filial do tenant B.
- **AC-006** — Dado uma alteração bem-sucedida em `/settings/tenant`, quando a operação terminar, então o sistema registra auditoria com usuário, tenant, ação e campos alterados, sem persistir senha, token de reset, segredo TOTP ou código de recuperação.
- **AC-007** — Dado CNPJ ausente, inválido ou já usado por outro tenant raiz, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação, mantém o formulário preenchido e não altera tenant, empresa raiz ou filial raiz.
- **AC-008** — Dado razão social vazia, e-mail principal inválido ou perfil operacional inválido, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação específico do campo e não grava dados parciais.
- **AC-009** — Dado um usuário autenticado sem papel `gerente`, quando acessar ou salvar `/settings/tenant`, então o sistema bloqueia a ação, não exibe o formulário editável e não altera dados do laboratório.
- **AC-010** — Dado um usuário gerente autenticado em tenant `suspended` com sessão em modo somente leitura, quando acessar `/settings/tenant`, então o sistema permite visualizar os dados do laboratório, mas quando tentar salvar retorna bloqueio de somente leitura e não altera registros.
- **AC-011** — Dado um usuário gerente autenticado cujo vínculo de tenant foi removido, suspenso ou trocado antes do salvamento, quando enviar `/settings/tenant`, então o sistema revalida o acesso, bloqueia a alteração e não grava no tenant anterior.
- **AC-012** — Dado falha ao criar ou atualizar empresa raiz ou filial raiz, quando o gerente salvar `/settings/tenant`, então o sistema desfaz a operação inteira e não deixa tenant, empresa e filial em estados divergentes.

## O que o usuário final vai ver

- Dado um usuário gerente autenticado em um tenant `active`, quando acessar `GET /settings/tenant`, então o sistema retorna HTTP 200 e exibe os campos razão social, CNPJ, nome fantasia, e-mail principal, telefone, perfil operacional e emissão de certificado metrológico.
- Dado um usuário gerente autenticado em um tenant `active` sem empresa raiz e filial raiz completas, quando salvar dados válidos em `/settings/tenant`, então o sistema atualiza o tenant atual, cria a empresa raiz e cria a filial raiz vinculadas ao mesmo tenant.
- Dado um usuário gerente autenticado em um tenant `active` que já possui empresa raiz e filial raiz, quando salvar novos dados válidos em `/settings/tenant`, então o sistema atualiza os registros existentes e não cria empresa raiz ou filial raiz duplicadas.
- Dado um usuário gerente autenticado em um tenant `trial`, quando salvar dados válidos em `/settings/tenant`, então o sistema permite a configuração inicial do laboratório e mantém os registros vinculados ao tenant atual.
- Dado dois tenants com gerentes diferentes e dados cadastrados, quando o gerente do tenant A acessar ou salvar `/settings/tenant`, então o sistema mostra e altera somente dados do tenant A, sem expor nomes, CNPJ, empresa ou filial do tenant B.
- Dado uma alteração bem-sucedida em `/settings/tenant`, quando a operação terminar, então o sistema registra auditoria com usuário, tenant, ação e campos alterados, sem persistir senha, token de reset, segredo TOTP ou código de recuperação.
- Dado CNPJ ausente, inválido ou já usado por outro tenant raiz, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação, mantém o formulário preenchido e não altera tenant, empresa raiz ou filial raiz.
- Dado razão social vazia, e-mail principal inválido ou perfil operacional inválido, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação específico do campo e não grava dados parciais.
- Dado um usuário autenticado sem papel `gerente`, quando acessar ou salvar `/settings/tenant`, então o sistema bloqueia a ação, não exibe o formulário editável e não altera dados do laboratório.
- Dado um usuário gerente autenticado em tenant `suspended` com sessão em modo somente leitura, quando acessar `/settings/tenant`, então o sistema permite visualizar os dados do laboratório, mas quando tentar salvar retorna bloqueio de somente leitura e não altera registros.
- Dado um usuário gerente autenticado cujo vínculo de tenant foi removido, suspenso ou trocado antes do salvamento, quando enviar `/settings/tenant`, então o sistema revalida o acesso, bloqueia a alteração e não grava no tenant anterior.
- Dado falha ao criar ou atualizar empresa raiz ou filial raiz, quando o gerente salvar `/settings/tenant`, então o sistema desfaz a operação inteira e não deixa tenant, empresa e filial em estados divergentes.

## O que funcionou

- ✓ Dado um usuário gerente autenticado em um tenant `active`, quando acessar `GET /settings/tenant`, então o sistema retorna HTTP 200 e exibe os campos razão social, CNPJ, nome fantasia, e-mail principal, telefone, perfil operacional e emissão de certificado metrológico.
- ✓ Dado um usuário gerente autenticado em um tenant `active` sem empresa raiz e filial raiz completas, quando salvar dados válidos em `/settings/tenant`, então o sistema atualiza o tenant atual, cria a empresa raiz e cria a filial raiz vinculadas ao mesmo tenant.
- ✓ Dado um usuário gerente autenticado em um tenant `active` que já possui empresa raiz e filial raiz, quando salvar novos dados válidos em `/settings/tenant`, então o sistema atualiza os registros existentes e não cria empresa raiz ou filial raiz duplicadas.
- ✓ Dado um usuário gerente autenticado em um tenant `trial`, quando salvar dados válidos em `/settings/tenant`, então o sistema permite a configuração inicial do laboratório e mantém os registros vinculados ao tenant atual.
- ✓ Dado dois tenants com gerentes diferentes e dados cadastrados, quando o gerente do tenant A acessar ou salvar `/settings/tenant`, então o sistema mostra e altera somente dados do tenant A, sem expor nomes, CNPJ, empresa ou filial do tenant B.
- ✓ Dado uma alteração bem-sucedida em `/settings/tenant`, quando a operação terminar, então o sistema registra auditoria com usuário, tenant, ação e campos alterados, sem persistir senha, token de reset, segredo TOTP ou código de recuperação.
- ✓ Dado CNPJ ausente, inválido ou já usado por outro tenant raiz, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação, mantém o formulário preenchido e não altera tenant, empresa raiz ou filial raiz.
- ✓ Dado razão social vazia, e-mail principal inválido ou perfil operacional inválido, quando o gerente salvar `/settings/tenant`, então o sistema retorna erro de validação específico do campo e não grava dados parciais.
- ✓ Dado um usuário autenticado sem papel `gerente`, quando acessar ou salvar `/settings/tenant`, então o sistema bloqueia a ação, não exibe o formulário editável e não altera dados do laboratório.
- ✓ Dado um usuário gerente autenticado em tenant `suspended` com sessão em modo somente leitura, quando acessar `/settings/tenant`, então o sistema permite visualizar os dados do laboratório, mas quando tentar salvar retorna bloqueio de somente leitura e não altera registros.
- ✓ Dado um usuário gerente autenticado cujo vínculo de tenant foi removido, suspenso ou trocado antes do salvamento, quando enviar `/settings/tenant`, então o sistema revalida o acesso, bloqueia a alteração e não grava no tenant anterior.
- ✓ Dado falha ao criar ou atualizar empresa raiz ou filial raiz, quando o gerente salvar `/settings/tenant`, então o sistema desfaz a operação inteira e não deixa tenant, empresa e filial em estados divergentes.

## O que NÃO está neste slice (fica pra depois)

- Registro público de novo laboratório por visitante anônimo.
- Convite de usuários, alteração de papéis e remoção do último gerente.
- Tela de planos e limites.
- Tela de privacidade, base legal e consentimentos LGPD.
- Portal do cliente final.
- Gestão de clientes, instrumentos, padrões ou ordens de serviço.
- Cobrança real, troca de plano e emissão de nota fiscal.
- Suporte interno Kalibrium em `/admin/tenants`.

## Próximo passo

Seguir para as revisões de segurança, testes e funcionalidade antes de qualquer merge.

---

<details>
<summary>Detalhes técnicos (não precisa abrir)</summary>

- **Verifier verdict:** approved
- **Reviewer verdict:** approved
- **Security verdict:** -
- **Test audit verdict:** -
- **Functional verdict:** -
- **ACs pass/fail:** 12 / 0
- **Artefatos:**
    - `specs/008/spec.md`
    - `specs/008/verification.json`
    - `specs/008/review.json`

Tradução gerada automaticamente por `scripts/translate-pm.sh` (B-010).

</details>
