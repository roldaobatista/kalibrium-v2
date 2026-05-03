<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class ClientePolicy
{
    /**
     * Determine if the user can list clientes (GET /clientes).
     */
    public function viewAny(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canReadClientes((string) $tenantUser->role);
    }

    /**
     * Determine if the user can view a single cliente (GET /clientes/{id}).
     */
    public function view(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canReadClientes((string) $tenantUser->role);
    }

    /**
     * Determine if the user can create clientes.
     * Previously used canManageClientes (which includes tecnico).
     * Now uses canWriteClientes (excludes tecnico).
     */
    public function create(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canWriteClientes((string) $tenantUser->role);
    }

    /**
     * Determine if the user can update clientes (PUT /clientes/{id}).
     */
    public function update(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canWriteClientes((string) $tenantUser->role);
    }

    /**
     * Determine if the user can deactivate (soft-delete) clientes.
     * Previously used canManageClientes (which includes tecnico).
     * Now uses canWriteClientes (excludes tecnico).
     */
    public function delete(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canWriteClientes((string) $tenantUser->role);
    }

    /**
     * Checks that the TenantUser record belongs to the authenticated user
     * and that the account is in active status.
     * Role check is done separately in each policy method.
     */
    private function isActiveWithRole(User $user, TenantUser $tenantUser): bool
    {
        return (int) $tenantUser->user_id === (int) $user->id
            && $tenantUser->status->value === 'active';
    }
}
