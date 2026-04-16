# Plano tecnico do slice 014 — E03-S02a: CRUD de contato + RBAC + isolamento

**Gerado por:** architect sub-agent
**Status:** draft
**Spec de origem:** `specs/014/spec.md`

---

## Resumo

Este slice cria o model `Contato`, vinculado ao model `Cliente` existente (slices 012/013), com CRUD completo, RBAC por role e isolamento de tenant. O contato herda o tenant do cliente via `cliente_id`, com `tenant_id` redundante na linha para alinhamento com o ERD E03 e pattern de RLS. Segue o pattern exato do `ClienteController`, `ClientePolicy`, `ClienteResource` e `SoftDeletes` dos slices anteriores. Backend-only: sem views Blade.

---

## Decisoes arquiteturais

### D1: tenant_id redundante na tabela contatos (dado no ERD, confirmado aqui)

**Opcoes consideradas:**
- **Opcao A: armazenar `tenant_id` direto na tabela `contatos` (FK → `tenants.id`)** — pros: isolamento garantido sem JOIN em toda query; alinhado com ERD E03 e pattern RLS do projeto; policy valida `contato.tenant_id` diretamente sem carregar o cliente pai; consistente com `clientes`, `instrumentos` e demais tabelas do E03; contras: requer preenchimento duplo no store (tenant vem do contexto + deve bater com o do cliente pai).
- **Opcao B: derivar tenant apenas via `cliente_id → clientes.tenant_id` (sem coluna tenant_id em contatos)** — pros: sem redundancia; contras: viola ERD E03; toda query de isolamento requer JOIN com `clientes`; `ScopesToCurrentTenant` nao funcionaria sem coluna `tenant_id` no model; inconsistente com o pattern estabelecido.

**Escolhida:** Opcao A.

**Razao:** ERD E03 define `tenant_id` em `contatos` explicitamente. O trait `ScopesToCurrentTenant` depende da coluna `tenant_id` no model para aplicar o global scope. Consistente com todos os demais models do E03. A relacao `cliente_id → clientes.tenant_id` serve como verificacao de consistencia mas nao como substituto do escopo primario.

**Reversibilidade:** dificil (altera schema e policy).

**ADR:** nao requer ADR novo (segue decisao de tenancy ja estabelecida nos slices anteriores).

---

### D2: isolamento via ScopesToCurrentTenant + validacao explicita no destroy() (sem Route Model Binding aninhado)

**Opcoes consideradas:**
- **Opcao A: `Contato` usa a trait `ScopesToCurrentTenant`; Policy valida `contato.tenant_id === tenant.id`; Controller usa `Contato::findOrFail($id)` — scope garante 404 cross-tenant automaticamente; `destroy()` usa `withTrashed()->where('tenant_id', $tenant->id)` explicitamente (identico ao ClienteController)** — pros: pattern identico ao `Cliente`; 404 automatico; sem JOIN adicional; contras: nenhum relevante.
- **Opcao B: Route Model Binding com scope via cliente: `/clientes/{cliente}/contatos/{contato}` para show/update/delete** — pros: URL semanticamente correta para recursos aninhados; contras: spec define rotas planas para show/update/delete (`/contatos/{id}`) — alterar seria fora de escopo; estrutura mista complicaria os testes.
- **Opcao C: validacao manual de tenant no controller em cada metodo** — pros: explicito; contras: viola DRY; pattern do projeto usa global scope para isso; risco de esquecer em um metodo.

**Escolhida:** Opcao A.

**Razao:** spec define as rotas exatamente como `/contatos/{id}` para show/update/delete. Global scope via `ScopesToCurrentTenant` e o pattern consolidado do projeto. `destroy()` com `withTrashed()->where('tenant_id')` explicito e necessario para detectar o 409 (contato ja inativo) sem bypassar o isolamento — identico ao `ClienteController::destroy()`.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

### D3: RBAC de contatos usa novos metodos canReadContatos/canWriteContatos no TenantRole

**Opcoes consideradas:**
- **Opcao A: adicionar `canReadContatos()` e `canWriteContatos()` ao `TenantRole` com logica identica a `canReadClientes()`/`canWriteClientes()`** — pros: semantica distinta por recurso; alteracoes futuras no RBAC de clientes nao afetam contatos silenciosamente; testes de contato testam os metodos corretos; contras: dois metodos que por ora sao identicos.
- **Opcao B: `ContatoPolicy` reutiliza `TenantRole::canReadClientes()`/`canWriteClientes()` diretamente** — pros: sem novo codigo; contras: acoplamento entre RBAC de clientes e contatos; se o RBAC de clientes mudar, contatos sao afetados sem intencao; nomes enganosos nos testes.
- **Opcao C: mapa de permissoes por recurso via config** — pros: flexivel; contras: over-engineering para MVP; nenhum ADR existente aponta nessa direcao.

