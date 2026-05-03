<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\MobileDeviceStatus;
use App\Enums\TenantUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\LoginRequest;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\MobileDeviceRequested;
use App\Support\Tenancy\TenantRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

final class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('current_tenant');

        $user = User::where('email', $request->string('email')->toString())->first();

        if (! $user instanceof User || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json(['erro' => 'Email ou senha incorretos'], 401);
        }

        // Verifica se o usuário tem vínculo ativo com este tenant (SEC-002).
        $tenantUser = TenantUser::withoutGlobalScope('current_tenant')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if ($tenantUser === null || $tenantUser->status === TenantUserStatus::Inactive) {
            return response()->json([
                'erro' => 'Sua conta foi desativada. Procure o gerente.',
            ], 403);
        }

        $deviceIdentifier = $request->string('device_identifier')->toString();
        $deviceLabel = $request->string('device_label')->toString() ?: null;

        // Busca sem global scope — filtro explícito por tenant_id garante isolamento
        // mesmo antes do SetCurrentTenantContext ter rodado (SEC-001).
        $device = MobileDevice::withoutGlobalScope('current_tenant')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('device_identifier', $deviceIdentifier)
            ->first();

        if ($device === null) {
            $newDevice = MobileDevice::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'device_identifier' => $deviceIdentifier,
                'device_label' => $deviceLabel,
                'status' => MobileDeviceStatus::Pending,
            ]);

            $this->notificarGerentes($tenant, $newDevice);

            return response()->json([
                'status' => 'aguardando_aprovacao',
                'mensagem' => 'Este celular ainda não foi autorizado pelo seu laboratório. Pedido de autorização enviado para o gerente. Você vai poder entrar assim que ele aprovar.',
            ], 202);
        }

        if ($device->status === MobileDeviceStatus::Pending) {
            $device->update(['last_seen_at' => now()]);

            return response()->json([
                'status' => 'aguardando_aprovacao',
                'mensagem' => 'Este celular ainda não foi autorizado pelo seu laboratório. Pedido de autorização enviado para o gerente. Você vai poder entrar assim que ele aprovar.',
            ], 202);
        }

        if ($device->status === MobileDeviceStatus::Revoked) {
            return response()->json([
                'erro' => 'Este celular foi bloqueado pelo gerente. Entre em contato com ele.',
            ], 403);
        }

        // Approved — token Sanctum ancorado ao tenant via nome do token.
        $token = $user->createToken(
            name: 'mobile:tenant:'.$tenant->id,
            abilities: ['mobile:full'],
            expiresAt: now()->addDays(4),
        );

        $device->update(['last_seen_at' => now()]);

        return response()->json([
            'status' => 'ok',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    private function notificarGerentes(Tenant $tenant, MobileDevice $device): void
    {
        $gerentes = TenantUser::where('tenant_id', $tenant->id)
            ->where('role', TenantRole::MANAGER)
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        Notification::send($gerentes, new MobileDeviceRequested($device));
    }
}
