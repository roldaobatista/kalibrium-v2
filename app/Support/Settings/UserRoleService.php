<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\Concerns\AuthorizesTenantSettings;
use App\Support\Tenancy\TenantAuditRecorder;
use App\Support\Tenancy\TenantRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class UserRoleService
{
    use AuthorizesTenantSettings;

    public function __construct(
        private TenantAuditRecorder $auditRecorder,
    ) {}

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function updateRole(User $actor, TenantUser $actorTenantUser, TenantUser $target, string $role): void
    {
        $tenant = $this->assertActiveManager($actor, $actorTenantUser);
        $this->assertSameTenant($actorTenantUser, $target);
        $data = Validator::make(['role' => $role], [
            'role' => ['required', 'string', Rule::in(TenantRole::values())],
        ])->validate();
        $newRole = strtolower((string) $data['role']);

        DB::transaction(function () use ($actor, $tenant, $target, $newRole): void {
            $activeManagers = $this->lockActiveManagerSetForTenant((int) $tenant->id);
            $fresh = TenantUser::query()
                ->whereKey($target->id)
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fresh->status === 'active' && $fresh->role === 'gerente' && $newRole !== 'gerente') {
                if ($activeManagers <= 1) {
                    throw new AuthorizationException('Mantenha ao menos um gerente ativo.');
                }
            }

            $fresh->forceFill([
                'role' => $newRole,
                'requires_2fa' => TenantRole::requiresTwoFactor($newRole),
            ])->save();

            $this->auditRecorder->record(
                request(),
                (int) $tenant->id,
                $actor->id,
                'tenant.user.role.updated',
                ['role', 'requires_2fa'],
            );
        });
    }
}
