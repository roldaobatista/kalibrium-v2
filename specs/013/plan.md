# Plano tecnico do slice 013 — E03-S01b: Listagem + filtro + paginacao + RBAC de cliente

**Gerado por:** architect sub-agent
**Status:** draft
**Spec de origem:** `specs/013/spec.md`

---

## Resumo

Este slice expoe os endpoints restantes do CRUD de clientes: `GET /clientes` (listagem paginada + filtros), `GET /clientes/{id}` (detalhe), `PUT /clientes/{id}` (edicao) e completa o RBAC diferenciando leitura (tecnico + atendente) de escrita (atendente). O Model `Cliente`, a migration, `POST /clientes` e `DELETE /clientes/{id}` ja existem do slice 012.

---

## Decisoes arquiteturais

### D1: Paginacao via `->withQueryString()` nativo do Laravel, sem pacote externo

**Opcoes consideradas:**
- **Opcao A: `Cliente::query()->paginate($perPage)->withQueryString()`** — pros: nativo do Laravel, preserva todos os query params na URL das paginas; sem dependencias adicionais; contras: nenhum relevante.
- **Opcao B: cursor-based pagination (`cursorPaginate`)** — pros: performance melhor em tabelas muito grandes; contras: nao atende o spec que exige `meta.total` + `meta.last_page` (estrutura de offset pagination); nao ha requisito de escala que justifique.
- **Opcao C: pacote externo de paginacao** — pros: nenhum relevante; contras: viola ADR-0001 (preferencia por solucoes nativas).

**Escolhida:** Opcao A.

**Razao:** spec exige `meta.total`, `meta.last_page`, `meta.from`, `meta.to` — estrutura de offset pagination. `withQueryString()` preserva `search`, `ativo`, `sort`, `per_page` na URL de navegacao, mitigando o risco apontado no spec.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

### D2: Filtro por search via `ILIKE` no PostgreSQL, sem full-text search

**Opcoes consideradas:**
- **Opcao A: `ILIKE '%substring%'` nos campos `razao_social`, `nome_fantasia` e `documento`** — pros: nativo PostgreSQL, case-insensitive, sem dependencias, cobre o AC-008 literalmente; contras: performance com tabelas muito grandes (mitigado pelo scope de tenant que reduz o dataset).
- **Opcao B: full-text search via `to_tsvector`** — pros: performance melhor em escala; contras: configuracao adicional, complexidade fora de escopo para MVP, spec nao requer.
- **Opcao C: Laravel Scout + driver de busca** — pros: abstracao; contras: dependencia externa, viola ADR-0001.

**Escolhida:** Opcao A.

**Razao:** AC-008 pede busca por substring case-insensitive. O scope de tenant (`WHERE tenant_id = ?`) ja reduz o dataset. Indices compostos `(tenant_id, razao_social)` ja existem na tabela. `ILIKE` e suficiente para MVP.

**Reversibilidade:** facil (pode ser trocado por full-text em slice futuro sem alterar contrato da API).

**ADR:** nao requer ADR novo.

---

### D3: Validacao de `sort` no `ListClientesRequest` via regra `in:`, retornando 422

**Opcoes consideradas:**
- **Opcao A: criar `ListClientesRequest` com regra `sort => in:razao_social,-razao_social,created_at,-created_at`** — pros: 422 automatico pelo Laravel com mensagem clara; consistente com pattern dos outros FormRequests do projeto; contras: nenhum relevante.
- **Opcao B: validar inline no Controller com `$request->validate([...])`** — pros: menos arquivos; contras: logica de validacao no controller viola SRP; pattern estabelecido usa FormRequests separados.
- **Opcao C: ignorar silenciosamente sort invalido e usar default** — pros: tolerante a erros; contras: viola AC-012c explicitamente.

**Escolhida:** Opcao A.

**Razao:** AC-012c exige HTTP 422 para sort invalido. Pattern do projeto centraliza validacao em FormRequests. Consistente com `StoreClienteRequest` do slice 012.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

### D4: `UpdateClienteRequest` separado de `StoreClienteRequest`, com validacao de payload nao-vazio

**Opcoes consideradas:**
- **Opcao A: `UpdateClienteRequest` separado com todos os campos `sometimes`, mais validacao de payload nao-vazio via `withValidator`** — pros: separacao de responsabilidade; PUT aceita qualquer subconjunto dos campos editaveis; AC-009b atendido explicitamente; contras: arquivo adicional.
- **Opcao B: reutilizar `StoreClienteRequest` com flag de modo** — pros: menos arquivos; contras: viola SRP, acoplamento entre dois contratos distintos (POST obriga campos, PUT e parcial).
- **Opcao C: usar semantica `PATCH` com todos os campos opcionais** — pros: semantica REST mais correta para atualizacao parcial; contras: spec e API contract definem `PUT`; alterar verbo e fora de escopo.

