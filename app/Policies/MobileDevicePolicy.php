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
     */
    public function viewAny(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveManager($user, $tenantUser);
    }

    /**
     * Apenas gerentes podem aprovar, recusar ou bloquear celulares.
     */
    public function manage(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveManager($user, $tenantUser);
    }

    private function isActiveManager(User $user, TenantUser $tenantUser): bool
    {
        return (int) $tenantUser->user_id === (int) $user->id
            && strtolower((string) $tenantUser->status) === 'active'
            && strtolower((string) $tenantUser->role) === TenantRole::MANAGER;
    }
}
