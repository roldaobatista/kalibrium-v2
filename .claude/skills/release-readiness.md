---
description: Valida se o projeto esta pronto para release. Checa todos os epicos/stories completos, gates aprovados, documentacao atualizada, testes verdes, seguranca OK. Gera relatorio final para PM. Uso: /release-readiness.
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit fix SK-005"
---

# /release-readiness

## Uso
```
/release-readiness
```

## Por que existe
Release nao e "parece pronto". E um checklist objetivo que valida que tudo foi feito, testado, revisado e documentado. Nenhum release sem este gate.

## Quando invocar
Quando todos os epicos do MVP estiverem completos e o PM quiser lancar.

## Pre-condicoes
- Pelo menos 1 epico completo
- `project-state.json` existe

## O que faz

### 1. Checklist de Release

#### Produto
- [ ] Todos os epicos do MVP marcados como completos
- [ ] Todas as stories de cada epico com merge feito
- [ ] Nenhuma story com rejeicao R6 pendente
- [ ] PRD frozen e escopo respeitado

#### Qualidade
- [ ] Todos os slices com verification.json `approved`
- [ ] Todos os slices com review.json `approved`
- [ ] Todos os slices com security-review.json `approved`
- [ ] Todos os slices com test-audit.json `approved`
- [ ] Todos os slices com functional-review.json `approved`
- [ ] Nenhum finding critical/high pendente

#### Testes
- [ ] Suite completa roda verde
- [ ] Cobertura de AC: 100% dos ACs tem teste
- [ ] Testes E2E dos fluxos criticos passam

#### Seguranca
- [ ] Threat model revisado e atualizado
- [ ] Nenhum secret no repositorio
- [ ] Dependencias sem vulnerabilidades conhecidas criticas
- [ ] LGPD compliance checklist completo

#### Documentacao
- [ ] README atualizado
- [ ] ADRs completos e aceitos
- [ ] API documentada (se aplicavel)
- [ ] Runbook de operacao existe
- [ ] Changelog gerado

#### Operacao
- [ ] Healthcheck endpoint funcional
- [ ] Logs estruturados configurados
- [ ] Backup configurado
- [ ] Rollback testado
- [ ] Variaveis de ambiente documentadas
- [ ] Migracoes testadas em ambiente limpo

### 2. Executar validacoes automaticas
- Rodar suite de testes completa (unico momento permitido — P8)
- Verificar dependencias com vulnerabilidades
- Validar que nenhum .env ou secret esta commitado
- Verificar que todas as migracoes rodam em banco limpo

### 3. Apresentar ao PM

**Caso pronto:**
```
🚀 Release Readiness: PRONTO

Checklist completo:
✅ Produto: 3/3 epicos completos, 12/12 stories merged
✅ Qualidade: todos os gates aprovados em todos os slices
✅ Testes: suite verde, cobertura 100% dos ACs
✅ Seguranca: sem findings pendentes, LGPD OK
✅ Documentacao: README, ADRs, runbook atualizados
✅ Operacao: healthcheck, logs, backup, rollback OK

O projeto esta pronto para deploy em producao.
Proximo passo: aprovar o deploy. Confirma? (sim/nao)
```

**Caso nao pronto:**
```
⚠️ Release Readiness: NAO PRONTO

Itens pendentes:
🔴 Qualidade: slice-015 com security-review pendente
🔴 Testes: 2 testes E2E falhando
🟠 Documentacao: runbook de operacao nao existe
🟡 Operacao: rollback nao testado

Acao recomendada:
1. Rodar /security-review 015
2. Corrigir testes E2E
3. Criar runbook em docs/ops/runbook.md

Quer que eu ajude com algum desses itens?
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `project-state.json` não existe | Rodar `/checkpoint` para gerar o estado atual antes de validar readiness. |
| Suite de testes falha durante validação | Identificar testes falhando, listar ao PM, e sugerir `/fix` para cada slice afetado. |
| Slice sem todos os gates aprovados | Listar gates pendentes e sugerir a sequência de gates faltantes para cada slice. |
| Vulnerabilidades críticas em dependências | Bloquear release, listar CVEs encontrados, e recomendar atualização de dependências. |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

- Todos os épicos do MVP completos (todas as stories merged).
- `project-state.json` existe.
- Pelo menos 1 épico com todos os slices passando por todos os 5 gates.

## Handoff
- Tudo verde → PM aprova deploy
- Itens pendentes → listar acoes e ajudar a resolver

## Conformidade com protocolo v1.2.2

- **Agents invocados:** nenhum (orquestrador executa checklist agregado de todos os gates).
- **Gates produzidos:** gate meta-release; consolida outputs de todos os gates de slice + fase.
- **Output:** relatório PM-ready (pronto/não-pronto) + logs de validação automática.
- **Schema formal:** checklist declarado inline; validações referenciam schemas de cada gate.
- **Isolamento R3:** não aplicável (agrega artefatos já auditados).
- **Ordem no pipeline:** último gate antes de deploy; roda após todos os épicos MVP `merged`.