**Escolhida:** Opcao A.

**Razao:** spec e API contract definem `PUT /clientes/{id}` com todos os campos opcionais mas pelo menos um obrigatorio (AC-009b). `UpdateClienteRequest` separado mantem os dois contratos independentes.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

### D5: Granularidade do RBAC — separar permissoes de leitura e escrita na Policy e no TenantRole

**Contexto:** `TenantRole::canManageClientes()` atualmente retorna `true` para gerente, tecnico e administrativo. O spec (AC-011, AC-011b, AC-011c, AC-012a, AC-012b) define que tecnico pode apenas ler (index, show) e nao pode criar, editar ou desativar. A Policy atual do slice 012 usa `canManageClientes` para `create` e `delete` — o comportamento observado e correto para 403 no tecnico porque o Gate nega, mas o nome do metodo e enganoso e a logica precisa ser tornada explicita para os novos metodos `update`, `viewAny`, `view`.

**Opcoes consideradas:**
- **Opcao A: adicionar `TenantRole::canReadClientes()` (gerente, tecnico, administrativo, visualizador) e `TenantRole::canWriteClientes()` (gerente, administrativo — exclui tecnico); Policy usa os dois metodos distintos** — pros: semantica clara, facilmente extensivel, testa roles diretamente no TenantRole; contras: refatora TenantRole existente (mudanca cirurgica e minima).
- **Opcao B: manter `canManageClientes` e adicionar logica de exclusao de `tecnico` inline na Policy** — pros: nao altera TenantRole; contras: logica espalhada, viola DRY, TenantRole fica inconsistente entre o que diz e o que faz.
- **Opcao C: mapa de permissoes por role via config** — pros: flexivel; contras: over-engineering para MVP, nao previsto em nenhum ADR.

**Escolhida:** Opcao A.

**Razao:** o spec e explicito: tecnico le, nao escreve. Adicionar `canReadClientes()` e `canWriteClientes()` ao TenantRole e a abordagem mais limpa e alinhada com o pattern existente (`canManageUsers`, `canViewPlan`). `canManageClientes()` permanece sem alteracao para nao quebrar testes do slice 012.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo (extensao do pattern ja estabelecido, nao nova direcao arquitetural).

---

### D6: `contatos_count` e `instrumentos_count` no `show()` via `whenCounted` com fallback 0

**Opcoes consideradas:**
- **Opcao A: `ClienteResource` usa `$this->whenCounted('contatos', fn($c) => $c, fn() => 0)`; Controller `show()` tenta `loadCount` mas relacoes ainda nao existem (E03-S02a)** — pros: atende AC-012b hoje (retorna 0), fica pronto para quando as relacoes forem criadas; contras: `loadCount` em relacao inexistente pode gerar excecao.
- **Opcao B: hardcode `contatos_count: 0` no Resource enquanto relacoes nao existem** — pros: simples; contras: fragil, precisara ser alterado quando E03-S02a for implementado; mascara a intencao arquitetural.
- **Opcao C: omitir os campos ate as relacoes existirem** — pros: sem campo falso; contras: viola AC-012b que exige os campos com valor 0.

**Escolhida:** Opcao A simplificada: Controller `show()` NAO chama `loadCount` enquanto as relacoes nao existirem (E03-S02a). O `whenCounted` com fallback 0 no Resource garante que os campos retornam 0 sem precisar de `loadCount`. Quando E03-S02a criar as relacoes, basta adicionar `loadCount` sem alterar o Resource.

**Razao:** AC-012b exige `contatos_count` e `instrumentos_count` com valor 0 quando nao ha relacionamentos. A abordagem com `whenCounted` e idiomatica Laravel e nao requer alteracao futura no Resource quando E03-S02a criar as relacoes.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

## Sequencia de implementacao (test-first por P2)

### T1: `ListClientesRequest` — validacao dos query params do index

**Descricao:** Criar FormRequest com regras: `search` (string, max:100), `tipo_pessoa` (in:PJ,PF), `ativo` (boolean), `page` (integer, min:1, default:1), `per_page` (integer, min:1, max:100, default:20), `sort` (in:razao_social,-razao_social,created_at,-created_at, default:razao_social). Mensagem de erro customizada para `sort` invalido listando os valores aceitos.

