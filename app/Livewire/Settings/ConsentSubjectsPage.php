<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Concerns\ResolvesTenantAndActor;
use App\Models\ConsentRecord;
use App\Models\ConsentSubject;
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

    public function mount(): void
    {
        $this->resolveTenant();
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
        // Contexto PostgresAuthContext do middleware já garante RLS por tenant.
        // withoutGlobalScopes() + where tenant_id explícito mantém a query
        // compatível com ambientes de teste que não fazem RLS via trigger.
        $query = ConsentSubject::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id);

        if ($this->statusFilter !== '') {
            $query->withConsentStatus('email', $this->statusFilter);
        }

        return $query->paginate($this->perPage);
    }

    /**
     * Resumo do último consent_record por subject: canal, status, data.
     *
     * @param  LengthAwarePaginator<int, ConsentSubject>  $subjects
     * @return array<string, array{channel: string, status: string, updated_at: string}>
     */
    private function latestRecordsFor(LengthAwarePaginator $subjects): array
    {
        $subjectIds = collect($subjects->items())->pluck('id')->all();
        if ($subjectIds === []) {
            return [];
        }

        $records = ConsentRecord::withoutGlobalScopes()
            ->whereIn('consent_subject_id', $subjectIds)
            ->orderByDesc('created_at')
            ->get(['consent_subject_id', 'channel', 'status', 'created_at']);

        $summary = [];
        foreach ($records as $record) {
            $key = 'id:'.$record->consent_subject_id;
            if (isset($summary[$key])) {
                continue;
            }
            $createdAt = $record->getAttribute('created_at');
            $summary[$key] = [
                'channel' => (string) $record->channel,
                'status' => (string) $record->status,
                'updated_at' => $createdAt instanceof \DateTimeInterface
                    ? $createdAt->format('d/m/Y H:i')
                    : '-',
            ];
        }

        return $summary;
    }

    public function render(): View
    {
        $subjects = $this->subjects();

        return view('livewire.settings.consent-subjects-page', [
            'subjects' => $subjects,
            'latestRecords' => $this->latestRecordsFor($subjects),
        ])->layout('layouts.app');
    }
}
