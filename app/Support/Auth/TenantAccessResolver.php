<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

final class TenantAccessResolver
{
    /** @var array<int, string> */
    private const ALLOWED_TENANT_STATUSES = ['active', 'trial', 'suspended'];

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
        $tenantUsers = $user->tenantUsers()->with('tenant')->get();
        $activeTenantUsers = $tenantUsers->filter(
            static fn (TenantUser $tenantUser): bool => strtolower((string) $tenantUser->status) === 'active'
                && $tenantUser->tenant !== null
        )->values();

        if ($activeTenantUsers->count() > 1) {
            return [
                'allowed' => false,
                'requires_two_factor' => false,
                'access_mode' => 'full',
                'event' => 'auth.login.blocked_ambiguous_tenant',
                'tenant_id' => null,
                'tenant_user_id' => null,
            ];
        }

        /** @var TenantUser|null $tenantUser */
        $tenantUser = $activeTenantUsers->first() ?? $tenantUsers->sortByDesc('id')->first();

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

        if ($bindingStatus !== 'active') {
            return [
                'allowed' => false,
                'requires_two_factor' => false,
                'access_mode' => 'full',
                'event' => 'auth.login.blocked_binding_status',
                'tenant_id' => $tenantUser->tenant_id,
                'tenant_user_id' => $tenantUser->id,
            ];
        }

        if (! in_array($tenantStatus, self::ALLOWED_TENANT_STATUSES, true)) {
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
