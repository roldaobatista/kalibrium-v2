<?php

declare(strict_types=1);

namespace App\Livewire\MobileDevices;

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\TenantUser;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function mount(): void
    {
        $this->authorize('mobile-devices.viewAny', $this->tenantUser());
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

    public function aprovar(string $deviceId): void
    {
        $this->authorize('mobile-devices.manage', $this->tenantUser());

        $device = $this->findDeviceOrFail($deviceId);

        $device->update([
            'status' => MobileDeviceStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => auth()->id(),
        ]);

        $this->registrarAuditoria('mobile_device.approved', $deviceId);

        $this->dispatch('toast-show', type: 'success', message: 'Celular aprovado. Técnico já pode entrar.');
    }

    public function recusar(string $deviceId): void
    {
        $this->authorize('mobile-devices.manage', $this->tenantUser());

        $device = $this->findDeviceOrFail($deviceId);

        $device->update([
            'status' => MobileDeviceStatus::Revoked,
            'revoked_at' => now(),
        ]);

        $this->registrarAuditoria('mobile_device.refused', $deviceId);

        $this->dispatch('toast-show', type: 'warning', message: 'Pedido recusado.');
    }

    public function bloquear(string $deviceId): void
    {
        $this->authorize('mobile-devices.manage', $this->tenantUser());

        $device = $this->findDeviceOrFail($deviceId);

        $device->update([
            'status' => MobileDeviceStatus::Revoked,
            'revoked_at' => now(),
        ]);

        $this->registrarAuditoria('mobile_device.blocked', $deviceId);

        $this->dispatch('toast-show', type: 'danger', message: 'Celular bloqueado.');
    }

    public function reativar(string $deviceId): void
    {
        $this->authorize('mobile-devices.manage', $this->tenantUser());

        $device = $this->findDeviceOrFail($deviceId);

        // Volta para pending para novo ciclo de aprovação consciente.
        // O gerente precisa reaprovar explicitamente — isso garante revisão intencional.
        $device->update([
            'status' => MobileDeviceStatus::Pending,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'revoked_at' => null,
        ]);

        $this->registrarAuditoria('mobile_device.reactivated', $deviceId);

        $this->dispatch('toast-show', type: 'info', message: 'Celular reativado. Revise e aprove para liberar acesso.');
    }

    /** @return LengthAwarePaginator<int, MobileDevice> */
    public function devices(): LengthAwarePaginator
    {
        $validStatus = MobileDeviceStatus::tryFrom($this->statusFilter);

        return MobileDevice::query()
            ->with(['user', 'approver'])
            ->when($this->search !== '', function ($q): void {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%'));
            })
            ->when($validStatus !== null, fn ($q) => $q->where('status', $validStatus))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('last_seen_at')
            ->paginate(25);
    }

    private function findDeviceOrFail(string $deviceId): MobileDevice
    {
        $tenantId = $this->currentTenantId();

        try {
            return MobileDevice::where('tenant_id', $tenantId)
                ->findOrFail($deviceId);
        } catch (ModelNotFoundException) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        return view('livewire.mobile-devices.index-page', [
            'devices' => $this->devices(),
            'statuses' => [
                '' => 'Todos',
                MobileDeviceStatus::Pending->value => 'Aguardando',
                MobileDeviceStatus::Approved->value => 'Aprovado',
                MobileDeviceStatus::Revoked->value => 'Bloqueado',
            ],
        ]);
    }

    private function registrarAuditoria(string $action, string $deviceId): void
    {
        TenantAuditLog::create([
            'tenant_id' => $this->currentTenantId(),
            'user_id' => auth()->id(),
            'action' => $action,
            'changed_fields' => ['device_id' => $deviceId],
            'ip_address' => request()->ip(),
            'user_agent_hash' => hash('sha256', request()->userAgent() ?? ''),
        ]);
    }

    private function tenantUser(): ?TenantUser
    {
        // Tenta primeiro via atributo do request (middleware tenant.context no HTTP normal).
        /** @var TenantUser|null $fromRequest */
        $fromRequest = request()->attributes->get('current_tenant_user');
        if ($fromRequest instanceof TenantUser) {
            return $fromRequest;
        }

        // Fallback: busca no banco pelo usuário autenticado + tenant do contexto.
        // Necessário em contextos onde o atributo não está disponível (ex: testes Livewire).
        $userId = auth()->id();
        $tenantId = $this->currentTenantId();

        if ($userId === null || $tenantId === null) {
            return null;
        }

        return TenantUser::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();
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