**Escolhida:** Opcao A.

**Razao:** O projeto ja estabeleceu o pattern de metodos especificos por recurso no `TenantRole` (`canManageUsers`, `canViewPlan`, `canReadClientes`, `canWriteClientes`). Adicionar `canReadContatos`/`canWriteContatos` e consistente com esse pattern e previne acoplamento inadvertido entre recursos distintos. A implementacao inicial e identica a clientes, mas pode divergir sem refatoracao.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo (extensao do pattern estabelecido).

---

### D4: validacao condicional email-ou-whatsapp via withValidator no StoreContatoRequest

**Opcoes consideradas:**
- **Opcao A: `StoreContatoRequest::withValidator()` adiciona erro se nem `email` nem `whatsapp` estiverem presentes no payload (AC-015)** — pros: pattern identico ao `UpdateClienteRequest` do slice 013; validacao centralizada no FormRequest; mensagem clara; contras: nenhum relevante.
- **Opcao B: validar inline no Controller** — pros: menos arquivo; contras: viola SRP; inconsistente com o pattern do projeto.
- **Opcao C: regra customizada de validacao (Rule object)** — pros: reutilizavel; contras: over-engineering para uma regra simples de dois campos; sem reuso identificado em outros FormRequests.

**Escolhida:** Opcao A.

**Razao:** pattern estabelecido pelo `UpdateClienteRequest` do slice 013 usa exatamente `withValidator` para validacoes multi-campo. Consistente, centralizado, testavel.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

### D5: validacao de WhatsApp via regex minimo 10 digitos numericos (sem normalizacao para E.164)

**Opcoes consideradas:**
- **Opcao A: validar `whatsapp` com `regex:/^\d{10,20}$/` no FormRequest; armazenar como recebido (digitos apenas)** — pros: cobre AC-012 (DDD obrigatorio = minimo 10 digitos); sem formatacao opinionada; aceita `11987654321` e `5511987654321`; consistente com coluna `varchar(20)` do ERD; contras: nenhum para este scope.
- **Opcao B: normalizar para E.164 (`+55XXXXXXXXXXX`) antes de salvar** — pros: padrao internacional; contras: logica de normalizacao complexa; nenhum AC exige formato E.164; pode conflitar com E03-S02b (consentimento de canal whatsapp).
- **Opcao C: validar com mascara (`11 98765-4321`)** — pros: mais legivel; contras: spec especifica "digitos numericos" sem mascara; ERD define `varchar(20)` sem mascara.

**Escolhida:** Opcao A.

**Razao:** AC-012 exige "minimo 10 digitos numericos" com DDD obrigatorio. Regex `^\d{10,20}$` atende exatamente. Armazenar apenas digitos e consistente com o campo `documento` em `clientes`.

**Reversibilidade:** facil (regex pode ser endurecido sem alterar contrato da API).

**ADR:** nao requer ADR novo.

---

### D6: ContatoResource com campo consentimentos como stub vazio em E03-S02a

**Opcoes consideradas:**
- **Opcao A: `ContatoResource` retorna `consentimentos` como `{ "email_marketing": false, "whatsapp": false }` (stub hardcoded) ate E03-S02b implementar a tabela `consentimentos_contato`** — pros: contrato de API satisfeito parcialmente; campo presente no response conforme contrato; E03-S02b substitui stub por logica real sem alterar o shape; contras: stub pode mascarar bugs se E03-S02b nao substituir corretamente.
- **Opcao B: omitir campo `consentimentos` ate E03-S02b** — pros: sem dado falso; contras: viola contrato de API; testes de AC-016 esperariam o campo.
- **Opcao C: criar tabela `consentimentos_contato` agora** — pros: sem stub; contras: fora de escopo deste slice; spec exclui explicitamente consentimentos LGPD.

**Escolhida:** Opcao A.

**Razao:** spec declara que `consentimentos` e responsabilidade de E03-S02b. Contrato de API define o campo como presente no response. Stub com `false`/`false` e idiomatico — identico ao `whenCounted` com fallback 0 do slice 013. E03-S02b substitui o stub sem alterar o shape.

**Reversibilidade:** facil (E03-S02b substitui stub por logica real).

**ADR:** nao requer ADR novo.

---

### D7: destroy() desativa via ativo=false + SoftDelete — mesmo pattern de Cliente

