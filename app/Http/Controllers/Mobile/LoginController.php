<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\MobileDeviceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\LoginRequest;
use App\Models\MobileDevice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

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

        $deviceIdentifier = $request->string('device_identifier')->toString();
        $deviceLabel = $request->string('device_label')->toString() ?: null;

        // ScopesToCurrentTenant filtra pelo tenant ativo no request->attributes (SEC-001).
        $device = MobileDevice::where('user_id', $user->id)
            ->where('device_identifier', $deviceIdentifier)
            ->first();

        if ($device === null) {
            MobileDevice::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'device_identifier' => $deviceIdentifier,
                'device_label' => $deviceLabel,
                'status' => MobileDeviceStatus::Pending,
            ]);

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
}
