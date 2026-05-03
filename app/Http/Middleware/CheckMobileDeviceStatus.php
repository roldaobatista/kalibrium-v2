<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckMobileDeviceStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceIdentifier = $request->header('X-Device-Id');

        if ($deviceIdentifier === null || $deviceIdentifier === '') {
            return response()->json(['erro' => 'Identificador do celular não informado.'], 400);
        }

        $userId = $request->user()?->id;

        if ($userId === null) {
            return response()->json(['erro' => 'Não autenticado.'], 401);
        }

        $tenantId = $this->resolveTenantId($request);

        // Busca sem global scope de tenant — o middleware roda antes do SetCurrentTenantContext
        // e o filtro explícito por tenant_id garante o isolamento.
        $device = MobileDevice::withoutGlobalScope('current_tenant')
            ->where('user_id', $userId)
            ->where('device_identifier', $deviceIdentifier)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->first();

        if ($device === null) {
            return response()->json(['erro' => 'Celular não reconhecido.'], 401);
        }

        if ($device->status === MobileDeviceStatus::WipedAndRevoked) {
            if ($device->wipe_acknowledged_at === null) {
                $device->update(['wipe_acknowledged_at' => now()]);
            }

            return response()->json([
                'erro' => 'Este celular foi bloqueado pelo seu laboratório. Entre em contato com o gerente.',
                'wipe' => true,
            ], 401);
        }

        if ($device->status !== MobileDeviceStatus::Approved) {
            return response()->json(['erro' => 'Acesso não autorizado.'], 403);
        }

        return $next($request);
    }

    private function resolveTenantId(Request $request): ?int
    {
        // O token é nomeado 'mobile:tenant:{id}' — extrai o tenant_id do nome.
        $token = $request->user()?->currentAccessToken();
        if ($token === null) {
            return null;
        }

        $name = (string) $token->name;

        if (preg_match('/^mobile:tenant:(\d+)$/', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
