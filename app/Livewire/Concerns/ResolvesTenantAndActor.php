<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Facades\Auth;

trait ResolvesTenantAndActor
{
    private ?Tenant $tenant = null;

    public bool $readOnly = false;

    private function resolveTenant(): Tenant
    {
        if ($this->tenant === null) {
            $resolver = app(CurrentTenantResolver::class);
            $context = $resolver->resolve($this->actor());
            $this->tenant = $context['tenant'];
            $this->readOnly = $context['access_mode'] === 'read-only';
        }

        return $this->tenant;
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
