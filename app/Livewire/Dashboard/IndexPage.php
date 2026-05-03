<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Support\Auth\PostgresAuthContext;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantScopeBypass;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

final class IndexPage extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('mobile-devices.viewAny', $this->tenantUser());
    }

    public function saudacao(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
    }

    public function render(): View
    {
        $this->tenantUser();

        $pendingCount = MobileDevice::query()
            ->where('status', MobileDeviceStatus::Pending)
            ->count();

        $approvedCount = MobileDevice::query()
            ->where('status', MobileDeviceStatus::Approved)
            ->count();

        $revokedCount = MobileDevice::query()
            ->whereIn('status', [MobileDeviceStatus::Revoked, MobileDeviceStatus::WipedAndRevoked])
            ->count();

        return view('livewire.dashboard.index-page', [
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'revokedCount' => $revokedCount,
        ]);
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
