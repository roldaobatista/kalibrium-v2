---
tipo: problema-auto
estado: nao-refinado
detectado-em: 2026-05-03T21:39:50
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
vendor/bin/phpstan analyse tests/Feature/Mobile/SyncServiceOrderTest.php --memory-limit=256M 2>&1 | tail -20
```

## Trecho relevante da saída (interno — não mostrar cru ao Roldão)

```
         🪪  method.notFound                                                                                         \r\n  297    Call to an undefined method Pest\\PendingCalls\\TestCall::withToken().                                        \r\n         🪪  method.notFound                                                                                         \r\n  332    Call to an undefined method Pest\\PendingCalls\\TestCall::withToken().                                        \r\n         🪪  method.notFound                                                                                         \r\n  358    Call to an undefined method Pest\\PendingCalls\\TestCall::withToken().                                        \r\n         🪪  method.notFound                                                                                         \r\n  371    Call to an undefined method Pest\\PendingCalls\\TestCall::withToken().                                        \r\n         🪪  method.notFound                                                                                         \r\n  415    Call to an undefined method Pest\\PendingCalls\\TestCall::withToken().                                        \r\n         🪪  method.notFound                                                                                         \r\n  469    Call to an undefined method Pest\\PendingCalls\\TestCall::withToken().                                        \r\n         🪪  method.notFound                                                                                         \r\n ------ ------------------------------------------------------------------------------------------------------------ \r\n\r\n\r\n\u001b[37;41m                                                                                                                       \u001b[39;49m\r\n\u001b[37;41m [ERROR] Found 12 errors                                                                                               \u001b[39;49m\r\n\u001b[37;41m                                                                                                                       \u001b[39;49m","stderr":"","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01CsaGK5Ugy4cppADBZjCXpG","duration_ms
","interrupted":false,"isImage":false,"noOutputExpected":false},"tool_use_id":"toolu_01CsaGK5Ugy4cppADBZjCXpG","duration_ms
```

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
