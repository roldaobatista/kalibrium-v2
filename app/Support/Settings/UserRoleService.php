<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\Concerns\AuthorizesTenantSettings;
use App\Support\Tenancy\TenantAuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class UserRoleService
{
    use AuthorizesTenantSettings;

    /** @var array<int, string> */
    private const array ROLES = ['gerente', 'tecnico', 'administrativo', 'visualizador'];

    /** @var array<int, string> */
    private const array CRITICAL_ROLES = ['gerente', 'administrativo'];

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
            'role' => ['required', 'string', Rule::in(self::ROLES)],
        ])->validate();
        $newRole = strtolower((string) $data['role']);

        DB::transaction(function () use ($actor, $tenant, $target, $newRole): void {
            $fresh = TenantUser::query()
                ->whereKey($target->id)
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fresh->status === 'active' && $fresh->role === 'gerente' && $newRole !== 'gerente') {
                $activeManagers = TenantUser::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'gerente')
                    ->where('status', 'active')
                    ->count();

                if ($activeManagers <= 1) {
                    throw new AuthorizationException('Mantenha ao menos um gerente ativo.');
                }
            }

            $fresh->forceFill([
                'role' => $newRole,
                'requires_2fa' => in_array($newRole, self::CRITICAL_ROLES, true),
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
