# Migration Spec — E03 Cadastro Core

> **Status:** draft
> **Data:** 2026-04-15
> **Épico:** E03 — Cadastro Core
> **Pré-requisito:** todas as migrations de E02 aplicadas (tenants, companies, branches, tenant_users, roles, lgpd_categories, consent_subjects, consent_records)

---

## 1. Ordem de execução

As migrations devem ser aplicadas nesta sequência exata, pois há dependências de FK entre elas.

| Ordem | Arquivo | Tabela | Depende de |
|---|---|---|---|
| 1 | `2026_04_16_000100_create_clientes_table.php` | `clientes` | `tenants`, `tenant_users` |
| 2 | `2026_04_16_000110_create_contatos_table.php` | `contatos` | `clientes`, `tenant_users` |
| 3 | `2026_04_16_000120_create_consentimentos_contato_table.php` | `consentimentos_contato` | `contatos` |
| 4 | `2026_04_16_000130_create_instrumentos_table.php` | `instrumentos` | `clientes`, `tenant_users` |
| 5 | `2026_04_16_000140_create_padroes_referencia_table.php` | `padroes_referencia` | `tenants`, `tenant_users` (self-ref na própria tabela) |
| 6 | `2026_04_16_000150_create_procedimentos_calibracao_table.php` | `procedimentos_calibracao` | `tenants`, `tenant_users` |
| 7 | *(publicar migration do pacote)* | `audits` | — (owen-it/laravel-auditing) |

---

## 2. Especificação por migration

### Migration 1 — `create_clientes_table`

**Arquivo:** `2026_04_16_000100_create_clientes_table.php`

```
Schema::create('clientes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
    $table->string('tipo_pessoa', 2);                    // CHECK 'PF'|'PJ'
    $table->string('documento', 18);                     // CPF (14 c/ máscara) ou CNPJ (18)
    $table->string('razao_social');
    $table->string('nome_fantasia')->nullable();
    $table->string('regime_tributario', 20)->nullable(); // CHECK: simples|presumido|real|mei|isento
    $table->decimal('limite_credito', 15, 2)->nullable(); // CHECK >= 0
    $table->string('logradouro')->nullable();
    $table->string('numero', 20)->nullable();
    $table->string('complemento', 100)->nullable();
    $table->string('bairro', 100)->nullable();
    $table->string('cidade', 100)->nullable();
    $table->char('uf', 2)->nullable();
    $table->string('cep', 8)->nullable();
    $table->string('telefone', 20)->nullable();
    $table->string('email', 254)->nullable();
    $table->text('observacoes')->nullable();
    $table->boolean('ativo')->default(true);
    $table->foreignId('created_by')->constrained('tenant_users')->restrictOnDelete();
    $table->foreignId('updated_by')->constrained('tenant_users')->restrictOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'razao_social']);
    $table->index(['tenant_id', 'ativo']);
    $table->index('deleted_at');
});
```

**Constraints adicionais (PostgreSQL raw):**
```sql
ALTER TABLE clientes
    ADD CONSTRAINT clientes_tipo_pessoa_check
        CHECK (tipo_pessoa IN ('PF', 'PJ')),
    ADD CONSTRAINT clientes_regime_tributario_check
        CHECK (regime_tributario IN ('simples','presumido','real','mei','isento') OR regime_tributario IS NULL),
    ADD CONSTRAINT clientes_limite_credito_check
        CHECK (limite_credito >= 0 OR limite_credito IS NULL);

CREATE UNIQUE INDEX clientes_tenant_documento_unique
    ON clientes (tenant_id, documento)
    WHERE deleted_at IS NULL;

ALTER TABLE clientes ENABLE ROW LEVEL SECURITY;
ALTER TABLE clientes FORCE ROW LEVEL SECURITY;
CREATE POLICY clientes_tenant_isolation ON clientes
    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
```

**Rollback:** `Schema::dropIfExists('clientes')` — remover também policy e index antes do drop.

---

### Migration 2 — `create_contatos_table`

