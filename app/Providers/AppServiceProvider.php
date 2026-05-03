<?php

namespace App\Providers;

use App\Policies\ClientePolicy;
use App\Policies\ContatoPolicy;
use App\Policies\MobileDevicePolicy;
use App\Policies\TechnicianPolicy;
use App\Policies\TenantSettingsPolicy;
use App\Support\Auth\TenantAwarePasswordBrokerManager;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
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
        // Substitui o PasswordBrokerManager padrão pelo ciente de tenant,
        // para que tokens de reset sejam particionados por tenant_id.
        $this->app->extend(PasswordBrokerManager::class, function ($_, $app) {
            return new TenantAwarePasswordBrokerManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('tenant-users.manage', [TenantSettingsPolicy::class, 'manageUsers']);
        Gate::define('tenant-plans.view', [TenantSettingsPolicy::class, 'viewPlan']);
        Gate::define('tenant-plans.request-upgrade', [TenantSettingsPolicy::class, 'requestPlanUpgrade']);

        Gate::define('clientes.viewAny', [ClientePolicy::class, 'viewAny']);
        Gate::define('clientes.view', [ClientePolicy::class, 'view']);
        Gate::define('clientes.create', [ClientePolicy::class, 'create']);
        Gate::define('clientes.update', [ClientePolicy::class, 'update']);
        Gate::define('clientes.delete', [ClientePolicy::class, 'delete']);

        Gate::define('contatos.viewAny', [ContatoPolicy::class, 'viewAny']);
        Gate::define('contatos.view', [ContatoPolicy::class, 'view']);
        Gate::define('contatos.create', [ContatoPolicy::class, 'create']);
        Gate::define('contatos.update', [ContatoPolicy::class, 'update']);
        Gate::define('contatos.delete', [ContatoPolicy::class, 'delete']);

        Gate::define('mobile-devices.viewAny', [MobileDevicePolicy::class, 'viewAny']);
        Gate::define('mobile-devices.manage', [MobileDevicePolicy::class, 'manage']);

        Gate::define('technicians.viewAny', [TechnicianPolicy::class, 'viewAny']);
        Gate::define('technicians.create', [TechnicianPolicy::class, 'create']);
        Gate::define('technicians.update', [TechnicianPolicy::class, 'update']);
        Gate::define('technicians.deactivate', [TechnicianPolicy::class, 'deactivate']);
    }
}
