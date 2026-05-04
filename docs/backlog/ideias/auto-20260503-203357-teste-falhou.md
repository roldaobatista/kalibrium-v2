---
tipo: problema-auto
estado: nao-refinado
detectado-em: 2026-05-03T20:33:57
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
php artisan test tests/Feature/Mobile/SyncServiceOrderTest.php --filter=\
```

## Trecho relevante da saída (interno — não mostrar cru ao Roldão)

```
                    ^ (Connection: pgsql, Host: 127.0.0.1, Port: 5433, Database: kalibrium_test, SQL: insert into \"service_orders\" (\"id\", \"tenant_id\", \"user_id\", \"client_name\", \"instrument_description\", \"status\", \"version\", \"updated_at\", \"created_at\") values (f36b9e07-c3a6-46f8-ad12-47a8171593c1, 8711, 8713, Cliente Antigo, Instrumento Antigo, received, 1, 2026-05-04 00:33:56, 2026-05-04 00:33:56))\u001b[39;22m\r\n\r\n  at \u001b[32mvendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php\u001b[39m:\u001b[32m587\u001b[39m\r\n    583▕             $this->bindValues($statement, $this->prepareBindings($bindings));\r\n    584▕ \r\n    585▕             $this->recordsHaveBeenModified();\r\n    586▕ \r\n  ➜ 587▕             return $statement->execute();\r\n    588▕         });\r\n    589▕     }\r\n    590▕ \r\n    591▕     /**\r\n\r\n  \u001b[33m1   \u001b[39m\u001b[39;1mvendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php\u001b[39;22m:\u001b[39;1m587\u001b[39;22m\r\n  \u001b[33m2   \u001b[39m\u001b[39;1mvendor\\laravel\\framework\\src\\Illuminate\\Database\\Connection.php\u001b[39;22m:\u001b[39;1m830\u001b[39;22m\r\n\r\n\r\n  \u001b[90mTests:\u001b[39m    \u001b[31;1m1 failed\u001b[39;22m\u001b[90m (0 assertions)\u001b[39m\r\n  \u001b[90mDuration:\u001b[39m \u001b[39m0.82s\u001b[39m","stderr":"","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01DWonTNue3qCksw5AD2ZKBi","duration_ms
","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01DWonTNue3qCksw5AD2ZKBi","duration_ms
```

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
