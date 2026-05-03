<?php

declare(strict_types=1);

namespace App\Livewire\Technicians;

use App\Models\Note;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Support\Tenancy\TenantContext;
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
