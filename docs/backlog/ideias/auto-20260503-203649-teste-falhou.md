---
tipo: problema-auto
estado: nao-refinado
detectado-em: 2026-05-03T20:36:49
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
php artisan test --filter=\
```

## Trecho relevante da saída (interno — não mostrar cru ao Roldão)

```
  \u001b[90mTests:\u001b[39m    \u001b[32;1m1 passed\u001b[39;22m\u001b[90m (11 assertions)\u001b[39m\r\n  \u001b[90mDuration:\u001b[39m \u001b[39m1.71s\u001b[39m\r\n\r\n  \u001b[90mTests:\u001b[39m    \u001b[31;1m1 failed\u001b[39;22m\u001b[90m,\u001b[39m\u001b[39m \u001b[39m\u001b[32;1m2 passed\u001b[39;22m\u001b[90m (10 assertions)\u001b[39m","stderr":"","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01Ukxaqt89RWyG3HJeu3awWS","duration_ms
","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01Ukxaqt89RWyG3HJeu3awWS","duration_ms
```

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
