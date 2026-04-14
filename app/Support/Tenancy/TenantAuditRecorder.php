<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\TenantAuditLog;
use Illuminate\Http\Request;

final readonly class TenantAuditRecorder
{
    /**
     * @param  array<int, string>  $changedFields
     */
    public function record(
        Request $request,
        int $tenantId,
        int $userId,
        string $action,
        array $changedFields,
    ): void {
        TenantAuditLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'changed_fields' => array_values(array_unique($this->safeFields($changedFields))),
            'ip_address' => $request->ip(),
            'user_agent_hash' => $this->userAgentHash($request->userAgent()),
        ]);
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<int, string>
     */
    private function safeFields(array $fields): array
    {
        $blocked = [
            'password',
            'token',
            'reset',
            'totp',
            'recovery',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        return array_values(array_filter(
            $fields,
            static function (string $field) use ($blocked): bool {
                foreach ($blocked as $blockedFragment) {
                    if (str_contains(strtolower($field), $blockedFragment)) {
                        return false;
                    }
                }

                return true;
            },
        ));
    }

    private function userAgentHash(?string $userAgent): ?string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return null;
        }

        return hash('sha256', $userAgent);
    }
}