**Opcoes consideradas:**
- **Opcao A: `destroy()` seta `ativo = false`, persiste, e chama `$contato->delete()` (SoftDeletes — seta `deleted_at`)** — pros: identico ao `ClienteController::destroy()`; dupla camada; contato some da listagem padrao; rastreabilidade via `deleted_at`; contras: nenhum.
- **Opcao B: apenas `ativo = false` sem SoftDelete** — pros: mais simples; contras: inconsistente com o pattern de Cliente; perde rastreabilidade.
- **Opcao C: SoftDelete puro sem campo ativo** — pros: menos redundancia; contras: spec e ERD definem campo `ativo`; contrato de API usa `ativo`; alterar seria mudar o contrato.

**Escolhida:** Opcao A.

**Razao:** spec define `ativo = false` explicitamente. ERD define ambos os campos. 409 para contato ja inativo implementado exatamente como no Cliente. Consistencia com o pattern existente e obrigada.

**Reversibilidade:** media (remover um dos dois campos exigiria migration + alteracao do resource).

**ADR:** nao requer ADR novo.

---

## Sequencia de implementacao (test-first por P2)

### T1: migration `create_contatos_table`

**Descricao:** Criar migration com todas as colunas do ERD E03 §2.2:
- `id` bigserial PK
- `tenant_id` bigint NOT NULL FK → `tenants.id` RESTRICT
- `cliente_id` bigint NOT NULL FK → `clientes.id` RESTRICT
- `nome` varchar(255) NOT NULL
- `email` varchar(254) nullable
- `whatsapp` varchar(20) nullable
- `papel` varchar(30) NOT NULL com check `in ('comprador','responsavel_tecnico','financeiro','outro')`
- `principal` boolean NOT NULL default false
- `ativo` boolean NOT NULL default true
- `created_by` bigint NOT NULL FK → `tenant_users.id` RESTRICT
- `updated_by` bigint NOT NULL FK → `tenant_users.id` RESTRICT
- `timestamps()` — `created_at`, `updated_at`
- `softDeletes()` — `deleted_at`

Indices compostos:
- `btree (cliente_id, ativo)` — listagem por cliente (query principal do index)
- `btree (tenant_id, cliente_id)` — isolamento
- `btree (tenant_id, papel)` — filtro por papel
- `btree (tenant_id, ativo)` — listagem geral
- `btree (deleted_at)` — queries de soft delete

`down()`: `Schema::dropIfExists('contatos')`.

**Arquivos:**
- Criar: `database/migrations/<timestamp>_create_contatos_table.php`

**ACs cobertos:** AC-001 (pre-requisito estrutural)

**Criterio de done:** `php artisan migrate:fresh` sem erro; tabela `contatos` criada com todos os campos e indices.

---

### T2: Model `Contato` + relacao `contatos()` no Model `Cliente`

**Descricao:** Criar Model seguindo o pattern de `Cliente`:
- Traits: `HasFactory`, `ScopesToCurrentTenant`, `SoftDeletes`
- `$fillable`: `tenant_id`, `cliente_id`, `nome`, `email`, `whatsapp`, `papel`, `principal`, `ativo`, `created_by`, `updated_by`
- `casts()`: `ativo` → `boolean`, `principal` → `boolean`, `created_at`/`updated_at`/`deleted_at` → `datetime`
- Relacao `cliente()`: `BelongsTo<Cliente, $this>`
- Relacao `tenant()`: `BelongsTo<Tenant, $this>`
- Relacao `createdBy()`: `BelongsTo<TenantUser, $this>` com FK `created_by`
- Relacao `updatedBy()`: `BelongsTo<TenantUser, $this>` com FK `updated_by`

No Model `Cliente`: adicionar relacao `contatos(): HasMany<Contato, $this>`.

**Arquivos:**
- Criar: `app/Models/Contato.php`
- Modificar: `app/Models/Cliente.php` (adicionar `contatos()` HasMany)

**ACs cobertos:** AC-001, AC-008, AC-010 (pre-requisito de model)

**Criterio de done:** `Contato::query()` aplica scope de tenant automaticamente; `$cliente->contatos()` resolve sem erro.

---

### T3: `ContatoFactory` e `ContatoSeeder`

**Descricao:**
- `ContatoFactory`: estados `ativo()`, `inativo()`, `comWhatsapp()`, `semWhatsapp()`. Campo `papel` randomico entre os 4 valores do enum. `tenant_id` e `cliente_id` obrigatorios — sem default autogerado (devem ser fornecidos nos testes).
- `ContatoSeeder`: cria 3 contatos para o cliente de seed padrao (se existir). Opcional no `DatabaseSeeder`.

**Arquivos:**
- Criar: `database/factories/ContatoFactory.php`
- Criar: `database/seeders/ContatoSeeder.php`

**ACs cobertos:** (infra de testes para todos os ACs)

