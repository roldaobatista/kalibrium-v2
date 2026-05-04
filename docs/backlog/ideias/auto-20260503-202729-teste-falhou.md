---
tipo: problema-auto
estado: nao-refinado
detectado-em: 2026-05-03T20:27:29
origem: hook captura-problema (PostToolUse Bash)
categoria: teste-falhou
---

# Problema detectado automaticamente

Sistema rodou um comando que aparenta ter falhado. Esta ideia foi aberta sozinha
pelo hook `captura-problema`. Próxima sessão, a maestra deve:

1. Ler o trecho abaixo
2. Traduzir o problema em pt-BR pelo **efeito visível** (não pelo stack trace)
3. Propor ao Roldão: vira história? é bug pra corrigir agora? é falso alarme?

## Comando que falhou

```
php artisan config:clear --quiet && php artisan test --no-coverage 2>&1 --filter=\
```

## Trecho relevante da saída (interno — não mostrar cru ao Roldão)

```
  \u001b[44;1m INFO \u001b[49;22m Test file \"--testdox\" not found.\n\r\n    122▕     $response->assertJsonCount(0, 'rejected');\n    123▕     $response->assertJsonPath('applied.0.version', 1);\n    124▕\n\n\n  \u001b[90mTests:\u001b[39m    \u001b[31;1m1 failed\u001b[39;22m\u001b[90m (3 assertions)\u001b[39m","stderr":"","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01TvRfXSxPKBgguDLg8fS8Xc","duration_ms
","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01TvRfXSxPKBgguDLg8fS8Xc","duration_ms
```

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
