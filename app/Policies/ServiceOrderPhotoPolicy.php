<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceOrderPhoto;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class ServiceOrderPhotoPolicy
{
    /**
     * Técnico só vê foto da própria OS.
     * Gerente vê foto de OS de qualquer técnico do tenant.
     */
    public function view(User $user, ServiceOrderPhoto $photo): bool
    {
        $tenantUser = $this->tenantUserFor($user, (int) $photo->tenant_id);
        if ($tenantUser === null) {
            return false;
        }

        if (strtolower((string) $tenantUser->role) === TenantRole::MANAGER) {
            return true;
        }

        // Técnico: só pode ver foto da própria OS
        return (int) $photo->user_id === (int) $user->id;
    }

    /**
     * Apenas o técnico que criou a foto pode removê-la (soft-delete).
     */
    public function delete(User $user, ServiceOrderPhoto $photo): bool
    {
        $tenantUser = $this->tenantUserFor($user, (int) $photo->tenant_id);
        if ($tenantUser === null) {
            return false;
        }

        return (int) $photo->user_id === (int) $user->id;
    }

    private function tenantUserFor(User $user, int $tenantId): ?TenantUser
    {
        return TenantUser::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();
    }
}