**Arquivos:**
- Criar: `app/Http/Requests/ListClientesRequest.php`

**ACs cobertos:** AC-012c

**Criterio de done:** FormRequest rejeita sort invalido com 422 e mensagem listando valores aceitos.

---

### T2: `UpdateClienteRequest` — validacao parcial de campos editaveis

**Descricao:** Criar FormRequest com todos os campos do PUT como `sometimes`: `razao_social` (string, max:255), `nome_fantasia` (nullable, string, max:255), `logradouro` (string, max:255), `numero` (string, max:20), `complemento` (nullable, string, max:100), `bairro` (string, max:100), `cidade` (string, max:100), `uf` (string, size:2, estados validos), `cep` (string, regex), `regime_tributario` (in: labels e slugs), `limite_credito` (numeric, min:0, max:9999999.99). Metodo `withValidator` adiciona erro se nenhum dos campos editaveis estiver presente no payload. Metodo `validatedForStorage()` normaliza `regime_tributario` (label → slug) e `cep` (remove hifen). Campos `cnpj_cpf` e `tipo_pessoa` nao presentes nas regras (imutaveis).

**Arquivos:**
- Criar: `app/Http/Requests/UpdateClienteRequest.php`

**ACs cobertos:** AC-009, AC-009b

**Criterio de done:** PUT com payload vazio `{}` retorna 422; PUT com subset valido de campos retorna dados validados + slug de regime_tributario.

---

### T3: `TenantRole` — adicionar `canReadClientes()` e `canWriteClientes()`

**Descricao:** Adicionar dois metodos estaticos ao `TenantRole`:
- `canReadClientes(string $role): bool` — retorna `true` para gerente, tecnico, administrativo, visualizador.
- `canWriteClientes(string $role): bool` — retorna `true` para gerente e administrativo; exclui tecnico e visualizador.

**Arquivos:**
- Modificar: `app/Support/Tenancy/TenantRole.php`

**ACs cobertos:** AC-011, AC-011b, AC-011c, AC-012a, AC-012b (via Policy)

**Criterio de done:** Testes unitarios verificam todas as combinacoes de role vs metodo.

---

### T4: `ClientePolicy` — expandir com `viewAny`, `view`, `update`; corrigir `create` e `delete`

**Descricao:** Expandir a Policy com:
- `viewAny(User $user, TenantUser $tenantUser): bool` — usa `canReadClientes()`.
- `view(User $user, TenantUser $tenantUser): bool` — usa `canReadClientes()`.
- `update(User $user, TenantUser $tenantUser): bool` — usa `canWriteClientes()`.

Corrigir `create()` e `delete()` para usar `canWriteClientes()` em vez de `canManageClientes()`, removendo `tecnico` das permissoes de escrita (antes `canManageClientes` incluia tecnico; agora `canWriteClientes` exclui).

Nota: cada metodo da Policy DEVE preservar as 3 verificacoes obrigatorias de `isActiveOperational`: (1) `$tenantUser->user_id === $user->id`, (2) `$tenantUser->status === 'active'`, (3) check de role via `canReadClientes`/`canWriteClientes`. O metodo `isActiveOperational` permanece como helper privado; os novos metodos (`viewAny`, `view`, `update`) o chamam e adicionam o check de role granular.

**Arquivos:**
- Modificar: `app/Policies/ClientePolicy.php`

**ACs cobertos:** AC-011, AC-011b, AC-011c, AC-012a, AC-012b

**Criterio de done:** Policy retorna `false` para `tecnico` em `create`, `update`, `delete`; retorna `true` para `tecnico` em `viewAny`, `view`.

---

### T5: `ClienteResource` — adicionar `contatos_count` e `instrumentos_count` via `whenCounted`

**Descricao:** Adicionar ao `toArray()` condicional via `$this->when($this->showDetail ?? false, ...)`:
```php
$this->when($this->showDetail ?? false, fn () => [
    'contatos_count'     => $this->whenCounted('contatos', fn ($c) => $c, fn () => 0),
    'instrumentos_count' => $this->whenCounted('instrumentos', fn ($c) => $c, fn () => 0),
]),
```
O Controller `show()` seta `$resource->showDetail = true` antes de retornar. O `index()` nao seta, portanto esses campos nao aparecem na listagem — evitando divergencia de shape entre listagem e detalhe.

