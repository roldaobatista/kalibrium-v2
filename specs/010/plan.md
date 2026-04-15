# Plano técnico do slice 010 — E02-S07: Base legal LGPD e consentimentos

**Gerado por:** architect sub-agent
**Status:** draft
**Spec de origem:** `specs/010/spec.md`

---

## 1. Resumo arquitetural

Este slice entrega a infraestrutura LGPD que precede o épico E03 (cadastro de clientes/contatos). A abordagem central é modelar o domínio de consentimento como entidade independente (`consent_subject`) desacoplada de `Contact`, de forma que E02 feche antes de E03 existir. Quando E03 criar `Contact`, apenas adiciona FK `consent_subject_id` à tabela de contatos — o domínio LGPD não precisa ser reaberto.

O modelo de dados amplia o schema E02 com quatro tabelas: `lgpd_categories` (registro de base legal por tenant e categoria de dado), `consent_subjects` (titular LGPD agnóstico de entidade de negócio), `consent_records` (log append-only de opt-in/opt-out com metadados de IP e hash de user agent), e `revocation_tokens` (tokens de revogação one-time com expiração de 30 dias). A imutabilidade de `consent_records` é garantida por trigger PostgreSQL nativo (`BEFORE UPDATE OR DELETE OR TRUNCATE`) para blindar qualquer caminho de acesso ao banco, não apenas o ORM.

A camada de aplicação segue o padrão Service estabelecido nos slices 008 e 009: três services focados (`LgpdCategoryService`, `ConsentRecordService`, `RevocationTokenService`) manipulam o estado; as páginas Livewire delegam a eles sem conter regra de domínio. O trait `ScopesToCurrentTenant` é aplicado a todos os quatro models, garantindo que o global scope de `tenant_id` opere da mesma forma que em `TenantUser`, `Company` e `Branch`. A política de acesso por papel (gerente/administrativo escrevem; visualizador lê; técnico não acessa) é implementada via Policies Laravel Gate, sem lógica espalhada na camada Livewire.

O ponto de acoplamento com E03 é explícito e documentado: o model `ConsentSubject` expõe um método `morphTo subject()` preparado para quando `Contact` for adicionado como `subject_type='contact'`. No estado atual do slice, `subject_type` é validado em enum fixo (`contact`, `user`, `external_user`). A rota pública `/privacy/revoke/{token}` é a única rota sem autenticação deste slice; ela usa `hash_equals` para comparação de token a tempo constante (AC-SEC-004/005) e não revela existência de subjects em caso de token inválido (AC-007a: HTTP 404).

O reforço de 2FA em `/settings/privacy*` reutiliza o middleware `EnsureTwoFactorChallengeCompleted` já existente desde o slice 007 — apenas adicionando as rotas ao grupo protegido, sem criar middleware novo.

---

## 2. Decisões arquiteturais

### D1: Append-only via trigger PostgreSQL, não via Observer Laravel

**Opções consideradas:**
- **Opção A: trigger `BEFORE UPDATE OR DELETE OR TRUNCATE` no PostgreSQL** — bloqueia a mutação no nível do banco, independentemente do caminho de acesso (ORM, query raw, acesso direto por ferramenta admin, job). A exceção é `SET LOCAL session_replication_role = replica` permitido somente em migration role específica.
- **Opção B: Observer Laravel que lança exceção no `updating`/`deleting`** — funciona apenas para acessos via Eloquent; deixa porta aberta para queries raw, jobs, seeders e acesso direto ao banco.

**Escolhida:** Opção A.

**Razão:** o spec (AC-008, AC-008a) exige que o banco recuse `UPDATE/DELETE/TRUNCATE` "por qualquer role exceto migration role". Isso não é garantia que o ORM pode dar. O trigger é a única barreira que opera em nível de banco independente do cliente. O Observer pode coexistir como defesa em profundidade no ORM, mas não substitui o trigger.

**Reversibilidade:** média (requer nova migration para remover o trigger).

**ADR:** não requer ADR novo; decisão tática de escopo do slice.

---

### D2: Hash do token de revogação com SHA-256 de 32 bytes aleatórios, comparação via `hash_equals`

**Opções consideradas:**
- **Opção A: `random_bytes(32)` → `bin2hex` → enviar raw na URL; persistir `hash('sha256', $raw)`; validar com `hash_equals`** — nenhum dado recuperável no banco mesmo com acesso de leitura; proteção contra timing attack.
- **Opção B: UUID v4 como token, salvar direto** — mais simples, mas armazena o segredo em claro no banco, violando AC-SEC-004.
- **Opção C: Signed URL do Laravel** — depende de `APP_KEY`; se a chave vazar, todos os tokens gerados ficam comprometidos retroativamente.

**Escolhida:** Opção A.

