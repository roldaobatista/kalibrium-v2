# Slice 012 — E03-S01a: Model cliente + validação CNPJ/CPF + unicidade

**Status:** revisão aprovada; aguardando gates finais
**Data:** 2026-04-16
**Slice:** 012

---

## O que foi feito

Esta entrega cobre os seguintes critérios:

- **AC-001** — — Criação de cliente PJ com CNPJ válido
- **AC-002** — — Criação de cliente PF com CPF válido
- **AC-003** — — Rejeição de CNPJ inválido
- **AC-004** — — Rejeição de CPF inválido
- **AC-005** — — Unicidade de CNPJ/CPF dentro do tenant
- **AC-006** — — Isolamento de CNPJ entre tenants
- **AC-007** — — Soft-delete de cliente ativo
- **AC-008** — — Soft-delete de cliente já inativo retorna 409
- **AC-009** — — Migration e seeder executam sem erro

## O que o usuário final vai ver

- — Criação de cliente PJ com CNPJ válido
- — Criação de cliente PF com CPF válido
- — Rejeição de CNPJ inválido
- — Rejeição de CPF inválido
- — Unicidade de CNPJ/CPF dentro do tenant
- — Isolamento de CNPJ entre tenants
- — Soft-delete de cliente ativo
- — Soft-delete de cliente já inativo retorna 409
- — Migration e seeder executam sem erro

## O que funcionou

- ✓ — Criação de cliente PJ com CNPJ válido
- ✓ — Criação de cliente PF com CPF válido
- ✓ — Rejeição de CNPJ inválido
- ✓ — Rejeição de CPF inválido
- ✓ — Unicidade de CNPJ/CPF dentro do tenant
- ✓ — Isolamento de CNPJ entre tenants
- ✓ — Soft-delete de cliente ativo
- ✓ — Soft-delete de cliente já inativo retorna 409
- ✓ — Migration e seeder executam sem erro

## O que NÃO está neste slice (fica pra depois)

- Listagem paginada e filtros (E03-S01b)
- RBAC de escrita/leitura (E03-S01b)
- Contatos do cliente (E03-S02a)
- Importação em massa via CSV (pós-MVP)
- Consulta à API da Receita Federal (pós-MVP)
- Histórico de alterações / audit log (E03-S07a)

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
- **ACs pass/fail:** 9 / 0
- **Artefatos:**
    - `specs/012/spec.md`
    - `specs/012/verification.json`
    - `specs/012/review.json`

Tradução gerada automaticamente por `scripts/translate-pm.sh` (B-010).

</details>