**Arquivos:**
- Modificar: `app/Http/Resources/ClienteResource.php`

**ACs cobertos:** AC-012b

**Criterio de done:** `show()` retorna `contatos_count: 0` e `instrumentos_count: 0` quando nao ha relacoes carregadas.

---

### T6: `ClienteController` — implementar `index()`, `show()`, `update()`

**Descricao:**

`index()`:
- Recebe `ListClientesRequest`.
- `Gate::authorize('clientes.viewAny', $tenantUser)`.
- Constroi query com filtros opcionais:
  - `search`: `ILIKE '%...%'` em `razao_social`, `nome_fantasia`, e `documento` (apos normalizar search para so digitos ao buscar em `documento`).
  - `tipo_pessoa`: `where('tipo_pessoa', $tipoPessoa)`.
  - `ativo`: `where('ativo', $ativo)` — default `true`.
- Aplica ordenacao via mapa: `razao_social` → `ORDER BY razao_social ASC`, `-razao_social` → `DESC`, `created_at` → `ASC`, `-created_at` → `DESC`. Default: `razao_social ASC`.
- Pagina com `->paginate($perPage)->withQueryString()`.
- Retorna `ClienteResource::collection($paginator)` — Laravel serializa automaticamente `meta` e `links`.

`show()`:
- `Gate::authorize('clientes.view', $tenantUser)`.
- `Cliente::findOrFail($id)` — global scope de tenant garante 404 para outro tenant (AC-013).
- NAO chamar `loadCount` enquanto as relacoes `contatos`/`instrumentos` nao existirem (E03-S02a). O `ClienteResource` usa `whenCounted` com fallback 0, que retorna 0 quando nenhum count foi carregado.
- Retorna `new ClienteResource($cliente)` com flag `$this->showDetail = true` para indicar que e detalhe.

`update()`:
- Recebe `UpdateClienteRequest`.
- `Gate::authorize('clientes.update', $tenantUser)`.
- `Cliente::findOrFail($id)` — 404 automatico via global scope.
- `$cliente->fill($request->validatedForStorage())`.
- `$cliente->updated_by = $tenantUser->id`.
- `$cliente->save()`.
- Retorna `new ClienteResource($cliente)` com status 200.

**Arquivos:**
- Modificar: `app/Http/Controllers/ClienteController.php`

**ACs cobertos:** AC-007, AC-008, AC-009, AC-009b (via Request), AC-010a, AC-011b (via Policy), AC-012a, AC-012b, AC-012c (via Request), AC-013

**Criterio de done:** Todos os endpoints respondem com status e payloads corretos conforme API contract.

---

### T7: `routes/web.php` — adicionar rotas index, show, update ao grupo existente

**Descricao:** Expandir o grupo de rotas do slice 012 (middleware `auth` + `EnsureTwoFactorChallengeCompleted` + `SetCurrentTenantContext` + `EnsureReadOnlyTenantMode`) com:
- `Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index')`
- `Route::get('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show')`
- `Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update')`

**Arquivos:**
- Modificar: `routes/web.php`

**ACs cobertos:** AC-007, AC-009, AC-012a, AC-012b

**Criterio de done:** `php artisan route:list --name=clientes` mostra as 5 rotas (store, destroy, index, show, update).

---

### T8: Testes de integracao — todos os ACs do slice 013

**Descricao:** Criar suite de testes Pest cobrindo todos os ACs em contexto isolado.

**Arquivos:**
- Criar: `tests/slice-013/ClienteListingTest.php` — AC-007, AC-008, AC-010a, AC-012a, AC-012c
- Criar: `tests/slice-013/ClienteShowTest.php` — AC-012b, AC-013
- Criar: `tests/slice-013/ClienteUpdateTest.php` — AC-009, AC-009b
- Criar: `tests/slice-013/ClienteRbacTest.php` — AC-011, AC-011b, AC-011c, AC-012a, AC-012b, AC-010b (regressão: desativar cliente já inativo com atendente retorna 409 após mudança de Policy)
- Modificar: `tests/Pest.php` — registrar diretorio `tests/slice-013`

**ACs cobertos:** AC-007 a AC-013 (todos, incluindo sub-ACs)