**Arquivo:** `2026_04_16_000110_create_contatos_table.php`

```
Schema::create('contatos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
    $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
    $table->string('nome');
    $table->string('email', 254)->nullable();
    $table->string('whatsapp', 20)->nullable();
    $table->string('papel', 30);                         // CHECK: comprador|responsavel_tecnico|financeiro|outro
    $table->boolean('principal')->default(false);
    $table->boolean('ativo')->default(true);
    $table->foreignId('created_by')->constrained('tenant_users')->restrictOnDelete();
    $table->foreignId('updated_by')->constrained('tenant_users')->restrictOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'cliente_id']);
    $table->index(['cliente_id', 'ativo']);
    $table->index(['tenant_id', 'papel']);
    $table->index('deleted_at');
});
```

**Constraints adicionais (PostgreSQL raw):**
```sql
ALTER TABLE contatos
    ADD CONSTRAINT contatos_papel_check
        CHECK (papel IN ('comprador','responsavel_tecnico','financeiro','outro'));

ALTER TABLE contatos ENABLE ROW LEVEL SECURITY;
ALTER TABLE contatos FORCE ROW LEVEL SECURITY;
CREATE POLICY contatos_tenant_isolation ON contatos
    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
```

**Rollback:** `Schema::dropIfExists('contatos')`

---

### Migration 3 — `create_consentimentos_contato_table`

**Arquivo:** `2026_04_16_000120_create_consentimentos_contato_table.php`

> Tabela append-only. Sem `updated_at`. Trigger bloqueia UPDATE/DELETE/TRUNCATE.

```
Schema::create('consentimentos_contato', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
    $table->foreignId('contato_id')->constrained('contatos')->restrictOnDelete();
    $table->string('canal', 20);                         // CHECK: email|whatsapp
    $table->string('status', 20);                        // CHECK: concedido|revogado
    $table->timestampTz('concedido_em')->nullable();
    $table->timestampTz('revogado_em')->nullable();
    $table->string('ip_origem', 45)->nullable();
    $table->string('motivo_revogacao', 100)->nullable();
    $table->timestamp('created_at')->nullable();         // sem updated_at

    $table->index(['contato_id', 'canal', 'created_at']);
    $table->index(['tenant_id', 'contato_id', 'canal', 'status']);
});
```

**Constraints e trigger (PostgreSQL raw):**
```sql
ALTER TABLE consentimentos_contato
    ADD CONSTRAINT consentimentos_canal_check
        CHECK (canal IN ('email','whatsapp')),
    ADD CONSTRAINT consentimentos_status_check
        CHECK (status IN ('concedido','revogado'));

CREATE OR REPLACE FUNCTION consentimentos_contato_append_only()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    RAISE EXCEPTION 'append-only: operacao proibida em consentimentos_contato';
END;
$$;

CREATE TRIGGER consentimentos_contato_append_only_trigger
    BEFORE UPDATE OR DELETE OR TRUNCATE ON consentimentos_contato
    FOR EACH STATEMENT EXECUTE FUNCTION consentimentos_contato_append_only();

ALTER TABLE consentimentos_contato ENABLE ROW LEVEL SECURITY;
ALTER TABLE consentimentos_contato FORCE ROW LEVEL SECURITY;
CREATE POLICY consentimentos_contato_tenant_isolation ON consentimentos_contato
    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
```

**Rollback:**
```sql
DROP TRIGGER IF EXISTS consentimentos_contato_append_only_trigger ON consentimentos_contato;
DROP FUNCTION IF EXISTS consentimentos_contato_append_only();
```
Depois: `Schema::dropIfExists('consentimentos_contato')`

---

### Migration 4 — `create_instrumentos_table`

**Arquivo:** `2026_04_16_000130_create_instrumentos_table.php`

