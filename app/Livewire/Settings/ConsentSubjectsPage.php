<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\ConsentSubject;
use App\Models\Tenant;
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

    private ?Tenant $tenant = null;

    public function mount(CurrentTenantResolver $resolver): void
    {
        $user = $this->actor();
        $context = $resolver->resolve($user);
        $this->tenant = $context['tenant'];
    }

    private function resolveTenant(): Tenant
    {
        if ($this->tenant === null) {
            $resolver = app(CurrentTenantResolver::class);
            $context = $resolver->resolve($this->actor());
            $this->tenant = $context['tenant'];
        }

        return $this->tenant;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, ConsentSubject>
     */
    #[Computed]
    public function subjects(): LengthAwarePaginator
    {
        $tenant = $this->resolveTenant();
        $query = ConsentSubject::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id);

        if ($this->statusFilter !== '') {
            $query->withConsentStatus('email', $this->statusFilter);
        }

        return $query->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.settings.consent-subjects-page', [
            'subjects' => $this->subjects(),
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
