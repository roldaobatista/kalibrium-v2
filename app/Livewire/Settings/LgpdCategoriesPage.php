<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Livewire\Concerns\ResolvesTenantAndActor;
use App\Models\LgpdCategory;
use App\Support\Lgpd\LgpdCategoryService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

final class LgpdCategoriesPage extends Component
{
    use ResolvesTenantAndActor;

    public string $code = '';

    public string $name = '';

    public string $legal_basis = '';

    public string $retention_policy = '';

    public string $comment = '';

    public function mount(): void
    {
        $this->resolveTenant();
    }

    public function save(LgpdCategoryService $service): void
    {
        if ($this->readOnly) {
            abort(403, 'Conta em modo somente leitura.');
        }

        try {
            $service->declare($this->resolveTenant(), $this->actor(), [
                'code' => $this->code,
                'name' => $this->name,
                'legal_basis' => $this->legal_basis,
                'retention_policy' => $this->retention_policy,
                'comment' => $this->comment,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, (string) ($messages[0] ?? 'Erro de validação.'));
            }

            return;
        }

        $this->reset(['code', 'name', 'legal_basis', 'retention_policy', 'comment']);
        session()->flash('status', 'Base legal registrada.');
    }

    public function deleteCategory(LgpdCategoryService $service, string $id): void
    {
        if ($this->readOnly) {
            abort(403, 'Conta em modo somente leitura.');
        }

        $tenant = $this->resolveTenant();
        $category = LgpdCategory::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $service->delete($tenant, $category);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, (string) ($messages[0] ?? 'Erro.'));
            }
        }
    }

    public function render(LgpdCategoryService $service): View
    {
        $categories = $service->listForTenant($this->resolveTenant());

        return view('livewire.settings.lgpd-categories-page', [
            'categories' => $categories,
            'codes' => LgpdCategory::CODES,
            'legalBases' => LgpdCategory::LEGAL_BASES,
        ])->layout('layouts.app');
    }
}
