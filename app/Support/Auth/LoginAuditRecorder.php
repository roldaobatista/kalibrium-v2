<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\LoginAuditLog;
use Illuminate\Http\Request;

final class LoginAuditRecorder
{
    public function __construct(
        private readonly AuthPayloadSanitizer $sanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        Request $request,
        string $event,
        ?int $userId = null,
        ?int $tenantId = null,
        array $context = [],
    ): void {
        LoginAuditLog::query()->create([
            'event' => $event,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'ip_address' => $request->ip(),
            'user_agent_hash' => $this->userAgentHash($request->userAgent()),
            'context' => $this->sanitizer->sanitize($context),
        ]);
    }

    private function userAgentHash(?string $userAgent): ?string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return null;
        }

        return hash('sha256', $userAgent);
    }
}
