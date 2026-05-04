<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantRole;

final class ServiceOrderPolicy
{
    /**
     * Gerente pode ver qualquer OS do tenant.
     * Técnico só pode ver a própria OS.
     */
    public function view(User $user, ServiceOrder $serviceOrder): bool
    {
        $tenantUser = $this->tenantUserFor($user, (int) $serviceOrder->tenant_id);
        if ($tenantUser === null) {
            return false;
        }

        if (strtolower((string) $tenantUser->role) === TenantRole::MANAGER) {
            return true;
        }

        return (int) $serviceOrder->user_id === (int) $user->id;
    }

    /**
     * Apenas o técnico dono da OS pode editar (via sync mobile).
     */
    public function update(User $user, ServiceOrder $serviceOrder): bool
    {
        $tenantUser = $this->tenantUserFor($user, (int) $serviceOrder->tenant_id);
        if ($tenantUser === null) {
            return false;
        }

        return (int) $serviceOrder->user_id === (int) $user->id;
    }

    private function tenantUserFor(User $user, int $tenantId): ?TenantUser
    {
        return TenantUser::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();
    }
}