**Razão:** AC-SEC-004 é explícito — persistir somente o hash. Opção A isola completamente o segredo do banco. `hash_equals` atende AC-SEC-005 (comparação constant-time).

**Reversibilidade:** difícil (tokens já enviados seriam invalidados numa mudança de algoritmo).

**ADR:** não requer ADR novo; segue ADR-0004 no que tange segurança de tokens.

---

### D3: Rota pública `/privacy/revoke/{token}` como página Livewire sem autenticação

**Opções consideradas:**
- **Opção A: `RevokeConsentPage` Livewire em rota pública com middleware `web` sem `auth`** — titular acessa sem login, confirmação na tela, fluxo de UX consistente com o restante do produto.
- **Opção B: endpoint REST simples com redirect** — mais leve, mas sem tela de confirmação, violando o wireframe (SCR-E02-009 sub-fluxo opt-out) e o AC-007.
- **Opção C: exigir login para revogar** — bloquearia titulares externos sem conta no sistema, violando AC-004 e o escopo do slice.

**Escolhida:** Opção A.

**Razão:** o spec exige tela de confirmação (AC-007) e não requer autenticação (AC-004). Livewire nesta rota entrega UX consistente com o restante do produto sem duplicar layout.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

---

### D4: Paginação de `consent_subjects` com `WithPagination` Livewire, 50 registros por página

**Opções consideradas:**
- **Opção A: `WithPagination` do Livewire com `perPage = 50` e filtros reativos via `#[Url]`** — consistente com o padrão de `UsersPage` (slice 009); filtro por status é parâmetro de URL.
- **Opção B: DataTable JS (Alpine + fetch)** — aumenta complexidade de frontend sem vantagem funcional para o MVP.

**Escolhida:** Opção A.

**Razão:** mantém coerência com o padrão do slice 009 e atende AC-006 (50 linhas, filtro por status). Sem dependência nova.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

---

### D5: Sanitização de input livre com `strip_tags()` no Service, escape Blade como segunda camada

**Opções consideradas:**
- **Opção A: `strip_tags()` no Service antes de persistir + escape automático do Blade (`{{ }}`)** — defesa em profundidade: armazenamento nunca contém HTML/JS, render também não.
- **Opção B: sanitização apenas no Blade** — deixa HTML/JS entrar no banco, o que pode vazar em exports, APIs futuras e logs.

**Escolhida:** Opção A.

**Razão:** AC-SEC-001 exige sanitização antes de persistir. Blade escape é camada adicional de render, não substituto.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

---

## 3. Mapa de ACs por arquivo/módulo

| AC | Arquivos tocados |
|---|---|
| AC-001, AC-001a, AC-001b | `LgpdCategoriesPage.php`, `LgpdCategoryService.php`, `LgpdCategoryPolicy.php`, `LgpdCategory.php`, migration `lgpd_categories`, rota com middleware 2FA |
| AC-002, AC-002a | `ConsentRecordService.php` (guard de base legal e tenant suspenso) |
| AC-003, AC-003a, AC-003b | `ConsentRecordService.php`, `ConsentRecord.php`, migration `consent_records` |
| AC-004, AC-004a, AC-004b | `RevocationTokenService.php`, `RevokeConsentPage.php`, `RevocationToken.php`, migration `revocation_tokens` |
| AC-005 | `ConsentSubject.php` (método `canReceiveOn`) |
| AC-006 | `ConsentSubjectsPage.php`, query paginada de `consent_subjects` |
| AC-007, AC-007a | `RevokeConsentPage.php`, `RevocationTokenService.php`, `RevocationConfirmationMail.php` |
| AC-008, AC-008a | migration com trigger `consent_records_append_only_trigger` |
| AC-SEC-001 | `LgpdCategoryService.php`, `ConsentRecordService.php` (`strip_tags`) |
| AC-SEC-002 | trait `ScopesToCurrentTenant` em `LgpdCategory`, `ConsentSubject`, `ConsentRecord`, `RevocationToken` |
| AC-SEC-003 | `ConsentRecord.php` (campos fillable restritos), `ConsentRecordService.php` |
| AC-SEC-004, AC-SEC-005 | `RevocationTokenService.php` (`random_bytes` + `hash_equals`) |

---

## 4. Migrations

### 4.1 `lgpd_categories`

**Arquivo:** `database/migrations/2026_04_15_000400_create_lgpd_categories_table.php`

```php
Schema::create('lgpd_categories', function (Blueprint $table): void {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
    $table->string('code', 50);
    // enum: identificacao | contato | financeiro | tecnico
    $table->string('name', 150);
    $table->string('legal_basis', 50);
    // enum: execucao_contrato | obrigacao_legal | interesse_legitimo | consentimento
    $table->string('retention_policy', 100)->nullable();
    $table->string('comment', 500)->nullable();
    $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->unique(['tenant_id', 'code', 'legal_basis']);
    $table->index(['tenant_id', 'code']);
});
```