**Criterio de done:** `ContatoFactory::new()->inativo()->make()` retorna `ativo = false`.

---

### T4: `TenantRole` — adicionar `canReadContatos()` e `canWriteContatos()`

**Descricao:** Adicionar dois metodos estaticos ao `TenantRole` (metodos existentes permanecem sem alteracao):
- `canReadContatos(string $role): bool` — retorna `true` para gerente, tecnico, administrativo, visualizador.
- `canWriteContatos(string $role): bool` — retorna `true` para gerente e administrativo; exclui tecnico e visualizador.

**Nota:** o contrato de API usa `'atendente'` como label de role. No `TenantRole`, isso mapeia para os roles `'gerente'` e `'administrativo'`. Ambos os metodos devem incluir PHPDoc documentando este mapeamento, seguindo o pattern de `canManageClientes()`.

**Arquivos:**
- Modificar: `app/Support/Tenancy/TenantRole.php`

**ACs cobertos:** AC-005, AC-006, AC-009, AC-010 (via Policy)

**Criterio de done:** `TenantRole::canWriteContatos('tecnico')` retorna `false`; `TenantRole::canReadContatos('tecnico')` retorna `true`.

---

### T5: `ContatoPolicy`

**Descricao:** Criar Policy com o mesmo pattern de `ClientePolicy`:
- `viewAny(User $user, TenantUser $tenantUser): bool` — `isActiveWithRole` + `canReadContatos()`.
- `view(User $user, TenantUser $tenantUser): bool` — `isActiveWithRole` + `canReadContatos()`.
- `create(User $user, TenantUser $tenantUser): bool` — `isActiveWithRole` + `canWriteContatos()`.
- `update(User $user, TenantUser $tenantUser): bool` — `isActiveWithRole` + `canWriteContatos()`.
- `delete(User $user, TenantUser $tenantUser): bool` — `isActiveWithRole` + `canWriteContatos()`.
- `isActiveWithRole(User $user, TenantUser $tenantUser): bool` — helper privado: `tenantUser->user_id === user->id` e `status === 'active'`.

Registrar a Policy: verificar `AuthServiceProvider::$policies`; adicionar `Contato::class => ContatoPolicy::class` se auto-discovery nao cobrir.

**Arquivos:**
- Criar: `app/Policies/ContatoPolicy.php`
- Verificar/Modificar: `app/Providers/AuthServiceProvider.php`

**ACs cobertos:** AC-005, AC-006, AC-009, AC-010

**Criterio de done:** `Gate::inspect('contatos.create', $tenantUser)` nega tecnico; permite gerente/administrativo.

---

### T6: `StoreContatoRequest`

**Descricao:** Criar FormRequest com:
- `authorize()`: retorna `true` (autorizacao via Gate no Controller).
- Regras:
  - `nome`: `required`, `string`, `max:255`
  - `email`: `nullable`, `string`, `email`, `max:254`
  - `whatsapp`: `nullable`, `string`, `regex:/^\d{10,20}$/`
  - `papel`: `required`, `string`, `in:comprador,responsavel_tecnico,financeiro,outro`
  - `principal`: `nullable`, `boolean`
- `withValidator()`: adiciona erro em `email` se nem `email` nem `whatsapp` estiverem presentes/nao-nulos (AC-015). Mensagem: `"Pelo menos email ou whatsapp deve ser informado."`.
- Mensagem customizada para `whatsapp.regex`: `"O WhatsApp deve conter apenas digitos, incluindo DDD (minimo 10 digitos)."`.

**Arquivos:**
- Criar: `app/Http/Requests/StoreContatoRequest.php`

**ACs cobertos:** AC-012, AC-015

**Criterio de done:** POST sem `email` e sem `whatsapp` retorna 422; POST com `whatsapp = "99999-9999"` retorna 422.

---

### T7: `UpdateContatoRequest`

**Descricao:** Criar FormRequest com:
- Todos os campos editaveis como `sometimes`:
  - `nome`: `sometimes`, `string`, `max:255`
  - `email`: `sometimes`, `nullable`, `string`, `email`, `max:254`
  - `whatsapp`: `sometimes`, `nullable`, `string`, `regex:/^\d{10,20}$/`
  - `papel`: `sometimes`, `string`, `in:comprador,responsavel_tecnico,financeiro,outro`
  - `principal`: `sometimes`, `nullable`, `boolean`
- Campo `cliente_id` ausente das regras — silenciosamente ignorado (AC-017).
- `withValidator()`: erro em `fields` se payload nao contiver nenhum dos campos editaveis (AC-004). Mensagem: `"Ao menos um campo deve ser informado."`.

**Arquivos:**
- Criar: `app/Http/Requests/UpdateContatoRequest.php`

