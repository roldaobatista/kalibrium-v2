<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Settings;

use App\Livewire\Pages\Settings\Concerns\ResolvesTenantSettingsContext;
use App\Models\User;
use App\Support\Settings\PlanSummaryService;
use App\Support\Settings\PlanUpgradeRequestService;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

final class PlansPage extends Component
{
    use ResolvesTenantSettingsContext;

    public bool $readOnly = false;

    public bool $canRequestUpgrade = false;

    public function mount(CurrentTenantResolver $resolver): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $context = $resolver->resolve($user);
        Gate::forUser($user)->authorize('tenant-plans.view', $context['tenant_user']);

        if ((bool) $context['tenant_user']->requires_2fa && $user->two_factor_confirmed_at === null) {
            abort(403, 'Conclua a verificação em duas etapas.');
        }

        $this->readOnly = $context['access_mode'] === 'read-only' || session('tenant.access_mode') === 'read-only';
        $this->canRequestUpgrade = Gate::forUser($user)->allows('tenant-plans.request-upgrade', $context['tenant_user'])
            && ! $this->readOnly;
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
}