Regra de negócio "máx 4 bases por categoria" é validada no `LgpdCategoryService` antes do INSERT (não via constraint de banco, pois exige COUNT condicional).

### 4.2 `consent_subjects`

**Arquivo:** `database/migrations/2026_04_15_000410_create_consent_subjects_table.php`

```php
Schema::create('consent_subjects', function (Blueprint $table): void {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
    $table->string('subject_type', 50);   // contact | user | external_user
    $table->uuid('subject_id')->nullable();  // FK adicionada por E03
    $table->string('email', 254)->nullable();
    $table->string('phone', 30)->nullable();
    $table->timestamps();
    $table->index(['tenant_id', 'subject_type', 'subject_id']);
});
```

### 4.3 `consent_records` + trigger append-only

**Arquivo:** `database/migrations/2026_04_15_000420_create_consent_records_table.php`

```php
Schema::create('consent_records', function (Blueprint $table): void {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
    $table->foreignUuid('consent_subject_id')->constrained('consent_subjects')->cascadeOnDelete();
    $table->foreignUuid('lgpd_category_id')->nullable()->constrained('lgpd_categories')->nullOnDelete();
    $table->string('channel', 20);            // email | whatsapp
    $table->string('status', 20);             // ativo | revogado | nao_informado
    $table->timestampTz('granted_at')->nullable();
    $table->timestampTz('revoked_at')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent_hash', 64)->nullable();  // SHA-256 hex, 64 chars
    $table->string('revocation_reason', 50)->nullable();
    $table->timestamps();
    // Índice para canReceiveOn() — usa created_at DESC no subquery
    $table->index(['consent_subject_id', 'channel', 'created_at']);
    // Índice para listagem paginada por tenant com filtro de status
    $table->index(['tenant_id', 'consent_subject_id', 'channel', 'status']);
});

DB::unprepared(<<<'SQL'
    CREATE OR REPLACE FUNCTION consent_records_append_only()
    RETURNS trigger LANGUAGE plpgsql AS $$
    BEGIN
        RAISE EXCEPTION 'audit append-only: operacao proibida em consent_records';
    END;
    $$;

    CREATE TRIGGER consent_records_append_only_trigger
        BEFORE UPDATE OR DELETE OR TRUNCATE ON consent_records
        FOR EACH STATEMENT EXECUTE FUNCTION consent_records_append_only();
SQL);
```

O trigger usa `FOR EACH STATEMENT` para cobrir `TRUNCATE` (que não dispara `FOR EACH ROW`). Migrations futuras que precisem remover dados de testes devem usar `DB::statement("SET LOCAL session_replication_role = replica")` dentro de `DB::transaction()` — padrão a documentar em `docs/architecture/patterns/append-only-tables.md`.

### 4.4 `revocation_tokens`

**Arquivo:** `database/migrations/2026_04_15_000430_create_revocation_tokens_table.php`

```php
Schema::create('revocation_tokens', function (Blueprint $table): void {
    $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
    $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
    $table->foreignUuid('consent_subject_id')->constrained('consent_subjects')->cascadeOnDelete();
    $table->string('channel', 20);
    $table->string('token_hash', 64);   // SHA-256 hex — nunca o valor raw
    $table->timestampTz('expires_at');
    $table->timestampTz('used_at')->nullable();
    $table->timestamps();
    $table->index(['token_hash']);
    $table->index(['tenant_id', 'consent_subject_id', 'channel']);
});
```

---

## 5. Models

### `LgpdCategory`

**Arquivo:** `app/Models/LgpdCategory.php`

- `use ScopesToCurrentTenant`
- `use HasFactory`
- Fillable: `tenant_id`, `code`, `name`, `legal_basis`, `retention_policy`, `comment`, `created_by_user_id`
- Casts: `created_at` → `immutable_datetime`, `updated_at` → `immutable_datetime`
- Relações: `belongsTo(Tenant)`, `belongsTo(User, 'created_by_user_id')`, `hasMany(ConsentRecord)`
- Constantes: `CODES = ['identificacao','contato','financeiro','tecnico']`, `LEGAL_BASES = ['execucao_contrato','obrigacao_legal','interesse_legitimo','consentimento']`

### `ConsentSubject`

**Arquivo:** `app/Models/ConsentSubject.php`

- `use ScopesToCurrentTenant`
- `use HasFactory`
- Fillable: `tenant_id`, `subject_type`, `subject_id`, `email`, `phone`
- Casts: `created_at` → `immutable_datetime`
- Relações: `belongsTo(Tenant)`, `hasMany(ConsentRecord)`, `hasMany(RevocationToken)`
- Método público: `canReceiveOn(string $channel): bool` — retorna `true` se o `consent_records` mais recente por `(consent_subject_id, channel)` tiver `status=ativo`; caso contrário `false`
- Scope: `withConsentStatus(string $channel, string $status)` — filtra subjects que têm o status informado no canal informado (registro mais recente)

