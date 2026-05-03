# Dívida técnica conhecida (pós-reset)

> **Data:** 2026-05-03
> **Por que existe este documento:** depois do reset do harness em abril/2026 e do descarte do frontend Livewire, a suíte de testes ficou com 47 testes vermelhos que **não são bugs novos** — são testes escritos pra cenários que mudaram (rotas removidas, Livewire descartado, comandos do harness antigo). Este documento separa os 47 por categoria pra cada um ter destino claro: ressuscitar, atualizar ou apagar.
>
> **Estado atual da suíte (snapshot 2026-05-03):**
>
> **Snapshot inicial (antes da limpeza):** 197 verdes, 47 vermelhos, 2 pulados.
>
> **Snapshot atual (após a passada de limpeza de 2026-05-03):**
>
> -   204 testes verdes
> -   18 testes vermelhos — **todos da categoria 1** (rotas removidas)
> -   2 testes pulados

---

## Resumo (pra Roldão)

**Estado de partida:** 47 testes vermelhos, 6 categorias.

**Estado atual:** 18 testes vermelhos, 1 única categoria — todos esperando endereços (rotas) que vão ser criados quando a história "técnico entra no app do celular" for executada.

**O que foi feito na passada de limpeza:**

-   Apagado: ExampleTest (dummy do Laravel), pasta `slice-006` inteira (testes Livewire), `TestScopeCommandTest`, `CiSbomTest`, `CiTriggerTest` (testavam configurações do harness antigo que mudaram de propósito).
-   Consertado: 10 testes de admin do tenant (helper agora configura o contexto do laboratório antes de rodar comandos administrativos).
-   Consertado: dados iniciais de cliente (seeder) agora podem rodar mais de uma vez sem dar erro de "já existe".

**Os 47 vermelhos originais eram:**

1. Esperam endereços (rotas) que foram removidos quando a tela velha foi descartada — vão voltar a verde quando a história "técnico entra no app do celular" for feita (porque ela cria os endereços novos).
2. Testam que telas antigas (Livewire) funcionam — essas telas foram descartadas de propósito, então os testes precisam ser apagados.
3. Testam comandos internos do harness antigo — também precisam ser revistos.

Decisão técnica minha: **não vou tentar corrigir tudo agora**. Vou priorizar pelo que dá mais valor:

-   A **categoria 1** (rotas) será resolvida automaticamente quando a história do app móvel for executada.
-   As **categorias 2-6** podem ser limpas em uma passada de manutenção (~1-2 horas), separada da história principal.

---

## Categoria 1 — Rotas removidas (HTTP 404) — 17 testes

**Causa:** o frontend Livewire foi descartado e as rotas web (`/clientes`, `/clientes/{id}/contatos`) foram removidas. Os controladores ainda existem em código (`ClienteController`, `ContatoController`), mas estão "desconectados" — nada aponta pra eles.

**Onde:**

-   `tests/slice-012/Cliente*Test.php` (criação, soft-delete, uniqueness, migration)
-   `tests/slice-013/Cliente*Test.php` (listing, RBAC, show, update)
-   `tests/slice-014/Contato*Test.php` (criação, deativação, RBAC, isolamento, validação)

**Destino:** vão voltar a verde quando a história `tecnico-entra-no-app-do-celular.md` for executada (porque o plano técnico vai criar `routes/api.php` e re-registrar os controladores). Antes disso, vão precisar ser ajustados pra apontar pra `/api/clientes` em vez de `/clientes` — mas essa mudança faz parte do plano da história. **Manter os arquivos como estão.**

## Categoria 2 — Tela inicial mudou (HTTP 302) — 1 teste

**Causa:** `ExampleTest::it returns a successful response` espera `GET /` retornar 200, mas agora `/` redireciona pra `/health`.

**Onde:** `tests/Feature/ExampleTest.php`

**Destino:** atualizar o teste pra esperar 302 (ou apagar — é um teste exemplo do Laravel sem valor real). **Trabalho: 5 minutos.**

## Categoria 3 — Telas Livewire — 6 testes

**Causa:** `slice006/ac006-*` testa que existe uma tela `/ping` em Livewire, que `php artisan livewire:list` lista o componente, que o build do frontend Livewire funciona. Tudo isso foi descartado pelo ADR-0015 (mudança pra app móvel).

**Onde:**

-   `tests/slice-006/ac-006-ping-routeTest.php`
-   `tests/slice-006/ac-006-livewire-commandTest.php`
-   `tests/slice-006/ac-006-build-manifestTest.php`
-   `tests/slice-006/ac-006-static-analysisTest.php`

**Destino:** apagar a pasta `tests/slice-006/` inteira, remover entrada `Slice006` do `phpunit.xml`. Coerente com a remoção do `slice-015`. **Trabalho: 5 minutos.**

## Categoria 4 — Comandos do harness antigo — 8 testes

**Causa:** `TestScopeCommandTest`, `CiSbomTest`, `CiTriggerTest` testam configurações de CI e scripts de execução do harness antigo, que mudaram no reset.

**Onde:**

-   `tests/Feature/TestScopeCommandTest.php` (4 testes)
-   `tests/slice-003/CiSbomTest.php` (1 teste)
-   `tests/slice-003/CiTriggerTest.php` (3 testes)

**Destino:** revisar e atualizar pra refletir o `.github/workflows/ci.yml` e os scripts atuais (que são diferentes dos antigos). Ou apagar se a função coberta não existe mais. **Trabalho: 30-60 minutos.**

## Categoria 5 — AuthorizationException em comandos de admin — 10 testes

**Causa:** `slice009` testa fluxos onde gerente desativa usuário, altera papel, solicita upgrade de plano. Todos retornam `AuthorizationException`. Provavelmente uma policy esperando autenticação que mudou.

**Onde:**

-   `tests/slice-009/PlanUpgradeRequestTest.php` (4 testes)
-   `tests/slice-009/UsersDeactivateTest.php` (2 testes)
-   `tests/slice-009/UsersRoleTest.php` (4 testes)

**Destino:** investigar — provavelmente é uma policy ou um helper de teste que precisa ser ajustado. Comportamento é importante (admin do tenant precisa funcionar), os testes valem a pena consertar. **Trabalho: 1-2 horas.**

## Categoria 6 — Outros — 5 testes

-   `tests/slice-007/AuthConfigTest.php::AC-021 — DatabaseSeeder não cria conta` — `UniqueConstraintViolationException`. Provavelmente o seeder está rodando duas vezes em sequência. **Trabalho: 15 min.**
-   `tests/slice-010/ConsentRecordTest.php::AC-SEC-001 — HTML/JS sanitization` — alguma regra de sanitização mudou. **Trabalho: 15-30 min.**
-   3 falhas restantes em `slice-012/ClienteUniquenessTest` e similares — derivadas das categorias 1 e 5, vão resolver junto.

---

## Plano de ação proposto

**Antes da história principal:** uma passada curta de limpeza (estimativa 2-3 horas) que cobre as categorias 2, 3, 6 — apaga o que é claramente obsoleto (Livewire, ExampleTest), conserta o que tem solução simples (seeder duplicado, sanitização). Isso baixa de 47 vermelhos pra ~25.

**Durante a história "técnico entra no app":** categoria 1 (17 testes) volta a verde naturalmente quando `routes/api.php` for criado. Categoria 5 (10 testes) provavelmente também — o ajuste de auth pode ser causa comum. Isso baixa pra ~0.

**Decisão pendente do Roldão:** quer fazer a passada de limpeza agora antes da história, ou prefere ir direto pra história e limpar depois?