**ACs cobertos:** AC-004, AC-017

**Criterio de done:** PUT com `{}` retorna 422; PUT com `{ "cliente_id": 999 }` retorna 422 (nenhum campo editavel presente); PUT com `{ "nome": "Novo" }` passa validacao.

---

### T8: `ContatoResource`

**Descricao:** Criar API Resource com:
- Campos: `id`, `cliente_id`, `nome`, `email`, `whatsapp`, `papel`, `principal`, `ativo`, `created_at` (ISO 8601), `updated_at` (ISO 8601).
- Campo `consentimentos` (stub E03-S02a):
  ```php
  'consentimentos' => ['email_marketing' => false, 'whatsapp' => false],
  ```
- Nao incluir `tenant_id`, `deleted_at`, `created_by`, `updated_by` no response (campos internos).

**Arquivos:**
- Criar: `app/Http/Resources/ContatoResource.php`

**ACs cobertos:** AC-016 (shape do response de GET /contatos/{id})

**Criterio de done:** Resource serializa todos os campos esperados; `consentimentos` presente com `false`/`false`.

---

### T9: `ContatoController`

**Descricao:** Criar Controller com 5 metodos:

**`index(Request $request, int $clienteId): JsonResponse`**
- Valida `ativo` (boolean, default `true`) e `page` (integer, min 1).
- `Gate::authorize('contatos.viewAny', $tenantUser)`.
- `Cliente::findOrFail($clienteId)` — scope garante 404 se cliente e de outro tenant.
- Query: `Contato::where('cliente_id', $clienteId)->where('ativo', $ativoFilter)`.
- Se total <= 20: retorna `->get()` sem paginar. Se > 20: `->paginate(20)->withQueryString()`.
- Retorna `ContatoResource::collection($result)` com meta estruturado.

**`store(StoreContatoRequest $request, int $clienteId): JsonResponse`**
- `Gate::authorize('contatos.create', $tenantUser)`.
- `Cliente::findOrFail($clienteId)` — 404 automatico cross-tenant.
- Cria: `Contato::create([...$request->validated(), 'tenant_id' => $tenant->id, 'cliente_id' => $clienteId, 'created_by' => $tenantUser->id, 'updated_by' => $tenantUser->id, 'ativo' => true])`.
- Retorna `ContatoResource` com status 201.

**`show(Request $request, int $id): JsonResponse`**
- `Gate::authorize('contatos.view', $tenantUser)`.
- `Contato::findOrFail($id)` — scope garante 404 cross-tenant.
- Retorna `ContatoResource` com status 200.

**`update(UpdateContatoRequest $request, int $id): JsonResponse`**
- `Gate::authorize('contatos.update', $tenantUser)`.
- `Contato::findOrFail($id)` — 404 automatico cross-tenant.
- `$contato->fill($request->validated())` — `cliente_id` ausente das regras, logo ignorado.
- `$contato->updated_by = $tenantUser->id; $contato->save()`.
- Retorna `ContatoResource` com status 200.

**`destroy(Request $request, int $id): JsonResponse`**
- `Gate::authorize('contatos.delete', $tenantUser)`.
- `Contato::withTrashed()->where('tenant_id', $tenant->id)->where('id', $id)->firstOrFail()` — inclui inativos para detectar 409; `where('tenant_id')` explicito porque `withTrashed` bypassa o global scope.
- Se `!$contato->ativo`: retorna 409 `contato_ja_inativo`.
- `$contato->ativo = false; $contato->save(); $contato->delete()`.
- Retorna JSON `{ message, data: { id, ativo: false, updated_at } }` com status 200.

**Arquivos:**
- Criar: `app/Http/Controllers/ContatoController.php`

**ACs cobertos:** AC-001, AC-002, AC-003, AC-005, AC-006, AC-007, AC-008, AC-009, AC-010, AC-011, AC-013, AC-014, AC-016, AC-017

**Criterio de done:** Todos os metodos respondem com status e payloads corretos conforme API contract.

---

### T10: `routes/web.php` — adicionar rotas de contato

**Descricao:** Adicionar bloco de rotas no mesmo grupo de middleware dos slices 012/013 (`auth` + `EnsureTwoFactorChallengeCompleted` + `SetCurrentTenantContext` + `EnsureReadOnlyTenantMode`):

```
POST   /clientes/{cliente}/contatos   → ContatoController@store    (contatos.store)
GET    /clientes/{cliente}/contatos   → ContatoController@index    (contatos.index)
GET    /contatos/{contato}            → ContatoController@show     (contatos.show)
PUT    /contatos/{contato}            → ContatoController@update   (contatos.update)
DELETE /contatos/{contato}            → ContatoController@destroy  (contatos.destroy)
```

