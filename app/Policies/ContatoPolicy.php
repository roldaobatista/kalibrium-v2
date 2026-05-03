<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class ContatoPolicy
{
    public function viewAny(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canReadContatos((string) $tenantUser->role);
    }

    public function view(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canReadContatos((string) $tenantUser->role);
    }

    public function create(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canWriteContatos((string) $tenantUser->role);
    }

    public function update(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canWriteContatos((string) $tenantUser->role);
    }

    public function delete(User $user, TenantUser $tenantUser): bool
    {
        return $this->isActiveWithRole($user, $tenantUser)
            && TenantRole::canWriteContatos((string) $tenantUser->role);
    }

    private function isActiveWithRole(User $user, TenantUser $tenantUser): bool
    {
        return (int) $tenantUser->user_id === (int) $user->id
            && $tenantUser->status->value === 'active';
    }
}
