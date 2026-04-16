<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class ClientePolicy
{
    /**
     * Determine if the user can create clientes.
     * Spec role "atendente" maps to operational roles (gerente, tecnico, administrativo).
     */
    public function create(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveOperational($user, $tenantUser);
    }

    /**
     * Determine if the user can deactivate (soft-delete) clientes.
     */
    public function delete(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveOperational($user, $tenantUser);
    }

    private function isActiveOperational(User $user, TenantUser $tenantUser): bool
    {
        return (int) $tenantUser->user_id === (int) $user->id
            && strtolower((string) $tenantUser->status) === 'active'
            && TenantRole::canManageClientes((string) $tenantUser->role);
    }
}