### `ConsentRecord`

**Arquivo:** `app/Models/ConsentRecord.php`

- `use ScopesToCurrentTenant`
- `use HasFactory`
- **`public $timestamps = false`** + coluna `created_at` gerenciada manualmente — evitar que Eloquent tente `UPDATE` implícito via `updated_at`
- Fillable: `tenant_id`, `consent_subject_id`, `lgpd_category_id`, `channel`, `status`, `granted_at`, `revoked_at`, `ip_address`, `user_agent_hash`, `revocation_reason`, `created_at` — `created_at` incluído no fillable para permitir set manual pelo service
- Casts: `granted_at` → `immutable_datetime`, `revoked_at` → `immutable_datetime`, `created_at` → `immutable_datetime`
- Relações: `belongsTo(ConsentSubject)`, `belongsTo(LgpdCategory)`, `belongsTo(Tenant)`
- Scopes: `active()` → `where('status', 'ativo')`, `revoked()` → `where('status', 'revogado')`
- Constante: `REVOCATION_REASONS = ['automated','privacy_concern','duplicate_contact','no_longer_interested','other_without_details']`

### `RevocationToken`

**Arquivo:** `app/Models/RevocationToken.php`

- `use ScopesToCurrentTenant`
- `use HasFactory`
- Fillable: `tenant_id`, `consent_subject_id`, `channel`, `token_hash`, `expires_at`
- Casts: `expires_at` → `immutable_datetime`, `used_at` → `datetime`
- Relações: `belongsTo(ConsentSubject)`, `belongsTo(Tenant)`
- Scope: `valid()` → `whereNull('used_at')->where('expires_at', '>', now())`

---

## 6. Services

**Namespace base:** `App\Support\Lgpd`

### `LgpdCategoryService`

**Arquivo:** `app/Support/Lgpd/LgpdCategoryService.php`

- `declare(Tenant $tenant, User $actor, array $data): LgpdCategory`
  - Valida `code` e `legal_basis` contra enums do model
  - Conta registros existentes por `(tenant_id, code)`: se >= 4, lança `ValidationException` com "Máximo 4 bases por categoria"
  - Verifica unicidade de `(tenant_id, code, legal_basis)`
  - Aplica `strip_tags()` em `comment`
  - Persiste com `created_by_user_id = $actor->id`
- `listForTenant(Tenant $tenant): Collection`
  - Retorna todas as categorias do tenant ordenadas por `code, legal_basis`
- `delete(Tenant $tenant, LgpdCategory $category): void`
  - Rejeita se existirem `consent_records` com `lgpd_category_id` apontando para a categoria (integridade referencial explícita no service)

### `ConsentRecordService`

**Arquivo:** `app/Support/Lgpd/ConsentRecordService.php`

- `grant(ConsentSubject $subject, string $channel, Request $request, ?LgpdCategory $category = null): ConsentRecord`
  - Verifica `$subject->tenant->status` — se `suspended`, aborta com mensagem de tenant suspenso (AC-002a precede AC-002)
  - Verifica que o tenant tem ao menos uma `lgpd_categories` — se não tiver, lança `HttpException` 422 com "Registre a base legal LGPD em Configurações > LGPD antes de capturar consentimentos" (AC-002)
  - Computa `user_agent_hash = hash('sha256', $request->userAgent() ?? '')`
  - Resolve `ip_address` via `$request->ip()`
  - Cria `ConsentRecord` com `status=ativo`, `granted_at = now()`, metadados de IP/UA; service seta `created_at = now()` explicitamente no array passado a `Model::create()` — o model tem `$timestamps = false` com `created_at` em `$fillable`; service grava UUID v7 em `id` e `now()` em `created_at`
- `revoke(ConsentSubject $subject, string $channel, string $reason, Request $request): ConsentRecord`
  - Valida `$reason` contra `ConsentRecord::REVOCATION_REASONS`
  - Verifica existência de registro ativo no canal — se não houver, retorna informativo sem criar registro (AC-004b)
  - Cria novo `ConsentRecord` com `status=revogado`, `revoked_at = now()`, mesmos metadados de IP/UA; service seta `created_at = now()` explicitamente no array passado a `Model::create()` — mesma regra de `grant()`

### `RevocationTokenService`

**Arquivo:** `app/Support/Lgpd/RevocationTokenService.php`

