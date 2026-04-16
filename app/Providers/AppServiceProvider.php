<?php

namespace App\Providers;

use App\Policies\ClientePolicy;
use App\Policies\TenantSettingsPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('tenant-users.manage', [TenantSettingsPolicy::class, 'manageUsers']);
        Gate::define('tenant-plans.view', [TenantSettingsPolicy::class, 'viewPlan']);
        Gate::define('tenant-plans.request-upgrade', [TenantSettingsPolicy::class, 'requestPlanUpgrade']);

        Gate::define('clientes.create', [ClientePolicy::class, 'create']);
        Gate::define('clientes.delete', [ClientePolicy::class, 'delete']);
    }
}
