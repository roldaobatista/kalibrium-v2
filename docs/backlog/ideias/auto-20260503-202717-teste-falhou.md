---
tipo: problema-auto
estado: nao-refinado
detectado-em: 2026-05-03T20:27:17
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
  \u001b[39;41;1m FAIL \u001b[39;49;22m\u001b[39m Tests\\Feature\\Mobile\\SyncServiceOrderTest\u001b[39m\r\n  \u001b[31;1m⨯\u001b[39;22m\u001b[90m \u001b[39m\u001b[90mpush create de OS cria ServiceOrder e SyncChange no tenant\u001b[39m\u001b[90m                                                  \u001b[39m \u001b[90m0.41s\u001b[39m  \r\n  \u001b[31m────────────────────────────────────────────────────────────────────────────────────────────────────────────────────\u001b[39m  \r\n  \u001b[41;1m FAILED \u001b[49;22m \u001b[1mTests\\Feature\\Mobile\\SyncServiceOrderTest\u001b[22m \u001b[90m>\u001b[39m push create de OS cria ServiceOrder e SyncChange no tenant       \r\n\u001b[39;1m  Failed asserting that an array has the key 0.\u001b[39;22m\r\n\r\n  at \u001b[32mtests\\Feature\\Mobile\\SyncServiceOrderTest.php\u001b[39m:\u001b[32m120\u001b[39m\r\n    116▕             'changes' => [$change],\r\n    117▕         ]);\r\n    118▕ \r\n    119▕     $response->assertOk();\r\n  ➜ 120▕     $response->assertJsonStructure(['applied' => [['local_id', 'server_id', 'ulid', 'version']]]);\r\n    121▕     $response->assertJsonCount(1, 'applied');\r\n    122▕     $response->assertJsonCount(0, 'rejected');\r\n    123▕     $response->assertJsonPath('applied.0.version', 1);\r\n    124▕\r\n\r\n\r\n  \u001b[90mTests:\u001b[39m    \u001b[31;1m1 failed\u001b[39;22m\u001b[90m (3 assertions)\u001b[39m\r\n  \u001b[90mDuration:\u001b[39m \u001b[39m0.62s\u001b[39m\r\n\r\n---EXIT---","stderr":"","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_018dzDy2tymrpgpHnnXDaCZ3","duration_ms
","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_018dzDy2tymrpgpHnnXDaCZ3","duration_ms
```

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
