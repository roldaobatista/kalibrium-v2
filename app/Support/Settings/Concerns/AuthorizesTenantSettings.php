<?php

declare(strict_types=1);

namespace App\Support\Settings\Concerns;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

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

        if ($fresh === null || $fresh->tenant === null || strtolower((string) $fresh->role) !== 'gerente') {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }

        $tenantStatus = strtolower((string) $fresh->tenant->status);
        if (! in_array($tenantStatus, $allowTrial ? ['active', 'trial', 'suspended'] : ['active', 'trial'], true)) {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }

        if (session('tenant.access_mode') === 'read-only' || $tenantStatus === 'suspended') {
            throw new AuthorizationException('Conta em modo somente leitura.');
        }

        if ((bool) $fresh->requires_2fa && $actor->two_factor_confirmed_at === null) {
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
}
