<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class TenantSettingsPolicy
{
    public function manageUsers(User $user, TenantUser $tenantUser): bool
    {
        return $this->sameActiveBinding($user, $tenantUser)
            && TenantRole::canManageUsers((string) $tenantUser->role);
    }

    public function viewPlan(User $user, TenantUser $tenantUser): bool
    {
        return $this->sameActiveBinding($user, $tenantUser)
            && TenantRole::canViewPlan((string) $tenantUser->role);
    }

    public function requestPlanUpgrade(User $user, TenantUser $tenantUser): bool
    {
        return $this->sameActiveBinding($user, $tenantUser)
            && TenantRole::canRequestPlanUpgrade((string) $tenantUser->role);
    }

    private function sameActiveBinding(User $user, TenantUser $tenantUser): bool
    {
        return (int) $tenantUser->user_id === (int) $user->id
            && $tenantUser->status->value === 'active';
    }
}
