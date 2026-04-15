<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Auth\TenantAccessResolver;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class CurrentTenantResolver
{
    public function __construct(
        private TenantAccessResolver $accessResolver,
    ) {}

    /**
     * @return array{tenant: Tenant, tenant_user: TenantUser, access_mode: 'full'|'read-only'}
     *
     * @throws AuthorizationException
     */
    public function resolve(User $user): array
    {
        $decision = $this->accessResolver->resolve($user);

        if (! $decision['allowed'] || $decision['tenant_id'] === null || $decision['tenant_user_id'] === null) {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }

        /** @var TenantUser|null $tenantUser */
        $tenantUser = TenantUser::query()
            ->with('tenant')
            ->whereKey($decision['tenant_user_id'])
            ->where('user_id', $user->id)
            ->where('tenant_id', $decision['tenant_id'])
            ->where('status', 'active')
            ->first();

        if ($tenantUser === null || $tenantUser->tenant === null) {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }

        return [
            'tenant' => $tenantUser->tenant,
            'tenant_user' => $tenantUser,
            'access_mode' => $decision['access_mode'],
        ];
    }

    /**
     * @param  array{tenant: Tenant, tenant_user: TenantUser, access_mode: 'full'|'read-only'}  $context
     *
     * @throws AuthorizationException
     */
    public function assertManager(array $context): void
    {
        if (strtolower((string) $context['tenant_user']->role) !== 'gerente') {
            throw new AuthorizationException('Acesso indisponivel para esta conta.');
        }
    }
}
