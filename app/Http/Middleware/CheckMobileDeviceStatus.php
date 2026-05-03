<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MobileDeviceStatus;
use App\Models\MobileDevice;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de status do device móvel.
 *
 * Deve rodar ANTES de auth:sanctum para garantir que devices wiped retornem
 * 401 com wipe:true mesmo quando o token já expirou — assim o app móvel
 * recebe o sinal de limpeza independente do estado do token.
 *
 * Fluxo:
 *  1. Lê o bearer token do header manualmente (sem depender do Sanctum).
 *  2. Resolve o tenant_id a partir do nome do token ('mobile:tenant:{id}').
 *     Se o nome não bater com esse padrão, rejeita com 401 imediatamente.
 *  3. Busca o device por user_id + device_identifier + tenant_id (obrigatório).
 *  4. Se wiped, retorna 401 com wipe:true.
 *  5. Se device ok, repassa para o próximo middleware (auth:sanctum validará o token).
 */
final class CheckMobileDeviceStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceIdentifier = $request->header('X-Device-Id');

        if ($deviceIdentifier === null || $deviceIdentifier === '') {
            return response()->json(['erro' => 'Identificador do celular não informado.'], 400);
        }

        // Lê o token diretamente do header — não depende do Sanctum ter rodado.
        $bearerToken = $request->bearerToken();

        if ($bearerToken === null || $bearerToken === '') {
            return response()->json(['erro' => 'Sessão inválida. Entre de novo.'], 401);
        }

        // Resolve o PersonalAccessToken pelo bearer — usa o método nativo do Sanctum
        // que já faz o split e valida o hash. Não verifica expiração aqui
        // intencionalmente: queremos retornar wipe:true mesmo com token expirado,
        // para que o app móvel receba o sinal de limpeza.
        $accessToken = PersonalAccessToken::findToken($bearerToken);

        if (! $accessToken instanceof PersonalAccessToken) {
            return response()->json(['erro' => 'Sessão inválida. Entre de novo.'], 401);
        }

        // Valida o padrão do nome do token: 'mobile:tenant:{id}'.
        $tenantId = $this->resolveTenantIdFromToken($accessToken);

        if ($tenantId === null) {
            return response()->json(['erro' => 'Sessão inválida. Entre de novo.'], 401);
        }

        $userId = $accessToken->tokenable_id;

        // Filtro de tenant obrigatório — nunca executa query sem tenant_id.
        $device = MobileDevice::withoutGlobalScope('current_tenant')
            ->where('user_id', $userId)
            ->where('device_identifier', $deviceIdentifier)
            ->where('tenant_id', $tenantId)
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

    private function resolveTenantIdFromToken(PersonalAccessToken $token): ?int
    {
        $name = (string) $token->name;

        if (preg_match('/^mobile:tenant:(\d+)$/', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