Adicionar `use App\Http\Controllers\ContatoController;` no arquivo.

**Arquivos:**
- Modificar: `routes/web.php`

**ACs cobertos:** (estrutural — todas as rotas do spec)

**Criterio de done:** `php artisan route:list --name=contatos` exibe 5 rotas.

---

### T11: Testes Pest — todos os ACs do slice 014

**Descricao:** Criar suite de testes em `tests/slice-014/`.

**Arquivos:**
- `tests/slice-014/ContatoStoreTest.php` — AC-001, AC-002, AC-009, AC-012, AC-015
- `tests/slice-014/ContatoUpdateTest.php` — AC-003, AC-004, AC-005, AC-013, AC-017
- `tests/slice-014/ContatoDestroyTest.php` — AC-006, AC-007, AC-011, AC-014
- `tests/slice-014/ContatoShowTest.php` — AC-008, AC-010, AC-016
- Modificar: `tests/Pest.php` — registrar diretorio `tests/slice-014`

**ACs cobertos:** AC-001 a AC-017 (todos)

**Criterio de done:** `php artisan test tests/slice-014` com exit 0, todos os testes passam.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `ContatoController::store()`, `StoreContatoRequest`, `ContatoResource`, `Contato`, `routes/web.php` | `tests/slice-014/ContatoStoreTest.php` |
| AC-002 | `ContatoController::store()`, `ContatoController::index()` | `tests/slice-014/ContatoStoreTest.php` |
| AC-003 | `ContatoController::update()`, `UpdateContatoRequest`, `ContatoResource` | `tests/slice-014/ContatoUpdateTest.php` |
| AC-004 | `UpdateContatoRequest` (withValidator payload nao-vazio) | `tests/slice-014/ContatoUpdateTest.php` |
| AC-005 | `ContatoPolicy::update()`, `TenantRole::canWriteContatos()` | `tests/slice-014/ContatoUpdateTest.php` |
| AC-006 | `ContatoPolicy::delete()`, `TenantRole::canWriteContatos()` | `tests/slice-014/ContatoDestroyTest.php` |
| AC-007 | `ContatoController::destroy()` (409 se ativo=false) | `tests/slice-014/ContatoDestroyTest.php` |
| AC-008 | `Contato::findOrFail()` + `ScopesToCurrentTenant` → 404 cross-tenant no GET | `tests/slice-014/ContatoShowTest.php` |
| AC-009 | `ContatoPolicy::create()`, `TenantRole::canWriteContatos()` | `tests/slice-014/ContatoStoreTest.php` |
| AC-010 | `ContatoPolicy::viewAny()`, `TenantRole::canReadContatos()` | `tests/slice-014/ContatoShowTest.php` |
| AC-011 | `ContatoController::destroy()` (ativo=false + soft-delete + some da listagem) | `tests/slice-014/ContatoDestroyTest.php` |
| AC-012 | `StoreContatoRequest` (regex whatsapp) | `tests/slice-014/ContatoStoreTest.php` |
| AC-013 | `Contato::findOrFail()` + scope → 404 cross-tenant no PUT | `tests/slice-014/ContatoUpdateTest.php` |
| AC-014 | `ContatoController::destroy()` + `where('tenant_id')` explicito → 404 cross-tenant | `tests/slice-014/ContatoDestroyTest.php` |
| AC-015 | `StoreContatoRequest` (withValidator email-ou-whatsapp) | `tests/slice-014/ContatoStoreTest.php` |
| AC-016 | `ContatoController::show()`, `ContatoResource` (shape com todos os campos) | `tests/slice-014/ContatoShowTest.php` |
| AC-017 | `UpdateContatoRequest` (cliente_id ausente das regras), `ContatoController::update()` | `tests/slice-014/ContatoUpdateTest.php` |

---

## Novos arquivos

- `database/migrations/<timestamp>_create_contatos_table.php` — schema da tabela contatos (ERD E03 §2.2)
- `app/Models/Contato.php` — model com ScopesToCurrentTenant, SoftDeletes, fillable, casts, relacoes
- `database/factories/ContatoFactory.php` — factory com estados ativo/inativo/comWhatsapp/semWhatsapp
- `database/seeders/ContatoSeeder.php` — seed de contatos para testes manuais
- `app/Http/Controllers/ContatoController.php` — 5 metodos: index, store, show, update, destroy
- `app/Http/Requests/StoreContatoRequest.php` — validacao POST com regra condicional email-ou-whatsapp
- `app/Http/Requests/UpdateContatoRequest.php` — validacao PUT parcial, sem cliente_id, payload nao-vazio
- `app/Policies/ContatoPolicy.php` — RBAC via canReadContatos/canWriteContatos
- `app/Http/Resources/ContatoResource.php` — resource com stub de consentimentos
- `tests/slice-014/ContatoStoreTest.php` — ACs 001, 002, 009, 012, 015
- `tests/slice-014/ContatoUpdateTest.php` — ACs 003, 004, 005, 013, 017
- `tests/slice-014/ContatoDestroyTest.php` — ACs 006, 007, 011, 014
- `tests/slice-014/ContatoShowTest.php` — ACs 008, 010, 016

