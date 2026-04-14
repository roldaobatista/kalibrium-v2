<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Support\Facades\DB;

final class PostgresAuthContext
{
    public function forUser(int $userId): void
    {
        $this->set('app.auth_user_id', (string) $userId);
    }

    public function forTenant(?int $tenantId): void
    {
        $this->set('app.current_tenant_id', $tenantId === null ? '' : (string) $tenantId);
    }

    private function set(string $key, string $value): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::select('select set_config(?, ?, false)', [$key, $value]);
    }
}
