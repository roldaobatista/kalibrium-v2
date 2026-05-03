---
name: posso-subir
description: Checklist completo antes de subir pro servidor — formatação, análise estática, testes (suite Feature inteira), lint frontend, build do frontend, isolamento multi-tenant, migrations seguras, documentação atualizada. Reporta em tabela pt-BR. Roldão usa pra decidir se autoriza o deploy.
allowed-tools: Bash, Read, Glob, Grep
---

# /posso-subir

Checklist completo de "Definition of Done" executado antes de subir pro servidor que o cliente usa. Mais rigoroso que `/conferir` (que verifica só o escopo recente). Aqui rodamos a suite de testes inteira da feature/regressão.

## Passos

### 1. Formatação completa

`vendor/bin/pint --test`
- ✓ se passou
- ✗ se falhou: rodar `vendor/bin/pint` pra corrigir e marcar "formatação estava torta, ajeitei"

### 2. Análise estática completa

`vendor/bin/phpstan analyse --memory-limit=2G`
- ✓ ou ✗ com tradução em pt-BR pelo efeito

### 3. Suite de testes (Feature)

`composer test --testsuite=Feature`
- ✓ se todos passaram
- ✗ traduzir falha pelo efeito visível, nunca stack trace

### 4. Suite de testes (Unit)

`composer test --testsuite=Unit`
- ✓ ou ✗

### 5. Lint frontend

`npm run lint`
- ✓ ou ✗

### 6. Build frontend

`npm run build`
- ✓ ou ✗ (se falhou, reportar "o pacote do frontend não conseguiu ser gerado")

### 7. Isolamento multi-tenant

Chamar subagente `revisor` apenas na lente de multi-tenant, passando todos os arquivos alterados desde o último deploy.
- ✓ revisor disse que está OK
- ⚠ revisor achou ATENÇÃO em algum lugar
- ✗ revisor achou BLOQUEIO

### 8. Migrations pendentes seguras

`php artisan migrate:status` pra listar pendentes.
Se houver migration pendente:
- Chamar subagente `revisor` na lente de migration.
- Confirmar que é segura pra rodar em produção (sem `dropColumn` sem backup, sem mudança de tipo destrutiva, etc.).

### 9. Documentação atualizada

Verificar:
- Se mudou contrato/comportamento que afeta cliente → existe atualização em `docs/product/` ou `docs/architecture/`?
- Se mudou política/permissão → `docs/security/` foi atualizado?
- Se foi decisão arquitetural relevante → ADR criado em `docs/adr/`?

Não bloquear se não houver — só sinalizar como AMARELO.

### 10. Relatório final em tabela pt-BR

```
🚀 Posso subir pro servidor?

| Verificação                        | Resultado          |
|------------------------------------|---------------------|
| Formatação do código (Pint)        | ✓ OK               |
| Análise estática (PHPStan)         | ✓ OK               |
| Testes de funcionalidade           | ✓ 142 passaram     |
| Testes unitários                   | ✓ 38 passaram      |
| Lint do frontend                   | ✓ OK               |
| Empacotamento do frontend          | ✓ OK               |
| Isolamento entre clientes          | ✓ Sem vazamento    |
| Mudanças na estrutura de dados     | ✓ 1 segura         |
| Documentação                       | ⚠ ADR-007 falta    |
|------------------------------------|---------------------|
| Veredito                           | VERDE — pode subir |
```

Veredito final:
- **VERDE** = todas verificações OK ou apenas avisos menores (documentação) → "pode subir pro servidor"
- **AMARELO** = avisos relevantes mas nada quebrado → "pode subir, mas vale resolver X depois"
- **VERMELHO** = pelo menos uma verificação falhou de forma bloqueadora → "não dá pra subir agora; preciso resolver: <lista>"

## Princípios

- **Tradução pelo efeito.** "tela X não carrega", nunca "Class not found in line 42".
- **Auto-correção quando determinístico.** Pint sujo? corrijo. Lint sujo? corrijo. Re-rodo a verificação.
- **Decisão fica com o Roldão.** Mesmo VERDE, eu não subo — só reporto. Subir é autorização explícita do Roldão.
- **Se Roldão autorizar, confirmar uma última vez.** "Vou subir <N> mudanças pra produção, tudo bem?" antes de executar.