---

## Arquivos modificados

- `app/Support/Tenancy/TenantRole.php` — adicionar `canReadContatos()` e `canWriteContatos()`
- `app/Models/Cliente.php` — adicionar relacao `contatos(): HasMany<Contato, $this>`
- `routes/web.php` — adicionar bloco de rotas de contato (5 rotas) com comentario de slice
- `app/Providers/AuthServiceProvider.php` — adicionar `Contato::class => ContatoPolicy::class` se auto-discovery nao cobrir
- `tests/Pest.php` — registrar diretorio `tests/slice-014`

---

## Schema / migrations

**Arquivo:** `database/migrations/<timestamp>_create_contatos_table.php`

Colunas obrigatorias (NOT NULL): `id`, `tenant_id`, `cliente_id`, `nome`, `papel`, `principal` (default false), `ativo` (default true), `created_by`, `updated_by`, `created_at`, `updated_at`.

Colunas nullable: `email`, `whatsapp`, `deleted_at`.

FKs (todas RESTRICT):
- `tenant_id` → `tenants.id`
- `cliente_id` → `clientes.id`
- `created_by` → `tenant_users.id`
- `updated_by` → `tenant_users.id`

Check constraint: `papel IN ('comprador','responsavel_tecnico','financeiro','outro')`.

Indices:
- `(cliente_id, ativo)` — listagem por cliente
- `(tenant_id, cliente_id)` — isolamento
- `(tenant_id, papel)` — filtro por papel
- `(tenant_id, ativo)` — listagem geral
- `(deleted_at)` — soft delete

`down()`: `Schema::dropIfExists('contatos')`.

---

## APIs / contratos

### POST /clientes/{clienteId}/contatos

**Permissao:** gerente, administrativo (tecnico → 403)

**Request body:**
```json
{
  "nome": "Ana Paula Silva",
  "email": "ana.silva@calibralab.com.br",
  "whatsapp": "11987654321",
  "papel": "responsavel_tecnico"
}
```

**Response 201:**
```json
{
  "data": {
    "id": 42,
    "cliente_id": 7,
    "nome": "Ana Paula Silva",
    "email": "ana.silva@calibralab.com.br",
    "whatsapp": "11987654321",
    "papel": "responsavel_tecnico",
    "principal": false,
    "ativo": true,
    "consentimentos": { "email_marketing": false, "whatsapp": false },
    "created_at": "2026-04-16T10:00:00-03:00",
    "updated_at": "2026-04-16T10:00:00-03:00"
  }
}
```

**Response 422 (whatsapp sem DDD):**
```json
{
  "message": "O campo whatsapp nao e valido.",
  "errors": {
    "whatsapp": ["O WhatsApp deve conter apenas digitos, incluindo DDD (minimo 10 digitos)."]
  }
}
```

**Response 422 (sem email e sem whatsapp):**
```json
{
  "message": "Pelo menos email ou whatsapp deve ser informado.",
  "errors": {
    "email": ["Pelo menos email ou whatsapp deve ser informado."]
  }
}
```

---

### GET /clientes/{clienteId}/contatos

**Permissao:** tecnico, gerente, administrativo

**Query params:** `ativo` (boolean, default true), `page` (integer, default 1)

**Response 200:**
```json
{
  "data": [
    {
      "id": 42,
      "cliente_id": 7,
      "nome": "Ana Paula Silva",
      "email": "ana.silva@calibralab.com.br",
      "whatsapp": "11987654321",
      "papel": "responsavel_tecnico",
      "principal": false,
      "ativo": true,
      "consentimentos": { "email_marketing": false, "whatsapp": false },
      "created_at": "2026-04-16T10:00:00-03:00",
      "updated_at": "2026-04-16T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 2,
    "last_page": 1
  }
}
```

---

### GET /contatos/{id}

**Permissao:** tecnico, gerente, administrativo

**Response 200:** objeto `data` singular com mesmo shape do item acima.

**Response 404:** contato nao existe no tenant atual (isolamento via scope).

---

### PUT /contatos/{id}

**Permissao:** gerente, administrativo (tecnico → 403)

