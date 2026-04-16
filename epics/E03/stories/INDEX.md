# Stories do Épico E03 — Cadastro Core

> Stories originais E03-S01 a E03-S07 foram decompostas pelo story-auditor (findings SA-001 a SA-014).
> Cada sub-story tem máximo 5 ACs e Story Contract completo.

| ID | Título | Prioridade | Depende de | Status |
|---|---|---|---|---|
| E03-S01a | Model cliente + validação CNPJ/CPF + unicidade | P0 | — (E02 fechado) | draft |
| E03-S01b | Listagem + filtro + paginação + RBAC de cliente | P0 | E03-S01a | draft |
| E03-S02a | CRUD de contato + RBAC + isolamento | P0 | E03-S01b | draft |
| E03-S02b | Consentimentos LGPD + imutabilidade + validações | P0 | E03-S02a | draft |
| E03-S03a | Model instrumento + 4 domínios + rejeição inválido + unicidade | P0 | E03-S01b | draft |
| E03-S03b | Listagens + filtros + paginação + isolamento + RBAC de instrumento | P0 | E03-S03a | draft |
| E03-S04a | Model padrão de referência + cadeia rastreabilidade + anti-ciclo | P0 | — (E02 fechado) | draft |
| E03-S04b | Listagem + status vigência + RBAC + isolamento de padrão | P0 | E03-S04a | draft |
| E03-S05a | Job expiração + detecção de vencimento + method estaVigente() | P1 | E03-S04b | draft |
| E03-S05b | Alertas UI + endpoints consulta vencimento + scheduler | P1 | E03-S05a | draft |
| E03-S06a | Model procedimento de calibração + fluxo de versões | P1 | — (E02 fechado) | draft |
| E03-S06b | Listagem + filtros + RBAC + isolamento de procedimento | P1 | E03-S06a | draft |
| E03-S07a | Configuração laravel-auditing + audit em CRUD + imutabilidade | P2 | E03-S01b, E03-S02b, E03-S03b, E03-S04b, E03-S06a | draft |
| E03-S07b | Endpoints consulta auditoria + RBAC + isolamento | P2 | E03-S07a | draft |

## Grafo de dependências

```
E02 (fechado)
├── E03-S01a (Model Cliente)
│   └── E03-S01b (Listagem + RBAC Cliente)
│       ├── E03-S02a (CRUD Contato + RBAC)
│       │   └── E03-S02b (Consentimentos LGPD)
│       └── E03-S03a (Model Instrumento)
│           └── E03-S03b (Listagens + RBAC + Desativação Instrumento)
├── E03-S04a (Model Padrão + Cadeia)
│   └── E03-S04b (Listagem + RBAC Padrão)
│       └── E03-S05a (Job Vencimento + estaVigente)
│           └── E03-S05b (Alertas UI + Endpoints + Scheduler)
└── E03-S06a (Model Procedimento + Versões)
    └── E03-S06b (Listagem + RBAC Procedimento)

E03-S07a (laravel-auditing + traits) ← depende de S01b, S02b, S03b, S04b, S06a
E03-S07b (Endpoints auditoria) ← depende de S07a
```

## Paralelismo permitido (R13)

- **E03-S01a + E03-S04a + E03-S06a** podem rodar em paralelo (nenhuma depende de outra)
- **E03-S02a + E03-S03a** podem rodar em paralelo após E03-S01b merged
- **E03-S05a** inicia apenas após E03-S04b merged
- **E03-S06b** inicia apenas após E03-S06a merged
- **E03-S07a** inicia apenas após S01b + S02b + S03b + S04b + S06a merged
- **E03-S07b** inicia apenas após E03-S07a merged

## Contagem de ACs por sub-story

| Story | ACs |
|---|---|
| E03-S01a | 6 |
| E03-S01b | 7 |
| E03-S02a | 7 |
| E03-S02b | 5 |
| E03-S03a | 7 |
| E03-S03b | 8 |
| E03-S04a | 5 |
| E03-S04b | 8 |
| E03-S05a | 6 |
| E03-S05b | 6 |
| E03-S06a | 5 |
| E03-S06b | 8 |
| E03-S07a | 5 |
| E03-S07b | 7 |
| **Total** | **95** |

> Nota de contagem: 90 ACs originais redistribuídos + 9 duplicatas removidas + 4 ACs duplicados de E03-S03c (AC-012/013/014/015) removidos com exclusão de S03c (SA-015) + 1 AC novo criado para endpoint GET /auditoria/instrumentos/{id} (SA-011) = 91 ACs únicos distribuídos em 14 sub-stories.

## Findings corrigidos

| Finding | Severidade | Ação |
|---|---|---|
| SA-001 | MAJOR | E03-S01 (13 ACs) → S01a (6) + S01b (7) |
| SA-002 | MAJOR | E03-S02 (12 ACs) → S02a (7) + S02b (5) |
| SA-003 | MAJOR | AC-005 de S02: cláusula de e-mail marketing removida (escopo de E06) |
| SA-004 | CRITICAL | E03-S03 (15 ACs) → S03a (7) + S03b (8) + S03c (4) |
| SA-005 | MAJOR | E03-S04 (13 ACs) → S04a (5) + S04b (8) |
| SA-006 | MINOR | S04a AC-001: validade mudada de `2025-12-31` (hardcoded) para `hoje + 365 dias` (relativa) |
| SA-007 | MAJOR | E03-S05 (12 ACs) → S05a (6) + S05b (6) |
| SA-008 | MINOR | S05b AC-012: verificação via `artisan schedule:list`, não inspecção de Kernel.php |
| SA-009 | MAJOR | E03-S06 (13 ACs) → S06a (5) + S06b (8) |
| SA-010 | MAJOR | E03-S07 (12 ACs) → S07a (5) + S07b (7) |
| SA-011 | MAJOR | AC novo criado: GET /auditoria/instrumentos/{id} em S07b AC-011 |
| SA-012 | MINOR | /auditoria/contatos/{id} declarado explicitamente em fora-de-escopo de S07b |
| SA-013 | MINOR | S07a não depende mais de E03-S05 (audit log não depende de alertas de vencimento) |
| SA-014 | MINOR | Seção "Testes obrigatórios" adicionada em todas as sub-stories |
| SA-015 | MAJOR | E03-S03c removida (AC-012/013/014/015 duplicados); ACs mantidos em E03-S03b; dependência de S07a atualizada de S03c → S03b |

## Critérios de saída do épico (conforme epic.md)

- [ ] Cliente cadastrado com ao menos um contato e consentimentos LGPD registrados (S01a + S01b + S02a + S02b)
- [ ] Instrumento cadastrado e vinculado ao cliente, com domínio metrológico selecionável (S03a + S03b)
- [ ] Padrão de referência cadastrado com cadeia de rastreabilidade de 2 níveis (S04a + S04b)
- [ ] Alerta de vencimento disparado quando `data_validade < hoje + 30 dias` (S05a + S05b)
- [ ] Padrão vencido bloqueado para uso em novas OS — method `estaVigente()` (S05a)
- [ ] Procedimento de calibração versionado com fluxo rascunho → vigente → obsoleto (S06a + S06b)
- [ ] Audit log registrando alterações em dados de cliente (S07a + S07b)
