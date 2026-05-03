<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TenantUserStatus;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class TechnicianPolicy
{
    public function viewAny(User $user, ?TenantUser $tenantUser): bool
    {
        return $this->isActiveManager($user, $tenantUser);
    }

    public function create(User $user, ?TenantUser $tenantUser): bool
    {
        return $this->isActiveManager($user, $tenantUser);
    }

    public function update(User $user, ?TenantUser $tenantUser): bool
    {
        return $this->isActiveManager($user, $tenantUser);
    }

    public function deactivate(User $user, ?TenantUser $tenantUser): bool
    {
        return $this->isActiveManager($user, $tenantUser);
    }

    private function isActiveManager(User $user, ?TenantUser $tenantUser): bool
    {
        if ($tenantUser === null) {
            return false;
        }

        return (int) $tenantUser->user_id === (int) $user->id
            && $tenantUser->status === TenantUserStatus::Active
            && strtolower((string) $tenantUser->role) === TenantRole::MANAGER;
    }
}
