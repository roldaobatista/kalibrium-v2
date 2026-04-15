<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\LgpdCategory;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Lgpd\LgpdCategoryService;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

final class LgpdCategoriesPage extends Component
{
    public string $code = '';

    public string $name = '';

    public string $legal_basis = '';

    public string $retention_policy = '';

    public string $comment = '';

    public bool $readOnly = false;

    private Tenant $tenant;

    private TenantUser $tenantUser;

    public function mount(CurrentTenantResolver $resolver): void
    {
        $user = $this->actor();
        $context = $resolver->resolve($user);
        $this->tenant = $context['tenant'];
        $this->tenantUser = $context['tenant_user'];
        $this->readOnly = $context['access_mode'] === 'read-only';
    }

    public function save(LgpdCategoryService $service): void
    {
        if ($this->readOnly) {
            abort(403, 'Conta em modo somente leitura.');
        }

        try {
            $service->declare($this->tenant, $this->actor(), [
                'code'             => $this->code,
                'name'             => $this->name,
                'legal_basis'      => $this->legal_basis,
                'retention_policy' => $this->retention_policy,
                'comment'          => $this->comment,
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

        $category = LgpdCategory::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $service->delete($this->tenant, $category);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, (string) ($messages[0] ?? 'Erro.'));
            }
        }
    }

    public function render(LgpdCategoryService $service): View
    {
        $categories = $service->listForTenant($this->tenant);

        return view('livewire.settings.lgpd-categories-page', [
            'categories' => $categories,
            'codes'      => LgpdCategory::CODES,
            'legalBases' => LgpdCategory::LEGAL_BASES,
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
