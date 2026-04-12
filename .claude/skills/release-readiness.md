---
description: Valida se o projeto esta pronto para release. Checa todos os epicos/stories completos, gates aprovados, documentacao atualizada, testes verdes, seguranca OK. Gera relatorio final para PM. Uso: /release-readiness.
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

## Handoff
- Tudo verde → PM aprova deploy
- Itens pendentes → listar acoes e ajudar a resolver
