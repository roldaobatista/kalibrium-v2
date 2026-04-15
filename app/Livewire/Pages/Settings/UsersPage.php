<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Settings;

use App\Livewire\Pages\Settings\Concerns\ResolvesTenantSettingsContext;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\UserDeactivationService;
use App\Support\Settings\UserInvitationService;
use App\Support\Settings\UserRoleService;
use App\Support\Settings\UsersDirectoryQuery;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

final class UsersPage extends Component
{
    use ResolvesTenantSettingsContext;

    /** @var array<string, string> */
    private const array INVITE_ERROR_MESSAGES = [
        'name' => 'Informe o nome do usuario.',
        'email' => 'Informe um e-mail valido.',
        'role' => 'Selecione um papel valido.',
        'company_id' => 'Empresa invalida para este laboratorio.',
        'branch_id' => 'Filial invalida para este laboratorio.',
    ];

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
        Gate::forUser($user)->authorize('tenant-users.manage', $context['tenant_user']);

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
        try {
            $service->invite($this->actor(), $this->actorTenantUser(), $this->form);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $message = self::INVITE_ERROR_MESSAGES[$field] ?? (string) ($messages[0] ?? 'Revise os dados do convite.');
                $this->addError('form.'.$field, $message);
            }

            return;
        }

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
