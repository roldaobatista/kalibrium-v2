# Plano tecnico do slice 900

**Gerado por:** orchestrator (smoke test — sem sub-agent architect)
**Status:** approved
**Spec de origem:** `specs/900/spec.md`

---

## Decisoes arquiteturais

### D1: PHP puro (sem Laravel) para smoke test
**Razao:** Smoke test valida o pipeline, nao a stack completa. PHP puro + Pest e o minimo necessario para exercitar testes + implementacao + gates.

**Reversibilidade:** facil — tudo sera deletado apos validacao.

### D2: Pest como runner de testes
**Razao:** Pest e o runner escolhido no ADR-0001 para o projeto real. Validar que funciona no ambiente.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `src/Utils/Greeting.php` | `tests/Unit/GreetingTest.php` |
| AC-002 | `src/Utils/Greeting.php` | `tests/Unit/GreetingTest.php` |
| AC-003 | `tests/Unit/GreetingTest.php` | (meta: o proprio arquivo) |

## Novos arquivos

- `composer.json` — config minima com autoload PSR-4 + pest como dev dependency
- `src/Utils/Greeting.php` — funcao greet()
- `tests/Unit/GreetingTest.php` — testes Pest para AC-001, AC-002
- `tests/Pest.php` — bootstrap do Pest

## Arquivos modificados

- Nenhum existente

## Schema / migrations

- Nenhum

## APIs / contratos

- Nenhum (funcao utilitaria pura)

## Riscos e mitigacoes

- Pest no Windows pode ter issues → fallback para PHPUnit direto

## Dependencias de outros slices

- Nenhuma

## Fora de escopo deste plano (confirmando spec)

- Laravel, Livewire, banco, API, UI