```
Schema::create('instrumentos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
    $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
    $table->string('descricao');
    $table->string('fabricante', 100)->nullable();
    $table->string('modelo', 100)->nullable();
    $table->string('numero_serie', 100);
    $table->string('dominio_metrologico', 20);           // CHECK: dimensional|pressao|massa|temperatura
    $table->string('faixa_minima', 50)->nullable();
    $table->string('faixa_maxima', 50)->nullable();
    $table->string('unidade_faixa', 20)->nullable();
    $table->string('resolucao', 50)->nullable();
    $table->string('tag_cliente', 100)->nullable();
    $table->boolean('ativo')->default(true);
    $table->foreignId('created_by')->constrained('tenant_users')->restrictOnDelete();
    $table->foreignId('updated_by')->constrained('tenant_users')->restrictOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'dominio_metrologico']);
    $table->index(['cliente_id', 'ativo']);
    $table->index(['tenant_id', 'ativo']);
    $table->index('deleted_at');
});
```

**Constraints adicionais (PostgreSQL raw):**
```sql
ALTER TABLE instrumentos
    ADD CONSTRAINT instrumentos_dominio_check
        CHECK (dominio_metrologico IN ('dimensional','pressao','massa','temperatura'));

CREATE UNIQUE INDEX instrumentos_tenant_serie_unique
    ON instrumentos (tenant_id, numero_serie)
    WHERE deleted_at IS NULL;

ALTER TABLE instrumentos ENABLE ROW LEVEL SECURITY;
ALTER TABLE instrumentos FORCE ROW LEVEL SECURITY;
CREATE POLICY instrumentos_tenant_isolation ON instrumentos
    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
```

**Rollback:** `Schema::dropIfExists('instrumentos')`

---

### Migration 5 — `create_padroes_referencia_table`

**Arquivo:** `2026_04_16_000140_create_padroes_referencia_table.php`

> A self-reference `padrao_anterior_id` é adicionada via `addColumn` após criar a tabela para evitar FK circular no momento do `CREATE TABLE`.

```
Schema::create('padroes_referencia', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
    $table->string('descricao');
    $table->string('fabricante', 100)->nullable();
    $table->string('modelo', 100)->nullable();
    $table->string('numero_serie', 100);
    $table->string('dominio_metrologico', 20);           // CHECK: dimensional|pressao|massa|temperatura
    $table->string('numero_certificado', 100)->nullable();
    $table->date('data_calibracao')->nullable();
    $table->date('data_validade')->nullable();
    $table->string('laboratorio_calibrador', 255)->nullable();
    $table->boolean('vigente')->default(true);
    $table->unsignedBigInteger('padrao_anterior_id')->nullable();
    $table->foreignId('created_by')->constrained('tenant_users')->restrictOnDelete();
    $table->foreignId('updated_by')->constrained('tenant_users')->restrictOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'data_validade']);
    $table->index(['tenant_id', 'vigente']);
    $table->index('padrao_anterior_id');
    $table->index('deleted_at');
});
```

**Constraints adicionais (PostgreSQL raw):**
```sql
ALTER TABLE padroes_referencia
    ADD CONSTRAINT padroes_ref_dominio_check
        CHECK (dominio_metrologico IN ('dimensional','pressao','massa','temperatura')),
    ADD CONSTRAINT padroes_ref_self_fk
        FOREIGN KEY (padrao_anterior_id) REFERENCES padroes_referencia(id) ON DELETE RESTRICT;

CREATE UNIQUE INDEX padroes_ref_tenant_serie_unique
    ON padroes_referencia (tenant_id, numero_serie)
    WHERE deleted_at IS NULL;

ALTER TABLE padroes_referencia ENABLE ROW LEVEL SECURITY;
ALTER TABLE padroes_referencia FORCE ROW LEVEL SECURITY;
CREATE POLICY padroes_referencia_tenant_isolation ON padroes_referencia
    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
```

**Rollback:** `Schema::dropIfExists('padroes_referencia')`

---

### Migration 6 — `create_procedimentos_calibracao_table`

**Arquivo:** `2026_04_16_000150_create_procedimentos_calibracao_table.php`