**Criterio de done:** `php artisan test tests/slice-013` com exit 0, todos os testes passam.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-007 | `ClienteController::index()`, `ListClientesRequest`, `ClienteResource`, `routes/web.php` | `tests/slice-013/ClienteListingTest.php` |
| AC-008 | `ClienteController::index()` (ILIKE em razao_social, nome_fantasia, documento) | `tests/slice-013/ClienteListingTest.php` |
| AC-009 | `ClienteController::update()`, `UpdateClienteRequest`, `ClienteResource` | `tests/slice-013/ClienteUpdateTest.php` |
| AC-009b | `UpdateClienteRequest` (withValidator payload nao-vazio) | `tests/slice-013/ClienteUpdateTest.php` |
| AC-010 | `ClienteController::destroy()` — ja implementado no slice 012, sem alteracao | `tests/slice-012/ClienteSoftDeleteTest.php` |
| AC-010a | `ClienteController::index()` (filtro `?ativo=false`) | `tests/slice-013/ClienteListingTest.php` |
| AC-010b | `ClienteController::destroy()` — ja implementado no slice 012; teste de regressao no slice 013 valida que a mudanca de Policy (canWriteClientes) nao quebra o 409 | `tests/slice-013/ClienteRbacTest.php` (regressao) |
| AC-011 | `ClientePolicy::create()`, `TenantRole::canWriteClientes()` | `tests/slice-013/ClienteRbacTest.php` |
| AC-011b | `ClientePolicy::update()`, `TenantRole::canWriteClientes()` | `tests/slice-013/ClienteRbacTest.php` |
| AC-011c | `ClientePolicy::delete()`, `TenantRole::canWriteClientes()` | `tests/slice-013/ClienteRbacTest.php` |
| AC-012a | `ClientePolicy::viewAny()`, `TenantRole::canReadClientes()` | `tests/slice-013/ClienteRbacTest.php` |
| AC-012b | `ClienteController::show()`, `ClienteResource` (contatos_count, instrumentos_count) | `tests/slice-013/ClienteShowTest.php` |
| AC-012c | `ListClientesRequest` (sort invalido → 422) | `tests/slice-013/ClienteListingTest.php` |
| AC-013 | `ClienteController::show()` com global scope de tenant → 404 | `tests/slice-013/ClienteShowTest.php` |

---

## Novos arquivos

- `app/Http/Requests/ListClientesRequest.php` — validacao dos query params de GET /clientes (search, tipo_pessoa, ativo, page, per_page, sort com in: validation)
- `app/Http/Requests/UpdateClienteRequest.php` — validacao parcial do PUT /clientes/{id}, validacao de payload nao-vazio, mapeamento regime_tributario label→slug
- `tests/slice-013/ClienteListingTest.php` — testes AC-007, AC-008, AC-010a, AC-012a, AC-012c
- `tests/slice-013/ClienteShowTest.php` — testes AC-012b, AC-013
- `tests/slice-013/ClienteUpdateTest.php` — testes AC-009, AC-009b
- `tests/slice-013/ClienteRbacTest.php` — testes AC-011, AC-011b, AC-011c, AC-012a, AC-012b

---

## Arquivos modificados

- `app/Http/Controllers/ClienteController.php` — adicionar `index()`, `show()`, `update()`; `store()` e `destroy()` existentes nao sao alterados
- `app/Policies/ClientePolicy.php` — adicionar `viewAny()`, `view()`, `update()`; corrigir `create()` e `delete()` para usar `canWriteClientes()`
- `app/Http/Resources/ClienteResource.php` — adicionar `contatos_count` e `instrumentos_count` via `whenCounted`
- `app/Support/Tenancy/TenantRole.php` — adicionar `canReadClientes()` e `canWriteClientes()`
- `routes/web.php` — adicionar rotas GET /clientes, GET /clientes/{id}, PUT /clientes/{id} ao grupo existente do slice 012
- `tests/Pest.php` — registrar diretorio `tests/slice-013`

---

## APIs / contratos

### GET /clientes

