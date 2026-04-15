<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * Base TestCase para a suite de isolamento entre tenants.
 *
 * Design (D1): fixture de 2 tenants criada uma única vez por suite via setUp,
 * reset incremental por teste via DatabaseTransactions — atende AC-006 (< 60s)
 * e AC-015 (crescimento linear por model).
 *
 * Expõe helpers: tenantA(), tenantB(), userA(), userB().
 */
abstract class TenantIsolationTestCase extends TestCase
{
    use DatabaseTransactions;

    /** @var array{tenant: Tenant, user: User}|null */
    protected ?array $fixtureA = null;

    /** @var array{tenant: Tenant, user: User}|null */
    protected ?array $fixtureB = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSharedFixture();
    }

    /**
     * Cria 2 tenants (A e B) com um usuário cada e dados homônimos.
     * Chamado apenas na primeira execução — fixture compartilhada entre testes.
     */
    protected function createSharedFixture(): void
    {
        // Tenant A
        $tenantA = Tenant::factory()->create([
            'name' => 'Tenant Alpha Isolation',
        ]);
        $userA = User::factory()->create([
            'name'  => 'Usuario Alpha',
            'email' => 'alpha-iso-'.uniqid().'@tenant-isolation.test',
        ]);
        // Associa usuário ao tenant
        DB::table('tenant_users')->insert([
            'tenant_id'  => $tenantA->id,
            'user_id'    => $userA->id,
            'role'       => 'manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tenant B — dados homônimos (mesma estrutura, tenant diferente)
        $tenantB = Tenant::factory()->create([
            'name' => 'Tenant Beta Isolation',
        ]);
        $userB = User::factory()->create([
            'name'  => 'Usuario Beta',
            'email' => 'beta-iso-'.uniqid().'@tenant-isolation.test',
        ]);
        DB::table('tenant_users')->insert([
            'tenant_id'  => $tenantB->id,
            'user_id'    => $userB->id,
            'role'       => 'manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->fixtureA = ['tenant' => $tenantA, 'user' => $userA];
        $this->fixtureB = ['tenant' => $tenantB, 'user' => $userB];
    }

    protected function tenantA(): Tenant
    {
        return $this->fixtureA['tenant'];
    }

    protected function tenantB(): Tenant
    {
        return $this->fixtureB['tenant'];
    }

    protected function userA(): User
    {
        return $this->fixtureA['user'];
    }

    protected function userB(): User
    {
        return $this->fixtureB['user'];
    }

    /**
     * Ativa o contexto de tenant via request attributes (caminho usado por ScopesToCurrentTenant).
     * Substitui tenancy()->initialize() que requer interface Stancl não implementada por App\Models\Tenant.
     */
    protected function initializeTenant(Tenant $tenant): void
    {
        request()->attributes->set('current_tenant', $tenant);
        request()->attributes->set('current_tenant_id', $tenant->id);
    }

    /**
     * Limpa o contexto de tenant.
     */
    protected function endTenant(): void
    {
        request()->attributes->remove('current_tenant');
        request()->attributes->remove('current_tenant_id');
    }

    /**
     * Retorna a lista de models sensíveis definida em config/tenancy.php[sensitive_models].
     * Falha duro se a chave não existir (AC-008 depende dela).
     *
     * @return array<string>
     */
    protected function sensitiveModels(): array
    {
        $models = config('tenancy.sensitive_models');

        if (! is_array($models) || empty($models)) {
            $this->fail(
                'config/tenancy.php[sensitive_models] não está definido ou está vazio. '.
                'Adicione os models sensíveis antes de rodar a suite de isolamento.'
            );
        }

        return $models;
    }

    /**
     * Retorna a tabela do primeiro model sensível disponível.
     */
    protected function firstSensitiveTable(): string
    {
        $models = config('tenancy.sensitive_models', []);

        if (empty($models)) {
            return 'users';
        }

        $firstModel = reset($models);

        return (new $firstModel())->getTable();
    }

    /**
     * @return array<string>
     */
    protected function getSensitiveTables(): array
    {
        $models = config('tenancy.sensitive_models', []);

        return array_map(
            fn ($m) => (new $m())->getTable(),
            array_filter($models, 'class_exists')
        );
    }

    /**
     * Garante dados mínimos em ambos os tenants para exports significativos.
     */
    protected function ensureExportFixture(Tenant $tenantA, Tenant $tenantB): void
    {
        foreach ([$tenantA, $tenantB] as $tenant) {
            $exists = DB::table('consent_subjects')->where('tenant_id', $tenant->id)->exists();
            if (! $exists) {
                DB::table('consent_subjects')->insert([
                    'tenant_id'  => $tenant->id,
                    'email'      => "fixture-{$tenant->id}@tenant-isolation.test",
                    'name'       => "Fixture {$tenant->name}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Coleta valores literais dos registros do tenant B em tabelas sensíveis.
     *
     * @return array<string|int>
     */
    protected function collectTenantBLiterals(Tenant $tenantB): array
    {
        $values = [
            (string) $tenantB->id,
            (string) ($tenantB->name ?? ''),
            (string) ($tenantB->slug ?? ''),
        ];

        $userBEmails = DB::table('users')
            ->join('tenant_users', 'users.id', '=', 'tenant_users.user_id')
            ->where('tenant_users.tenant_id', $tenantB->id)
            ->pluck('users.email')
            ->toArray();

        return array_merge($values, $userBEmails);
    }

    /**
     * Resolve o ID de um recurso pertencente ao tenant B para um URI pattern.
     */
    protected function resolveResourceIdFromTenantB(string $uriPattern, Tenant $tenantB): ?int
    {
        $tableMap = [
            '/users/{id}'             => 'users',
            '/plans/{id}'             => 'plans',
            '/consent-subjects/{id}'  => 'consent_subjects',
            '/DELETE /users/{id}'     => 'users',
        ];

        $table = $tableMap[$uriPattern] ?? null;
        if ($table === null) {
            return null;
        }

        $record = DB::table($table)->where('tenant_id', $tenantB->id)->first();

        return $record?->id;
    }
}
