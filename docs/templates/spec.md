# Slice NNN — <título>

**Status:** draft | approved | in-progress | verified | merged | abandoned
**Data de criação:** YYYY-MM-DD
**Autor:** <humano>
**Depende de:** slice-NNN | nenhum

---

## Contexto

Por que este slice existe? Qual problema resolve? Que stakeholder/usuário se beneficia?

1-3 parágrafos. Não repetir o que está em outras specs.

## Jornada alvo

Descrição em 1-2 parágrafos da jornada do usuário que este slice habilita. Ponta a ponta.

## Acceptance Criteria

**Regra:** cada AC vira **pelo menos um** teste automatizado (P2). ACs não-testáveis são reescritos ou movidos para fora do slice.

**OBRIGATORIO:** para cada AC de happy path, incluir pelo menos 1 AC de edge case/erro. ACs sem edge cases serao rejeitados pelo test-auditor.

### Happy path
- **AC-001:** Dado <pre-condicao>, quando <acao>, entao <resultado esperado>
- **AC-002:** ...

### Edge cases e erros (obrigatorios)
- **AC-001a:** Dado <condicao invalida>, quando <mesma acao>, entao <tratamento de erro esperado>
- **AC-001b:** Dado <condicao limite>, quando <mesma acao>, entao <comportamento no limite>
- **AC-002a:** ...

### Seguranca (se aplicavel)
- **AC-SEC-001:** Dado <input malicioso>, quando <acao>, entao <rejeicao segura sem exposicao de dados>

Formato recomendado: "Dado X, quando Y, então Z" (Gherkin-like), mas não é obrigatório.

**Exemplos de edge cases por tipo:**
- Input de texto: string vazia, string longa (>255), caracteres especiais, SQL injection, XSS
- Numeros: zero, negativo, overflow, NaN
- Auth: usuario sem permissao, token expirado, sessao invalida
- Concorrencia: dois usuarios editando ao mesmo tempo
- Rede: timeout, servidor indisponivel (para integracoes externas)

## Fora de escopo

Explicitar o que **não** está neste slice. Evita drift.

- <item 1>
- <item 2>

## Dependências externas

- Bibliotecas, serviços, APIs de terceiros
- ADRs relevantes

## Riscos conhecidos

- <risco> → <mitigação proposta>

## Notas do PM (humano)

Contexto adicional, decisões que ficaram fora do escopo, links para discussões.