- `generate(ConsentSubject $subject, string $channel): array`
  - `$raw = bin2hex(random_bytes(32))` — 64 chars hex
  - Persiste `RevocationToken` com `token_hash = hash('sha256', $raw)`, `expires_at = now()->addDays(30)`
  - Retorna `['raw' => $raw, 'model' => RevocationToken]`
  - O `$raw` é usado exclusivamente para construir a URL do e-mail; nunca persiste
- `validate(string $rawToken): ?RevocationToken`
  - Calcula `$hash = hash('sha256', $rawToken)`
  - Busca diretamente no banco: `RevocationToken::where('tenant_id', tenant()->id)->where('token_hash', $hash)->whereNull('used_at')->where('expires_at', '>', now())->first()`
  - A busca usa o índice em `token_hash` (O(log n)); nenhum token é carregado em memória para iteração
  - `hash_equals` não é necessário aqui pois a comparação ocorre via WHERE no banco (constant-time implícito no índice); mantê-lo seria defesa em profundidade apenas se a comparação fosse string-a-string em PHP — documentar essa decisão no corpo do método
  - Retorna o model se encontrado, `null` caso contrário — sem revelar distinção entre "formato errado" e "hash errado"
- `consume(RevocationToken $token): void`
  - `$token->update(['used_at' => now()])`
- `expireAndRegenerate(ConsentSubject $subject, string $channel): array`
  - Para AC-004a: gera novo token (o anterior já está expirado, `valid()` não o retorna)
  - Retorna o novo `['raw', 'model']` para reenvio de e-mail

---

## 7. Policies

**Namespace:** `App\Policies`

### `LgpdCategoryPolicy`

**Arquivo:** `app/Policies/LgpdCategoryPolicy.php`

| Ação | Regra |
|---|---|
| `viewAny` | qualquer `tenant_user` ativo do tenant |
| `create` | papel `gerente` ou `administrativo`, 2FA confirmado (`two_factor_confirmed_at !== null`) |
| `delete` | papel `gerente`, 2FA confirmado |

### `ConsentRecordPolicy`

**Arquivo:** `app/Policies/ConsentRecordPolicy.php`

| Ação | Regra |
|---|---|
| `viewAny` | papel `gerente` ou `administrativo` |
| `create` | papel `gerente` ou `administrativo` |
| `update` | **sempre negado** — append-only |
| `delete` | **sempre negado** — append-only |

Registrar ambas as policies em `app/Providers/AuthServiceProvider.php` no array `$policies`.

---

## 8. Livewire Pages

### `LgpdCategoriesPage`

**Arquivo:** `app/Livewire/Pages/Settings/LgpdCategoriesPage.php`

**Rota:** `GET /settings/privacy`

**Middleware:** `auth`, `EnsureTwoFactorChallengeCompleted`

**Pattern:** segue `UsersPage` (slice 009) — `mount()` resolve tenant via `CurrentTenantResolver`, autoriza via `Gate::authorize('create', LgpdCategory::class)`, define `$readOnly` quando tenant `suspended` ou access_mode `read-only`.

Propriedades públicas:
- `$form = ['code' => '', 'name' => '', 'legal_basis' => '', 'retention_policy' => '', 'comment' => '']`

Ações:
- `saveCategory(LgpdCategoryService $service): void` — delega ao service; em `ValidationException` adiciona erros nos campos via `addError()`
- `deleteCategory(LgpdCategoryService $service, string $id): void` — verifica `$readOnly`, delega ao service

`render()`: passa `$categories = LgpdCategory::listForTenant($tenant)` e constantes de enum para a view.

View: `resources/views/livewire/pages/settings/lgpd-categories-page.blade.php`
Layout: `layouts.app`

---

### `ConsentSubjectsPage`

**Arquivo:** `app/Livewire/Pages/Settings/ConsentSubjectsPage.php`

**Rota:** `GET /settings/privacy/consentimentos`

**Middleware:** `auth`, `EnsureTwoFactorChallengeCompleted`

Propriedades públicas:
- `#[Url] public string $statusFilter = ''` — valores: `ativo|revogado|nao_informado|''`

`render()`:
- Query: `ConsentSubject::query()` com eager load do último `consentRecord` por canal
- Aplica `withConsentStatus($statusFilter)` quando não vazio
- Pagina com `->paginate(50)`
- Exibe: identificador opaco do subject (UUID truncado), canal, status, data da última mudança — sem PII crua (AC-SEC-003)

View: `resources/views/livewire/pages/settings/consent-subjects-page.blade.php`
Layout: `layouts.app`

---

### `RevokeConsentPage`

**Arquivo:** `app/Livewire/Pages/Privacy/RevokeConsentPage.php`

**Rota:** `GET /privacy/revoke/{token}` (grupo `web` sem `auth`)

Propriedades públicas:
- `public string $rawToken`
- `public bool $confirmed = false`
- `public bool $alreadyRevoked = false`
- `public bool $expired = false`
- `public bool $invalid = false`
- `public string $selectedReason = 'other_without_details'`

