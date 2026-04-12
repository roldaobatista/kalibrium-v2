# Slice 900 — smoke-test: validar pipeline end-to-end

**Status:** approved
**Data de criacao:** 2026-04-12
**Autor:** PM (smoke test)
**Depende de:** nenhum

---

## Contexto

Este slice existe exclusivamente para validar que o pipeline da fabrica de software funciona de ponta a ponta: spec → plan → testes red → implementacao → 5 gates → merge.

Nao tem valor de produto. Sera removido apos validacao.

## Jornada alvo

Desenvolvedor (agente) recebe uma funcao utilitaria trivial para implementar. O pipeline completo roda e todos os gates aprovam antes do merge.

## Acceptance Criteria

- **AC-001:** Existe um modulo `src/Utils/Greeting.php` que contem a funcao `greet(string $name): string` retornando `"Hello, {name}!"`.
- **AC-002:** Dado name vazio (`""`), `greet("")` retorna `"Hello, World!"`.
- **AC-003:** Existe pelo menos um teste unitario para cada AC em `tests/Unit/GreetingTest.php` usando Pest, e todos passam com exit 0.

## Fora de escopo

- Qualquer logica de negocio real do Kalibrium
- Framework Laravel (este slice usa PHP puro + Pest)
- Integracao com outros modulos
- Persistencia, API, UI

## Dependencias externas

- PHP 8.2+ (disponivel)
- Composer (disponivel)
- Pest (test runner — sera instalado via composer)
- ADR-0001 (stack choice: PHP/Laravel/Pest)

## Riscos conhecidos

- Pest pode nao instalar corretamente no Windows → mitigacao: usar PHPUnit como fallback
- Autoload pode precisar de configuracao → mitigacao: configurar PSR-4 no composer.json

## Notas do PM (humano)

Slice ficticio para smoke test. Apagar specs/900/ inteiro apos sucesso.
