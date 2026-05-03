---
name: conferir
description: Roda formatação (Pint), análise estática (PHPStan), e testes (Pest) no escopo recente. Reporta em pt-BR sem jargão — verde (tudo ok), amarelo (avisos), vermelho (algo quebrado). Não delega — executa direto. Use quando quiser saber "está tudo ok com o que mudou agora?".
allowed-tools: Bash, Read, Glob, Grep
---

# /conferir

Esta skill confere se o que mudou no código está saudável: formatação correta, sem erros de tipo, testes passando. Reporta em **pt-BR sem jargão**.

## Passos

### 1. Detectar escopo do que mudou

Usar `git status --short` e `git diff --name-only HEAD~1..HEAD 2>/dev/null` pra montar a lista de arquivos PHP alterados (em working tree e/ou último commit).

Se nada mudou, dizer "nada foi alterado desde a última conferida — está tudo igual" e parar.

### 2. Rodar formatação no escopo (Pint)

`vendor/bin/pint --test <arquivos-alterados>`

- Se passar → "formatação: ✓ OK"
- Se falhar → rodar `vendor/bin/pint <arquivos-alterados>` pra corrigir e dizer "formatação estava torta, ajeitei"

### 3. Rodar análise estática no escopo (PHPStan)

`vendor/bin/phpstan analyse --memory-limit=2G <arquivos-alterados>` (com fallback pra projeto inteiro se a lista for vazia)

- Se passar → "análise estática: ✓ OK"
- Se houver warnings novos → traduzir o erro pelo **efeito**, ex: "achou 1 lugar onde uma variável pode ser nula sem checagem na tela X"

### 4. Rodar testes Pest do escopo

Detectar testes relacionados aos arquivos alterados (ex: `app/Livewire/Foo.php` → `tests/Feature/Livewire/FooTest.php`). Se nenhum teste específico, rodar `composer test --testsuite=Feature` no nível mais baixo.

`composer test -- --filter=<padrão>`

- Verde → "testes: ✓ todos passaram"
- Vermelho → traduzir falha pelo **efeito visível**: "a tela X está com erro: o filtro Y não está mostrando o cliente certo". Nunca colar stack trace pro Roldão.

### 5. Renderizar relatório em pt-BR

```
🔍 Conferência

▸ Arquivos verificados: <N>
▸ Formatação: ✓ OK | ✓ Ajeitei <N> arquivos | ✗ falhou
▸ Análise do código: ✓ OK | ⚠ <N> avisos | ✗ erros novos
▸ Testes: ✓ todos passaram | ⚠ <N> com aviso | ✗ <N> falhando

▸ Resumo: VERDE — pode seguir
   ou: AMARELO — tem 2 avisos, vale olhar antes de subir pro servidor
   ou: VERMELHO — tem coisa quebrada, vou investigar e corrigir

▸ Detalhes (se amarelo ou vermelho):
   - <descrição do problema em pt-BR pelo efeito visível>
```

## Princípios

- **Trabalho real, não delegação.** Esta skill executa Pint/PHPStan/Pest direto — não chama subagente.
- **Se vermelho, eu corrijo se for determinístico.** Erro de formatação, import sobrando, type simples → corrijo e re-rodo. Falha ambígua de regra de negócio → reporto pra Roldão decidir.
- **Nunca stack trace.** Sempre tradução pelo efeito visível ("a tela X não carrega" / "o relatório Y mostra valor errado").
- **Escopo proporcional.** Se mudou 2 arquivos, conferir só esses 2 + testes relacionados — não rodar suite inteira no meio do trabalho.
