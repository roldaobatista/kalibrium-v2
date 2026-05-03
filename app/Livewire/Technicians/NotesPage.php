<?php

declare(strict_types=1);

namespace App\Livewire\Technicians;

use App\Models\Note;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Support\Auth\PostgresAuthContext;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantScopeBypass;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

final class NotesPage extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public int $technicianUserId;

    public function mount(int $technicianUserId): void
    {
        $this->technicianUserId = $technicianUserId;
        $this->authorize('technicians.viewAny', $this->tenantUser());
    }

    public function render(): View
    {
        $tenantId = $this->currentTenantId();

        $technician = null;
        if ($tenantId !== null) {
            $technician = TenantUser::with('user')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $this->technicianUserId)
                ->first();
        }

        /** @var LengthAwarePaginator<int, Note> $notes */
        $notes = Note::where('user_id', $this->technicianUserId)
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('livewire.technicians.notes-page', [
            'technician' => $technician,
            'notes' => $notes,
        ])->layout('components.layouts.app');
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
                ->where('status', 'active')
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
