<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\TenantUser;
use Illuminate\Support\Collection;

final class UsersDirectoryQuery
{
    /**
     * @return Collection<int, TenantUser>
     */
    public function forTenant(int $tenantId, ?string $search = null, ?string $role = null): Collection
    {
        $normalizedSearch = trim((string) $search);

        return TenantUser::query()
            ->with('user')
            ->where('tenant_id', $tenantId)
            ->when($role !== null && $role !== '', static function ($query) use ($role): void {
                $query->where('role', $role);
            })
            ->when($normalizedSearch !== '', static function ($query) use ($normalizedSearch): void {
                $needle = '%'.str_replace(['%', '_'], ['\%', '\_'], $normalizedSearch).'%';
                $query->whereHas('user', static function ($userQuery) use ($needle): void {
                    $userQuery
                        ->where('name', 'like', $needle)
                        ->orWhere('email', 'like', $needle);
                });
            })
            ->orderBy('role')
            ->orderBy('id')
            ->get();
    }
}
