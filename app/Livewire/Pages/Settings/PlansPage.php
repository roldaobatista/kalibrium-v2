<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Settings;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\PlanSummaryService;
use App\Support\Settings\PlanUpgradeRequestService;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

final class PlansPage extends Component
{
    public bool $readOnly = false;

    public bool $canRequestUpgrade = false;

    public function mount(CurrentTenantResolver $resolver): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $context = $resolver->resolve($user);
        $role = strtolower((string) $context['tenant_user']->role);
        if (! in_array($role, ['gerente', 'administrativo', 'visualizador'], true)) {
            abort(403);
        }

        if ($role === 'gerente' && (bool) $context['tenant_user']->requires_2fa && $user->two_factor_confirmed_at === null) {
            abort(403, 'Conclua a verificação em duas etapas.');
        }

        $this->readOnly = $context['access_mode'] === 'read-only' || session('tenant.access_mode') === 'read-only';
        $this->canRequestUpgrade = $role === 'gerente' && ! $this->readOnly;
    }

    public function requestUpgrade(
        PlanUpgradeRequestService $service,
        string $featureCode,
        ?string $justification = null,
    ): void {
        if (! $this->canRequestUpgrade) {
            abort(403, 'Acesso indisponivel para esta conta.');
        }

        $service->requestUpgrade($this->actor(), $this->actorTenantUser(), $featureCode, $justification);
        session()->flash('status', 'Pedido de upgrade registrado.');
    }

    public function render(PlanSummaryService $summaryService): View
    {
        $tenantUser = $this->actorTenantUser();

        return view('livewire.pages.settings.plans-page', [
            'summary' => $summaryService->summaryFor($tenantUser->tenant()->firstOrFail()),
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

    private function actorTenantUser(): TenantUser
    {
        return app(CurrentTenantResolver::class)->resolve($this->actor())['tenant_user'];
    }
}