```
Schema::create('procedimentos_calibracao', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
    $table->string('nome');
    $table->string('versao', 20);
    $table->string('dominio_metrologico', 20);           // CHECK: dimensional|pressao|massa|temperatura
    $table->string('status', 20)->default('rascunho');   // CHECK: rascunho|vigente|obsoleto
    $table->text('descricao')->nullable();
    $table->date('data_vigencia_inicio')->nullable();
    $table->date('data_vigencia_fim')->nullable();
    $table->foreignId('created_by')->constrained('tenant_users')->restrictOnDelete();
    $table->foreignId('updated_by')->constrained('tenant_users')->restrictOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'nome']);
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'dominio_metrologico']);
    $table->index('deleted_at');
});
```

**Constraints adicionais (PostgreSQL raw):**
```sql
ALTER TABLE procedimentos_calibracao
    ADD CONSTRAINT proc_calibracao_dominio_check
        CHECK (dominio_metrologico IN ('dimensional','pressao','massa','temperatura')),
    ADD CONSTRAINT proc_calibracao_status_check
        CHECK (status IN ('rascunho','vigente','obsoleto'));

CREATE UNIQUE INDEX proc_calibracao_nome_versao_unique
    ON procedimentos_calibracao (tenant_id, nome, versao)
    WHERE deleted_at IS NULL;

CREATE UNIQUE INDEX proc_calibracao_vigente_unico
    ON procedimentos_calibracao (tenant_id, nome, dominio_metrologico)
    WHERE status = 'vigente' AND deleted_at IS NULL;

ALTER TABLE procedimentos_calibracao ENABLE ROW LEVEL SECURITY;
ALTER TABLE procedimentos_calibracao FORCE ROW LEVEL SECURITY;
CREATE POLICY proc_calibracao_tenant_isolation ON procedimentos_calibracao
    USING (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint)
    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::bigint);
```

**Rollback:** `Schema::dropIfExists('procedimentos_calibracao')`

---

### Migration 7 — Publicação do pacote owen-it/laravel-auditing

Executar via artisan antes de rodar seeds:
```bash
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="migrations"
php artisan migrate
```

A migration publicada cria a tabela `audits` com colunas polimórficas padrão do pacote. Nenhuma alteração manual é necessária.

---

## 3. Models a registrar em `config/tenancy.php` (sensitive_models)

Após criar os Models, adicionar ao array `sensitive_models`:

```php
App\Models\Cliente::class,
App\Models\Contato::class,
App\Models\ConsentimentoContato::class,
App\Models\Instrumento::class,
App\Models\PadraoReferencia::class,
App\Models\ProcedimentoCalibracao::class,
```

---

## 4. Estratégia de rollback

Ordem inversa das migrations. Atenção:

1. Drop `procedimentos_calibracao` (sem dependentes)
2. Drop `padroes_referencia` — remover constraint self-FK antes: `ALTER TABLE padroes_referencia DROP CONSTRAINT padroes_ref_self_fk`
3. Drop `instrumentos`
4. Drop `consentimentos_contato` — remover trigger e function antes
5. Drop `contatos`
6. Drop `clientes`

Nunca usar `CASCADE` em drop de produção — verificar manualmente ausência de dados referenciados.

---

## 5. Verificações pós-migration

```sql
-- Confirmar RLS ativo em todas as tabelas do E03
SELECT tablename, rowsecurity
FROM pg_tables
WHERE tablename IN (
    'clientes', 'contatos', 'consentimentos_contato',
    'instrumentos', 'padroes_referencia', 'procedimentos_calibracao'
);
-- Esperado: rowsecurity = true em todas

-- Confirmar partial unique indexes
SELECT indexname, indexdef
FROM pg_indexes
WHERE tablename IN ('clientes', 'instrumentos', 'padroes_referencia', 'procedimentos_calibracao')
  AND indexdef LIKE '%WHERE%';

-- Confirmar trigger append-only
SELECT trigger_name, event_object_table
FROM information_schema.triggers
WHERE trigger_name = 'consentimentos_contato_append_only_trigger';
```
