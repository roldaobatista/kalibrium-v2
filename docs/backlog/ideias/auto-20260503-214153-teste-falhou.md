---
tipo: problema-auto
estado: nao-refinado
detectado-em: 2026-05-03T21:41:53
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
  \u001b[39;41;1m FAIL \u001b[39;49;22m\u001b[39m Tests\\Feature\\Mobile\\SyncPhotoTest\u001b[39m\r\n  \u001b[31;1m⨯\u001b[39;22m\u001b[90m \u001b[39m\u001b[90mupload de foto acima de 8mb e rejeitado com 422\u001b[39m\u001b[90m                                                             \u001b[39m \u001b[90m0.55s\u001b[39m  \r\n  \u001b[31m────────────────────────────────────────────────────────────────────────────────────────────────────────────────────\u001b[39m  \r\n  \u001b[41;1m FAILED \u001b[49;22m \u001b[1mTests\\Feature\\Mobile\\SyncPhotoTest\u001b[22m \u001b[90m>\u001b[39m upload de foto acima de 8mb e rejeitado com 422                         \r\n\u001b[39;1m  Expected response status code [422] but received 201.\nFailed asserting that 201 is identical to 422.\u001b[39;22m\r\n\r\n  at \u001b[32mtests\\Feature\\Mobile\\SyncPhotoTest.php\u001b[39m:\u001b[32m173\u001b[39m\r\n    169▕             'client_uuid' => (string) Str::ulid(),\r\n    170▕             'photo' => photo_fake_image(9 * 1024), // 9 MB\r\n    171▕         ]);\r\n    172▕ \r\n  ➜ 173▕     $response->assertStatus(422);\r\n    174▕ });\r\n    175▕ \r\n    176▕ // ---------------------------------------------------------------------------\r\n    177▕ // 3. Upload com mime não aceito rejeitado (422)\r\n\r\n\r\n  \u001b[90mTests:\u001b[39m    \u001b[31;1m1 failed\u001b[39;22m\u001b[90m (1 assertions)\u001b[39m\r\n  \u001b[90mDuration:\u001b[39m \u001b[39m0.87s\u001b[39m","stderr":"","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01J1zwYLWTzmj75PRJhsFUA9","duration_ms
","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01J1zwYLWTzmj75PRJhsFUA9","duration_ms
```

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
