<?php

declare(strict_types=1);

namespace App\Livewire\Technicians;

use App\Enums\TenantUserStatus;
use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Auth\PostgresAuthContext;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantRole;
use App\Support\Tenancy\TenantScopeBypass;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class IndexPage extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    // Form fields
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public ?int $editingTenantUserId = null;

    public function mount(): void
    {
        $this->authorize('technicians.viewAny', $this->tenantUser());
        $this->search = $this->q;
        $this->statusFilter = $this->status;
    }

    public function updatedSearch(): void
    {
        $this->q = $this->search;
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->status = $this->statusFilter;
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('technicians.create', $this->tenantUser());
        $this->resetForm();
        $this->showCreateModal = true;
        $this->showEditModal = false;
    }

    public function criar(): void
    {
        $this->authorize('technicians.create', $this->tenantUser());

        $tenantId = $this->currentTenantId();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'regex:/[0-9]/'],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.regex' => 'A senha deve conter pelo menos um número.',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        TenantUser::create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'role' => TenantRole::TECHNICIAN,
            'status' => TenantUserStatus::Active,
        ]);

        $this->registrarAuditoria('technician.created', ['user_id' => $user->id, 'email' => $user->email]);

        $this->resetForm();
        $this->showCreateModal = false;

        $this->dispatch('toast-show', type: 'success', message: 'Técnico cadastrado com sucesso.');
    }

    public function editar(int $tenantUserId): void
    {
        $this->authorize('technicians.update', $this->tenantUser());

        $tenantUser = $this->findTechnicianOrFail($tenantUserId);

        $this->editingTenantUserId = $tenantUserId;
        $this->name = $tenantUser->user instanceof User ? $tenantUser->user->name : '';
        $this->email = $tenantUser->user instanceof User ? $tenantUser->user->email : '';
        $this->password = '';
        $this->showEditModal = true;
        $this->showCreateModal = false;
    }

    public function salvar(): void
    {
        $this->authorize('technicians.update', $this->tenantUser());

        $tenantUser = $this->findTechnicianOrFail((int) $this->editingTenantUserId);
        $user = $tenantUser->user;

        if (! $user instanceof User) {
            throw new NotFoundHttpException;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está em uso.',
        ]);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->registrarAuditoria('technician.updated', ['tenant_user_id' => $tenantUser->id, 'user_id' => $user->id]);

        $this->resetForm();
        $this->showEditModal = false;
        $this->editingTenantUserId = null;

        $this->dispatch('toast-show', type: 'success', message: 'Técnico atualizado.');
    }

    public function desativar(int $tenantUserId): void
    {
        $this->authorize('technicians.deactivate', $this->tenantUser());

        $tenantUser = $this->findTechnicianOrFail($tenantUserId);
        $tenantUser->update(['status' => TenantUserStatus::Inactive]);

        $this->registrarAuditoria('technician.deactivated', ['tenant_user_id' => $tenantUserId]);

        $this->dispatch('toast-show', type: 'warning', message: 'Técnico desativado. Acesso bloqueado.');
    }

    public function reativar(int $tenantUserId): void
    {
        $this->authorize('technicians.deactivate', $this->tenantUser());

        $tenantUser = $this->findTechnicianOrFail($tenantUserId);
        $tenantUser->update(['status' => TenantUserStatus::Active]);

        $this->registrarAuditoria('technician.reactivated', ['tenant_user_id' => $tenantUserId]);

        $this->dispatch('toast-show', type: 'success', message: 'Técnico reativado.');
    }

    public function fecharModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->editingTenantUserId = null;
    }

    /** @return LengthAwarePaginator<int, TenantUser> */
    public function technicians(): LengthAwarePaginator
    {
        if ($this->currentTenantId() === null) {
            throw new AuthorizationException('Sessão inválida. Entre de novo.');
        }

        $validStatus = TenantUserStatus::tryFrom($this->statusFilter);

        return TenantUser::query()
            ->with('user')
            ->where('role', TenantRole::TECHNICIAN)
            ->when($this->search !== '', function ($q): void {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%'));
            })
            ->when($validStatus !== null, fn ($q) => $q->where('status', $validStatus))
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public function render(): View
    {
        $this->tenantUser();

        $technicians = $this->technicians();

        return view('livewire.technicians.index-page', [
            'technicians' => $technicians,
            'techniciansCount' => $technicians->total(),
            'statuses' => [
                '' => 'Todos',
                TenantUserStatus::Active->value => 'Ativo',
                TenantUserStatus::Inactive->value => 'Inativo',
            ],
        ]);
    }

    private function findTechnicianOrFail(int $tenantUserId): TenantUser
    {
        $tenantId = $this->currentTenantId();

        try {
            return TenantUser::where('tenant_id', $tenantId)
                ->where('role', TenantRole::TECHNICIAN)
                ->with('user')
                ->findOrFail($tenantUserId);
        } catch (ModelNotFoundException) {
            throw new NotFoundHttpException;
        }
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
    }

    /** @param array<string, mixed> $changedFields */
    private function registrarAuditoria(string $action, array $changedFields = []): void
    {
        TenantAuditLog::create([
            'tenant_id' => $this->currentTenantId(),
            'user_id' => auth()->id(),
            'action' => $action,
            'changed_fields' => $changedFields,
            'ip_address' => request()->ip(),
            'user_agent_hash' => hash('sha256', request()->userAgent() ?? ''),
        ]);
    }

    private function tenantUser(): ?TenantUser
    {
        /** @var TenantUser|null $fromRequest */
        $fromRequest = request()->attributes->get('current_tenant_user');
        if ($fromRequest instanceof TenantUser) {
            return $fromRequest;
        }

        $rawUserId = auth()->id();
        if ($rawUserId === null) {
            return null;
        }

        $userId = (int) $rawUserId;

        $tenantId = $this->currentTenantId();
        if ($tenantId !== null) {
            return TenantUser::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->first();
        }

        app(PostgresAuthContext::class)->forUser($userId);

        /** @var TenantUser|null $resolved */
        $resolved = TenantScopeBypass::run(
            fn () => TenantUser::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('status', TenantUserStatus::Active)
                ->with('tenant')
                ->first(),
        );

        if ($resolved instanceof TenantUser && $resolved->tenant !== null) {
            app(PostgresAuthContext::class)->forTenant((int) $resolved->tenant_id);
            app(PostgresAuthContext::class)->forUser((int) $userId);
            request()->attributes->set('current_tenant', $resolved->tenant);
            request()->attributes->set('current_tenant_user', $resolved);
        }

        return $resolved;
    }

    private function currentTenantId(): ?int
    {
        $tenant = request()->attributes->get('current_tenant');
        if ($tenant instanceof Tenant) {
            return (int) $tenant->id;
        }

        if (function_exists('tenancy') && tenancy()->initialized) {
            $key = tenant()->getTenantKey();
            if (is_numeric($key)) {
                return (int) $key;
            }
        }

        return TenantContext::getTenantId();
    }
}