Ações:
- `mount(string $token, RevocationTokenService $service): void`
  - `$this->rawToken = $token`
  - `$this->tokenModel = $service->validate($token)`
  - Se `null` → `$this->invalid = true` (view renderiza HTTP 404 via `abort(404)`)
  - Se expirado (token existe mas `expires_at` no passado e `used_at` null) → `$this->expired = true`, dispara `expireAndRegenerate` + envia novo `RevocationLinkMail`
- `confirm(ConsentRecordService $consentService, RevocationTokenService $tokenService): void`
  - Executa `$consentService->revoke(...)` com `$this->selectedReason`
  - `$tokenService->consume($this->tokenModel)`
  - Dispatch de `RevocationConfirmationMail`
  - `$this->confirmed = true`

View: `resources/views/livewire/pages/privacy/revoke-consent-page.blade.php`
Layout: `layouts.guest`

---

## 9. Mailables

### `RevocationLinkMail`

**Arquivo:** `app/Mail/RevocationLinkMail.php`

- Implementa `ShouldQueue`
- `$tries = 3`
- Construtor: `ConsentSubject $subject`, `string $channel`, `string $rawToken`
- URL: `route('privacy.revoke', ['token' => $rawToken])`
- View: `resources/views/emails/revocation-link.blade.php`

### `RevocationConfirmationMail`

**Arquivo:** `app/Mail/RevocationConfirmationMail.php`

- Implementa `ShouldQueue`
- `$tries = 3`
- Construtor: `ConsentSubject $subject`, `string $channel`, `Carbon $revokedAt`
- Exibe data/hora UTC do evento formatada (AC-007)
- View: `resources/views/emails/revocation-confirmation.blade.php`

---

## 10. Rotas

**Arquivo modificado:** `routes/web.php`

```php
use App\Livewire\Pages\Settings\LgpdCategoriesPage;
use App\Livewire\Pages\Settings\ConsentSubjectsPage;
use App\Livewire\Pages\Privacy\RevokeConsentPage;

// Grupo autenticado + 2FA — dentro do grupo existente de settings
Route::middleware(['auth', 'ensure.two.factor'])
    ->prefix('settings/privacy')
    ->group(function (): void {
        Route::get('/', LgpdCategoriesPage::class)->name('settings.privacy');
        Route::get('/consentimentos', ConsentSubjectsPage::class)->name('settings.privacy.consents');
    });

// Rota pública de revogação — fora do grupo autenticado
Route::get('/privacy/revoke/{token}', RevokeConsentPage::class)
    ->middleware('web')
    ->name('privacy.revoke');
```

O alias `ensure.two.factor` aponta para `EnsureTwoFactorChallengeCompleted`, já registrado em `bootstrap/app.php` desde o slice 007. **Nenhum middleware novo é criado.**

---

## 11. Novos arquivos

- `database/migrations/2026_04_15_000400_create_lgpd_categories_table.php`
- `database/migrations/2026_04_15_000410_create_consent_subjects_table.php`
- `database/migrations/2026_04_15_000420_create_consent_records_table.php` (inclui trigger)
- `database/migrations/2026_04_15_000430_create_revocation_tokens_table.php`
- `app/Models/LgpdCategory.php`
- `app/Models/ConsentSubject.php`
- `app/Models/ConsentRecord.php`
- `app/Models/RevocationToken.php`
- `database/factories/LgpdCategoryFactory.php`
- `database/factories/ConsentSubjectFactory.php`
- `database/factories/ConsentRecordFactory.php`
- `database/factories/RevocationTokenFactory.php`
- `app/Support/Lgpd/LgpdCategoryService.php`
- `app/Support/Lgpd/ConsentRecordService.php`
- `app/Support/Lgpd/RevocationTokenService.php`
- `app/Policies/LgpdCategoryPolicy.php`
- `app/Policies/ConsentRecordPolicy.php`
- `app/Livewire/Pages/Settings/LgpdCategoriesPage.php`
- `app/Livewire/Pages/Settings/ConsentSubjectsPage.php`
- `app/Livewire/Pages/Privacy/RevokeConsentPage.php`
- `resources/views/livewire/pages/settings/lgpd-categories-page.blade.php`
- `resources/views/livewire/pages/settings/consent-subjects-page.blade.php`
- `resources/views/livewire/pages/privacy/revoke-consent-page.blade.php`
- `app/Mail/RevocationLinkMail.php`
- `app/Mail/RevocationConfirmationMail.php`
- `resources/views/emails/revocation-link.blade.php`
- `resources/views/emails/revocation-confirmation.blade.php`
- `tests/Feature/Slice010/LgpdCategoriesPageTest.php`
- `tests/Feature/Slice010/ConsentBlockingTest.php`
- `tests/Feature/Slice010/ConsentRecordTest.php`
- `tests/Feature/Slice010/RevocationTokenTest.php`
- `tests/Feature/Slice010/ConsentSubjectsPageTest.php`
- `tests/Feature/Slice010/AuditAppendOnlyTest.php`

