<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Concerns\ResolvesTenantAndActor;
use App\Models\ConsentSubject;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class ConsentSubjectsPage extends Component
{
    use ResolvesTenantAndActor;
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    public int $perPage = 50;

    public function mount(CurrentTenantResolver $resolver): void
    {
        $user = $this->actor();
        $context = $resolver->resolve($user);
        $this->tenant = $context['tenant'];
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
}
