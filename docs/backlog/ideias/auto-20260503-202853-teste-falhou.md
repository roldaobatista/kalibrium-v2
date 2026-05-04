---
tipo: problema-auto
estado: nao-refinado
detectado-em: 2026-05-03T20:28:53
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
php artisan config:clear --quiet && php artisan test tests/Feature/Mobile/SyncServiceOrderTest.php --filter=\
```

## Trecho relevante da saída (interno — não mostrar cru ao Roldão)

```
  \u001b[90mTests:\u001b[39m    \u001b[31;1m1 failed\u001b[39;22m\u001b[90m (3 assertions)\u001b[39m\r\n  \u001b[90mDuration:\u001b[39m \u001b[39m0.64s\u001b[39m","stderr":"","interrupted":false,"isImage":false,"returnCodeInterpretation":"No matches found","noOutputExpected":false},"tool_use_id":"toolu_01EonhKNnTfMETp9ecnNxDLQ","duration_ms
","interrupted":false,"isImage":false,"returnCodeInterpretation":"No matches found","noOutputExpected":false},"tool_use_id":"toolu_01EonhKNnTfMETp9ecnNxDLQ","duration_ms
```

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
