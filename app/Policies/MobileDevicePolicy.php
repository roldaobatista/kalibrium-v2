<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class MobileDevicePolicy
{
    /**
     * Apenas gerentes podem listar celulares dos técnicos.
     *
     * @param  TenantUser|null  $tenantUser  Pode ser null quando o vínculo ainda não existe
     */
    public function viewAny(User $user, ?TenantUser $tenantUser): bool
    {
        if ($tenantUser === null) {
            return false;
        }

        return $this->isActiveManager($user, $tenantUser);
    }

    /**
     * Apenas gerentes podem aprovar, recusar ou bloquear celulares.
     *
     * @param  TenantUser|null  $tenantUser  Pode ser null quando o vínculo ainda não existe
     */
    public function manage(User $user, ?TenantUser $tenantUser): bool
    {
        if ($tenantUser === null) {
            return false;
        }

        return $this->isActiveManager($user, $tenantUser);
    }

    private function isActiveManager(User $user, TenantUser $tenantUser): bool
    {
        return (int) $tenantUser->user_id === (int) $user->id
            && strtolower((string) $tenantUser->status) === 'active'
            && strtolower((string) $tenantUser->role) === TenantRole::MANAGER;
    }
}