## 12. Arquivos modificados

- `routes/web.php` — adicionar rotas `/settings/privacy*` e `/privacy/revoke/{token}`
- `app/Providers/AuthServiceProvider.php` — registrar `LgpdCategoryPolicy` e `ConsentRecordPolicy`

---

## 13. Layout de testes

**Diretório:** `tests/Feature/Slice010/`

### `LgpdCategoriesPageTest.php`
Cobre: AC-001, AC-001a, AC-001b, AC-SEC-002

Casos obrigatórios:
- Gerente com 2FA salva base legal → linha em `lgpd_categories` com `tenant_id` correto, `created_by_user_id`, `legal_basis`, timestamp UTC
- Tentativa de 5ª base na mesma categoria → `ValidationException` / 422, zero linhas novas criadas
- Acesso sem 2FA completo → redirect para `/auth/two-factor-challenge` sem gravar
- Tenant B não vê categorias do tenant A (escopo global scope)
- HTML/JS em `comment` → `strip_tags()` aplicado antes de persistir

### `ConsentBlockingTest.php`
Cobre: AC-002, AC-002a

Casos obrigatórios:
- Tenant sem `lgpd_categories` → `grant()` lança `HttpException` 422 com mensagem correta
- Tenant `suspended` → bloqueia antes da verificação LGPD, mensagem de tenant suspenso

### `ConsentRecordTest.php`
Cobre: AC-003, AC-003a, AC-003b, AC-005, AC-SEC-001, AC-SEC-003

Casos obrigatórios:
- Opt-in com checkbox marcado → grava `status=ativo` com `ip_address`, `user_agent_hash` SHA-256 de 64 chars, `granted_at` UTC
- Opt-in sem checkbox → não grava; estado permanece `nao_informado`
- Segundo opt-in para canal já ativo → novo registro (append); `canReceiveOn` usa o mais recente
- `canReceiveOn('email')` retorna `true` com ativo, `false` com revogado mais recente, `false` sem registro
- HTML/JS em campos livres → `strip_tags()` aplicado antes de persistir
- `ConsentRecord` não contém colunas com `name`, `email`, `whatsapp`, `cpf` (verificação de schema)

### `RevocationTokenTest.php`
Cobre: AC-004, AC-004a, AC-004b, AC-007, AC-007a, AC-SEC-004, AC-SEC-005

Casos obrigatórios:
- `generate()` → `token_hash` em banco é SHA-256 do raw; raw nunca persiste na tabela
- `validate()` retorna model para token válido; `null` para inválido ou expirado
- `validate()` com hash adulterado retorna `null` (sem branch diferente por tipo de falha)
- Token expirado → `valid()` scope não retorna; fluxo gera novo token e envia novo e-mail
- Token inválido na rota pública → `abort(404)` sem revelar existência de subject
- Titular sem consentimento ativo revoga → mensagem informativa sem criar `ConsentRecord`
- Após `confirm()` → `RevocationConfirmationMail` dispatchado com timestamp UTC correto

### `ConsentSubjectsPageTest.php`
Cobre: AC-006

Casos obrigatórios:
- Gerente acessa `/settings/privacy/consentimentos` → tabela paginada com 50 registros por página
- Filtro por `statusFilter=ativo` → retorna apenas subjects com consentimento ativo
- Filtro por `statusFilter=revogado` → retorna apenas subjects com revogação
- Sem filtro → retorna todos os subjects do tenant (não de outros tenants)
- Colunas exibidas não contêm PII crua (sem email, phone em claro na linha da tabela)

### `AuditAppendOnlyTest.php`
Cobre: AC-008, AC-008a

Casos obrigatórios:
- `DB::update('UPDATE consent_records SET status = ...')` → lança exceção com mensagem `audit append-only`
- `DB::delete('DELETE FROM consent_records WHERE id = ...')` → lança exceção
- `DB::statement('TRUNCATE consent_records')` → lança exceção
- `ConsentRecord::create([...])` → funciona normalmente (INSERT deve ser permitido)

---

## 14. Sequenciamento de implementação

### Task 1 — Migrations, models e factories

**Commit test RED:** `test(slice-010): AC-008/AC-008a trigger append-only red`
- Criar `tests/Feature/Slice010/AuditAppendOnlyTest.php`

**Commit impl:** `feat(slice-010): migrations LGPD + models + factories`
- Quatro migrations (ordem: lgpd_categories → consent_subjects → consent_records → revocation_tokens)
- Quatro models com `ScopesToCurrentTenant`
- Quatro factories

