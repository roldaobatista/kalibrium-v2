<?php

declare(strict_types=1);

namespace App\Support\Settings\Concerns;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

trait AuthorizesTenantSettings
{
    /**
     * @throws AuthorizationException
     */
    private function assertActiveManager(User $actor, TenantUser $actorTenantUser, bool $allowTrial = true): Tenant
    {
        $fresh = TenantUser::query()
            ->with('tenant')
            ->whereKey($actorTenantUser->id)
            ->where('user_id', $actor->id)
            ->where('tenant_id', $actorTenantUser->tenant_id)
            ->where('status', 'active')
            ->first();

        if ($fresh === null || $fresh->tenant === null) {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }

        Gate::forUser($actor)->authorize('tenant-users.manage', $fresh);

        $tenantStatus = strtolower((string) $fresh->tenant->status);
        if (! in_array($tenantStatus, $allowTrial ? ['active', 'trial', 'suspended'] : ['active', 'trial'], true)) {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }

        if (session('tenant.access_mode') === 'read-only' || $tenantStatus === 'suspended') {
            throw new AuthorizationException('Conta em modo somente leitura.');
        }

        if (((bool) $fresh->requires_2fa || TenantRole::requiresTwoFactor((string) $fresh->role))
            && $actor->two_factor_confirmed_at === null) {
            throw new AuthorizationException('Conclua a verificação em duas etapas.');
        }

        return $fresh->tenant;
    }

    /**
     * @throws AuthorizationException
     */
    private function assertSameTenant(TenantUser $actorTenantUser, TenantUser $target): void
    {
        if ((int) $actorTenantUser->tenant_id !== (int) $target->tenant_id) {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }
    }

    private function lockActiveManagerSetForTenant(int $tenantId): int
    {
        DB::table('tenants')
            ->where('id', $tenantId)
            ->lockForUpdate()
            ->first(['id']);

        $activeManagers = TenantUser::query()
            ->where('tenant_id', $tenantId)
            ->where('role', 'gerente')
            ->where('status', 'active')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id'])
            ->all();

        return count($activeManagers);
    }
}