**Query params:** `search` (string, max:100), `tipo_pessoa` (PJ|PF), `ativo` (boolean, default:true), `page` (int, default:1), `per_page` (int, 1-100, default:20), `sort` (razao_social|-razao_social|created_at|-created_at, default:razao_social)

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "tipo_pessoa": "PJ",
      "cnpj_cpf": "11.222.333/0001-81",
      "razao_social": "Calibra Laboratorios Ltda",
      "nome_fantasia": "CalibLab",
      "logradouro": "Rua das Industrias",
      "numero": "100",
      "complemento": "Galpao 3",
      "bairro": "Distrito Industrial",
      "cidade": "Sao Paulo",
      "uf": "SP",
      "cep": "01310-100",
      "regime_tributario": "Lucro Presumido",
      "limite_credito": "5000.00",
      "ativo": true,
      "created_at": "2026-04-16T10:00:00-03:00",
      "updated_at": "2026-04-16T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 25,
    "last_page": 2,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/clientes?page=1",
    "last": "/clientes?page=2",
    "prev": null,
    "next": "/clientes?page=2"
  }
}
```

### GET /clientes/{id}

**Response 200:**
```json
{
  "data": {
    "id": 1,
    "tipo_pessoa": "PJ",
    "cnpj_cpf": "11.222.333/0001-81",
    "razao_social": "Calibra Laboratorios Ltda",
    "nome_fantasia": "CalibLab",
    "logradouro": "Rua das Industrias",
    "numero": "100",
    "complemento": "Galpao 3",
    "bairro": "Distrito Industrial",
    "cidade": "Sao Paulo",
    "uf": "SP",
    "cep": "01310-100",
    "regime_tributario": "Lucro Presumido",
    "limite_credito": "5000.00",
    "ativo": true,
    "contatos_count": 0,
    "instrumentos_count": 0,
    "created_at": "2026-04-16T10:00:00-03:00",
    "updated_at": "2026-04-16T10:00:00-03:00"
  }
}
```

### PUT /clientes/{id}

**Request:** qualquer subconjunto de `razao_social`, `nome_fantasia`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `cep`, `regime_tributario`, `limite_credito` (pelo menos 1 campo obrigatorio).

**Response 200:** mesmo schema de `GET /clientes/{id}`.

**Response 422 (payload vazio):**
```json
{
  "message": "Ao menos um campo deve ser informado.",
  "errors": {
    "fields": ["Ao menos um campo editavel deve ser enviado."]
  }
}
```

---

## Riscos e mitigacoes

- **`TenantRole::canManageClientes()` inclui `tecnico`, que nao deve ter permissao de escrita** → mitigacao: T3 adiciona `canWriteClientes()` (exclui tecnico) e T4 atualiza Policy para usar o metodo correto; `canManageClientes()` permanece sem alteracao para nao quebrar testes existentes do slice 012.
- **Paginacao com filtros simultaneos perde parametros na URL** → mitigacao: `->withQueryString()` em todas as chamadas a `paginate()`; teste AC-007 valida estrutura de `links`.
- **ILIKE em `documento` vs `cnpj_cpf` formatado: coluna no banco e `documento` (so digitos), filtro pode chegar com mascara** → mitigacao: no Controller, normalizar o valor do `search` para apenas digitos antes de aplicar o ILIKE no campo `documento`; aplicar ILIKE direto em `razao_social` e `nome_fantasia` sem normalizacao.
- **`contatos_count`/`instrumentos_count` no shape de resposta enquanto relacoes nao existem** → mitigacao: NAO chamar `loadCount` ate E03-S02a; `whenCounted` com fallback 0 garante o valor correto sem dependencia de relacao; campos aparecem apenas no `show()` via flag `showDetail`.
- **Colisao de nomes de rotas com slice 012** → mitigacao: rotas novas usam nomes `clientes.index`, `clientes.show`, `clientes.update`; slice 012 usa `clientes.store` e `clientes.destroy`; sem conflito.
- **`per_page` acima de 100 pode causar sobrecarga de memoria** → mitigacao: `ListClientesRequest` valida `per_page` com `max:100`; Laravel retorna 422 automatico.

---

## Dependencias de outros slices

- `slice-012` (E03-S01a) — Model `Cliente` com `ScopesToCurrentTenant` + `SoftDeletes`, migration da tabela `clientes`, `ClienteController` (store/destroy), `ClientePolicy` (create/delete), `ClienteResource`, `StoreClienteRequest`, `ClienteFactory`. Este slice expande tudo isso sem reescrever.
- `slice-008` (E02-S04) — `TenantRole`, `ScopesToCurrentTenant` trait, `SetCurrentTenantContext` middleware.
- `slice-007` (E02-S03) — autenticacao, `TenantUser` model.

---

## Fora de escopo deste plano (confirmando spec)

- Validacao algoritmica de CNPJ/CPF (slice 012)
- Contatos do cliente (E03-S02a)
- Instrumentos do cliente (E03-S03)
- Importacao em massa via CSV (pos-MVP)
- Consulta a API da Receita Federal (pos-MVP)
- Historico de alteracoes / audit log (E03-S07a)
- UI/frontend — este slice e backend-only
- `PATCH /clientes/{id}` — API contract define `PUT`; semantica PATCH nao esta no escopo
