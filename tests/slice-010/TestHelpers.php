<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Helpers exclusivos para slice-010 (LGPD).
 * Reutiliza slice009_user_with_tenant_context() para criar gerente autenticado com 2FA.
 * As tabelas referenciadas (lgpd_categories, consent_subjects, consent_records,
 * revocation_tokens) ainda NÃO existem — helpers lançam RuntimeException
 * se a tabela não existir, garantindo falha RED.
 */
function slice010_routes(): array
{
    return [
        'privacy' => '/settings/privacy',
        'subjects' => '/settings/privacy/consentimentos',
        'revoke' => static fn (string $token): string => '/privacy/revoke/'.$token,
    ];
}

function slice010_unique_email(): string
{
    return 'slice010+'.Str::uuid().'@example.com';
}

/**
 * Cria contexto de gerente com 2FA confirmado (reutiliza helper slice-009).
 */
function slice010_manager_context(array $overrides = []): array
{
    return slice009_user_with_tenant_context(array_merge([
        'role' => 'gerente',
        'two_factor_confirmed' => true,
    ], $overrides));
}

/**
 * Garante que a tabela existe; lança RuntimeException se não existir (RED).
 */
function slice010_require_table(string $table): void
{
    if (! Schema::hasTable($table)) {
        throw new RuntimeException(
            "Tabela '{$table}' não existe. Execute as migrations do slice-010 antes de rodar os testes."
        );
    }
}

/**
 * Insere um registro em lgpd_categories via DB::table (ignora Model).
 * Lança se a tabela não existir.
 * Retorna o ID (bigInt) do registro inserido.
 */
function slice010_seed_lgpd_category(Tenant $tenant, User $createdBy, array $overrides = []): int
{
    slice010_require_table('lgpd_categories');

    return DB::table('lgpd_categories')->insertGetId([
        'tenant_id' => $tenant->id,
        'code' => $overrides['code'] ?? 'contato',
        'name' => $overrides['name'] ?? 'Dados de Contato',
        'legal_basis' => $overrides['legal_basis'] ?? 'execucao_contrato',
        'comment' => $overrides['comment'] ?? null,
        'created_by_user_id' => $createdBy->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * Insere um consent_subject via DB::table.
 * Lança se a tabela não existir.
 * Retorna o ID (bigInt) do registro inserido.
 */
function slice010_seed_consent_subject(Tenant $tenant, array $overrides = []): int
{
    slice010_require_table('consent_subjects');

    return DB::table('consent_subjects')->insertGetId([
        'tenant_id' => $tenant->id,
        'subject_type' => $overrides['subject_type'] ?? 'external_user',
        'subject_id' => $overrides['subject_id'] ?? null,
        'email' => $overrides['email'] ?? slice010_unique_email(),
        'phone' => $overrides['phone'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * Insere um consent_record (status ativo) via DB::table.
 * Lança se a tabela não existir.
 * Retorna o ID (bigInt) do registro inserido.
 */
function slice010_seed_consent_record(Tenant $tenant, int $subjectId, array $overrides = []): int
{
    slice010_require_table('consent_records');

    $rawUserAgent = $overrides['user_agent'] ?? 'Mozilla/5.0 (test)';

    return DB::table('consent_records')->insertGetId([
        'tenant_id' => $tenant->id,
        'consent_subject_id' => $subjectId,
        'lgpd_category_id' => $overrides['lgpd_category_id'] ?? null,
        'channel' => $overrides['channel'] ?? 'email',
        'status' => $overrides['status'] ?? 'ativo',
        'granted_at' => $overrides['granted_at'] ?? now(),
        'revoked_at' => $overrides['revoked_at'] ?? null,
        'ip_address' => $overrides['ip_address'] ?? '127.0.0.1',
        'user_agent_hash' => hash('sha256', $rawUserAgent),
        'revocation_reason' => $overrides['revocation_reason'] ?? null,
        'created_at' => $overrides['created_at'] ?? now(),
    ]);
}

/**
 * Gera token raw de revogação e insere revocation_token.
 * Retorna ['id' => int, 'raw_token' => string, 'token_hash' => string].
 */
function slice010_seed_revocation_token(Tenant $tenant, int $subjectId, array $overrides = []): array
{
    slice010_require_table('revocation_tokens');

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    $id = DB::table('revocation_tokens')->insertGetId([
        'tenant_id' => $tenant->id,
        'consent_subject_id' => $subjectId,
        'channel' => $overrides['channel'] ?? 'whatsapp',
        'token_hash' => $tokenHash,
        'expires_at' => $overrides['expires_at'] ?? now()->addDays(30),
        'granted_at' => $overrides['granted_at'] ?? now(),
        'used_at' => $overrides['used_at'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'id' => $id,
        'raw_token' => $rawToken,
        'token_hash' => $tokenHash,
    ];
}
