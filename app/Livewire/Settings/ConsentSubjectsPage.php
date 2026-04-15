<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\ConsentSubject;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class ConsentSubjectsPage extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    public int $perPage = 50;

    private Tenant $tenant;

    private TenantUser $tenantUser;

    public function mount(CurrentTenantResolver $resolver): void
    {
        $user = $this->actor();
        $context = $resolver->resolve($user);
        $this->tenant = $context['tenant'];
        $this->tenantUser = $context['tenant_user'];
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<ConsentSubject>
     */
    #[Computed]
    public function subjects(): LengthAwarePaginator
    {
        $query = ConsentSubject::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id);

        if ($this->statusFilter !== '') {
            $query->withConsentStatus('email', $this->statusFilter);
        }

        return $query->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.settings.consent-subjects-page', [
            'subjects' => $this->subjects,
        ])->layout('layouts.app');
    }

    private function actor(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
