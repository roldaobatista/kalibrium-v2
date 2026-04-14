<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Settings\Concerns;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Facades\Auth;

trait ResolvesTenantSettingsContext
{
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
