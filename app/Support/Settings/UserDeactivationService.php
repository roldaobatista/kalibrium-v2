<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\Concerns\AuthorizesTenantSettings;
use App\Support\Tenancy\TenantAuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class UserDeactivationService
{
    use AuthorizesTenantSettings;

    public function __construct(
        private TenantAuditRecorder $auditRecorder,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function deactivate(User $actor, TenantUser $actorTenantUser, TenantUser $target): void
    {
        $tenant = $this->assertActiveManager($actor, $actorTenantUser);
        $this->assertSameTenant($actorTenantUser, $target);

        DB::transaction(function () use ($actor, $tenant, $target): void {
            $activeManagers = $this->lockActiveManagerSetForTenant((int) $tenant->id);
            $fresh = TenantUser::query()
                ->whereKey($target->id)
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fresh->role === 'gerente' && $fresh->status === 'active') {
                if ($activeManagers <= 1 || (int) $fresh->user_id === $actor->id) {
                    throw new AuthorizationException('Mantenha ao menos um gerente ativo.');
                }
            }

            $fresh->forceFill(['status' => 'removed'])->save();

            $this->auditRecorder->record(
                request(),
                (int) $tenant->id,
                $actor->id,
                'tenant.user.deactivated',
                ['status'],
            );
        });
    }
}
