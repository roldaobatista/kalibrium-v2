<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

final class TenantAccessResolver
{
    /**
     * @return array{
     *   allowed: bool,
     *   requires_two_factor: bool,
     *   access_mode: 'full'|'read-only',
     *   event: string,
     *   tenant_id: int|null,
     *   tenant_user_id: int|null
     * }
     */
    public function resolve(User $user): array
    {
        /** @var TenantUser|null $tenantUser */
        $tenantUser = $user->tenantUsers()->with('tenant')->latest('id')->first();

        if ($tenantUser === null || $tenantUser->tenant === null) {
            return [
                'allowed' => false,
                'requires_two_factor' => false,
                'access_mode' => 'full',
                'event' => 'auth.login.blocked_binding_status',
                'tenant_id' => null,
                'tenant_user_id' => $tenantUser?->id,
            ];
        }

        /** @var Tenant $tenant */
        $tenant = $tenantUser->tenant;
        $tenantStatus = strtolower((string) $tenant->status);
        $bindingStatus = strtolower((string) $tenantUser->status);

        if (in_array($bindingStatus, ['suspended', 'invited', 'removed'], true)) {
            return [
                'allowed' => false,
                'requires_two_factor' => false,
                'access_mode' => 'full',
                'event' => 'auth.login.blocked_binding_status',
                'tenant_id' => $tenantUser->tenant_id,
                'tenant_user_id' => $tenantUser->id,
            ];
        }

        if ($tenantStatus === 'cancelled') {
            return [
                'allowed' => false,
                'requires_two_factor' => false,
                'access_mode' => 'full',
                'event' => 'auth.login.blocked_tenant_status',
                'tenant_id' => $tenantUser->tenant_id,
                'tenant_user_id' => $tenantUser->id,
            ];
        }

        $requiresTwoFactor = (bool) $tenantUser->requires_2fa;
        $event = $tenantStatus === 'suspended' ? 'auth.login.read_only_access' : 'auth.login.success';
        $accessMode = $tenantStatus === 'suspended' ? 'read-only' : 'full';

        return [
            'allowed' => true,
            'requires_two_factor' => $requiresTwoFactor,
            'access_mode' => $accessMode,
            'event' => $event,
            'tenant_id' => $tenantUser->tenant_id,
            'tenant_user_id' => $tenantUser->id,
        ];
    }
}