**Commit test GREEN:** `test(slice-010): AC-008/AC-008a trigger append-only green`

---

### Task 2 — Services, Policies e Mailables

**Commit test RED:** `test(slice-010): AC-001..005/SEC services red`
- Criar `tests/Feature/Slice010/LgpdCategoriesPageTest.php` (casos de service/policy)
- Criar `tests/Feature/Slice010/ConsentBlockingTest.php`
- Criar `tests/Feature/Slice010/ConsentRecordTest.php`
- Criar `tests/Feature/Slice010/RevocationTokenTest.php`

**Commit impl:** `feat(slice-010): services LGPD + policies + mailables`
- `LgpdCategoryService`, `ConsentRecordService`, `RevocationTokenService`
- `LgpdCategoryPolicy`, `ConsentRecordPolicy` + registro em `AuthServiceProvider`
- `RevocationLinkMail`, `RevocationConfirmationMail` + views de e-mail

**Commit test GREEN:** `test(slice-010): AC-001..005/SEC services green`

---

### Task 3 — Livewire pages, rotas e telas

**Commit test RED:** `test(slice-010): AC-001a/001b/006/007/007a UI red`
- Complementar `tests/Feature/Slice010/LgpdCategoriesPageTest.php` (casos de middleware/UI)
- Criar `tests/Feature/Slice010/ConsentSubjectsPageTest.php`

**Commit impl:** `feat(slice-010): Livewire pages LGPD + rotas`
- `LgpdCategoriesPage`, `ConsentSubjectsPage`, `RevokeConsentPage`
- Views Blade correspondentes
- Rotas em `routes/web.php`

**Commit test GREEN:** `test(slice-010): AC-001a/001b/006/007/007a UI green`

---

## 15. Dependências de slices anteriores

| Dependência | Fornecida por | Detalhe |
|---|---|---|
| Tabela `tenants` com coluna `status` | slice-008 | verificação de tenant suspenso (AC-002a) |
| Tabela `tenant_users` com `role` e `requires_2fa` | slice-008 | resolução de contexto no `mount()` |
| Middleware `EnsureTwoFactorChallengeCompleted` | slice-007 | reforço 2FA em `/settings/privacy*` |
| Trait `ScopesToCurrentTenant` | slice-008 | global scope `tenant_id` em todos os models |
| `CurrentTenantResolver` | slice-008 | resolução de tenant ativo no `mount()` |
| Papéis `gerente`, `administrativo`, `visualizador` | slice-009 | autorização via Gate/Policy |
| Queue worker (Redis) | slice-008 | dispatch de mailables queued |

**Acoplamento futuro (E03):** quando o slice que cria `Contact` for implementado, deve adicionar `$table->foreignUuid('consent_subject_id')->nullable()->constrained('consent_subjects')` na migration de `contacts` e usar `subject_type='contact'` ao criar `ConsentSubject` vinculado.

---

## 16. Riscos técnicos e mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| Trigger PostgreSQL bloqueia migrations futuras de limpeza de dados de teste | média | médio | Documentar padrão `SET LOCAL session_replication_role = replica` em `docs/architecture/patterns/append-only-tables.md`; `AuditAppendOnlyTest` valida que INSERT funciona normalmente |
| `random_bytes` mock em testes que precisam de token previsível | baixa | baixo | Usar `random_bytes` real nos testes; criar `ConsentSubject` + `RevocationToken` via factory com `token_hash` fixo para cenários de validação |
| Crescimento rápido de `consent_records` sem particionamento | baixa (MVP) | alto (produção futura) | Índice composto `(consent_subject_id, channel, created_at)` desde a migration; anotar no ERD que partition by range de `created_at` é candidata quando volume superar 1 M linhas |
| E-mail de revogação falha silenciosamente | média | médio | `$tries = 3` nos Mailables; coluna `failed_mail_at` em `revocation_tokens` pode ser adicionada em slice futuro para alertar o gerente na tela |
| Enum de `revocation_reason` estreito para casos reais | baixa | baixo | Valor `other_without_details` cobre o residual sem PII; refinamento futuro via migration `ALTER TABLE` sem impacto em registros existentes |

---

## 17. Fora de escopo deste plano (confirmando spec)

- Interface do titular para exercer direitos LGPD complexos (Art. 18 II/III/VI) — E09
- Acoplamento com `Contact` (E03) — slice de `Contact` adiciona FK
- DPO / encarregado por dados — documental, fora do MVP
- Exportação de dados para titular (Art. 18.V) — E09
- Relatório de impacto à proteção de dados (RIPD)
- Cookies e pixel tracking
- ADR novo — nenhuma decisão deste slice afeta estratégia de stack, tenancy ou autenticação além do já coberto por ADR-0001 e ADR-0004