**Request:** qualquer subconjunto de `nome`, `email`, `whatsapp`, `papel`, `principal` (pelo menos 1 obrigatorio). Campo `cliente_id` e silenciosamente ignorado.

**Response 200:** mesmo shape de `GET /contatos/{id}`.

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

### DELETE /contatos/{id}

**Permissao:** gerente, administrativo (tecnico → 403)

**Response 200:**
```json
{
  "message": "Contato desativado com sucesso.",
  "data": {
    "id": 42,
    "ativo": false,
    "updated_at": "2026-04-16T15:30:00-03:00"
  }
}
```

**Response 409:**
```json
{
  "message": "Este contato ja esta desativado.",
  "code": "contato_ja_inativo"
}
```

---

## Riscos e mitigacoes

- **tenant_id do contato divergir do tenant_id do cliente pai** — mitigacao: `store()` carrega o cliente via `Cliente::findOrFail($clienteId)` (scope de tenant garante que o cliente e do tenant correto) e usa `$tenant->id` do contexto — nao o `$cliente->tenant_id` — para preencher `contato.tenant_id`; os dois serao iguais por construcao; inconsistencia so seria possivel se o scope fosse bypassado, o que os testes cobrem.
- **ScopesToCurrentTenant bypassado pelo withTrashed() no destroy()** — mitigacao: `destroy()` usa `Contato::withTrashed()->where('tenant_id', $tenant->id)->where('id', $id)->firstOrFail()` explicitamente, identico ao pattern do `ClienteController::destroy()`; AC-014 testa especificamente que DELETE cross-tenant retorna 404.
- **ContatoPolicy nao registrada no Gate** — mitigacao: verificar `AuthServiceProvider::$policies`; adicionar entrada manual `Contato::class => ContatoPolicy::class`; teste de AC-009 (tecnico → 403) falharia imediatamente se a Policy nao fosse reconhecida — seria detectado na fase de testes red.
- **Paginacao condicional (<= 20 sem paginar, > 20 com paginar) com shapes diferentes** — mitigacao: mesmo sem paginacao Laravel, retornar estrutura `{ data, meta }` com meta manual para manter shape consistente com o contrato de API; implementar helper privado no controller para encapsular esta logica.
- **Stub de consentimentos em E03-S02a pode ser esquecido** — mitigacao: adicionar comentario `// TODO: E03-S02b substitui este stub por logica real` no `ContatoResource`; testes de E03-S02b devem regredir o shape para confirmar que nao quebrou.
- **Campo papel como varchar(30) com check constraint pode ser rigido** — mitigacao: check constraint no PostgreSQL e alteravel via `ALTER TABLE`; alteracao futura nao afeta o contrato da API enquanto o FormRequest for atualizado junto; sem impacto em dados existentes.
- **Cliente pai nao encontrado (clienteId invalido) no store/index** — mitigacao: `Cliente::findOrFail($clienteId)` retorna 404 automatico via scope de tenant; o scope garante que cliente de outro tenant tambem retorna 404 (nao 403), mantendo consistencia com AC-008.

---

## Dependencias de outros slices

- `slice-012` (E03-S01a) — Model `Cliente`, migration `clientes`, `ScopesToCurrentTenant` trait, `ClienteFactory`, `ClientePolicy`, `StoreClienteRequest`. O model `Contato` depende da tabela `clientes` via FK `cliente_id`.
- `slice-013` (E03-S01b) — `TenantRole::canReadClientes()` e `canWriteClientes()` (referencia de pattern para os novos metodos de contatos). `UpdateClienteRequest` (referencia de pattern para `withValidator`). `ClienteController` (referencia de pattern para todos os metodos).
- `slice-008` (E02-S04) — `TenantRole`, `ScopesToCurrentTenant` trait, `SetCurrentTenantContext` middleware.
- `slice-007` (E02-S03) — autenticacao, model `TenantUser`.

---

## Fora de escopo deste plano (confirmando spec)

- Regra de unicidade do campo `principal` (apenas 1 contato principal por cliente) — slice posterior.
- Consentimentos LGPD (`consentimentos_contato`) — E03-S02b. O campo `consentimentos` retorna stub `false`/`false` neste slice.
- Trait `Auditable` (owen-it/laravel-auditing) no Model `Contato` — E03-S07a. Nao incluir neste slice.
- Envio efetivo de e-mail ou WhatsApp — E06.
- Exportacao de dados / direito ao esquecimento — pos-MVP.
- Filtro por `papel` na listagem GET `/clientes/{id}/contatos` — nao previsto no spec nem no contrato de API deste slice.
- UI/frontend — backend-only, sem views Blade.
- `PATCH /contatos/{id}` — contrato define `PUT`; semantica PATCH fora de escopo.
