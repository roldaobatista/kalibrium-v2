<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Settings;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\UserDeactivationService;
use App\Support\Settings\UserInvitationService;
use App\Support\Settings\UserRoleService;
use App\Support\Settings\UsersDirectoryQuery;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

final class UsersPage extends Component
{
    public string $search = '';

    public string $role = '';

    /** @var array<string, mixed> */
    public array $form = [
        'name' => '',
        'email' => '',
        'role' => 'tecnico',
        'company_id' => null,
        'branch_id' => null,
    ];

    public bool $readOnly = false;

    public function mount(CurrentTenantResolver $resolver): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $context = $resolver->resolve($user);
        $resolver->assertManager($context);

        if ((bool) $context['tenant_user']->requires_2fa && $user->two_factor_confirmed_at === null) {
            abort(403, 'Conclua a verificação em duas etapas.');
        }

        $this->readOnly = $context['access_mode'] === 'read-only' || session('tenant.access_mode') === 'read-only';
        $this->form['company_id'] = $context['tenant_user']->company_id;
        $this->form['branch_id'] = $context['tenant_user']->branch_id;
    }

    public function inviteUser(UserInvitationService $service): void
    {
        $this->assertWritable();
        $service->invite($this->actor(), $this->actorTenantUser(), $this->form);
        session()->flash('status', 'Convite enviado.');
    }

    public function updateRole(UserRoleService $service, int $tenantUserId, string $role): void
    {
        $this->assertWritable();
        $target = $this->targetTenantUser($tenantUserId);
        $service->updateRole($this->actor(), $this->actorTenantUser(), $target, $role);
        session()->flash('status', 'Papel atualizado.');
    }

    public function deactivateUser(UserDeactivationService $service, int $tenantUserId): void
    {
        $this->assertWritable();
        $target = $this->targetTenantUser($tenantUserId);
        $service->deactivate($this->actor(), $this->actorTenantUser(), $target);
        session()->flash('status', 'Usuario removido.');
    }

    public function render(UsersDirectoryQuery $query): View
    {
        return view('livewire.pages.settings.users-page', [
            'users' => $this->users($query),
        ])->layout('layouts.app');
    }

    /**
     * @return Collection<int, TenantUser>
     */
    private function users(UsersDirectoryQuery $query): Collection
    {
        return $query->forTenant(
            (int) $this->actorTenantUser()->tenant_id,
            $this->search,
            $this->role === '' ? null : $this->role,
        );
    }

    private function actor(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function actorTenantUser(): TenantUser
    {
        return app(CurrentTenantResolver::class)->resolve($this->actor())['tenant_user'];
    }

    private function targetTenantUser(int $tenantUserId): TenantUser
    {
        return TenantUser::query()
            ->where('tenant_id', $this->actorTenantUser()->tenant_id)
            ->whereKey($tenantUserId)
            ->firstOrFail();
    }

    private function assertWritable(): void
    {
        if ($this->readOnly) {
            abort(403, 'Conta em modo somente leitura.');
        }
    }
}
