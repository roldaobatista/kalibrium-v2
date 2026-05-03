<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantScopeBypass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class ForgotPasswordController extends Controller
{
    /**
     * Envia link de redefinição de senha.
     *
     * Sempre retorna 200 com mensagem genérica — não revela se o e-mail
     * está cadastrado (proteção contra enumeração de usuários).
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
                'tenant_id' => ['required', 'integer', 'min:1'],
            ]);
        } catch (ValidationException) {
            // Retorna 200 genérico mesmo em dados inválidos para não vazar info
            return $this->mensagemGenerica();
        }

        $tenantId = (int) $validated['tenant_id'];
        $email = mb_strtolower($validated['email']);

        $tenant = TenantScopeBypass::run(
            fn () => Tenant::find($tenantId),
        );

        if (! $tenant instanceof Tenant) {
            return $this->mensagemGenerica();
        }

        // Procura usuário no tenant (sem global scope para evitar dependência de
        // contexto de tenant ainda não inicializado neste endpoint público).
        $user = TenantScopeBypass::run(function () use ($email, $tenantId): ?User {
            return User::whereHas('tenantUsers', function ($q) use ($tenantId): void {
                $q->where('tenant_id', $tenantId);
            })->where('email', $email)->first();
        });

        if ($user instanceof User) {
            // Seta o TenantContext para que TenantAwarePasswordTokenRepository
            // persista o token com o tenant_id correto (não o sentinel 0).
            TenantContext::setTenantId($tenantId);
            try {
                $token = Password::broker('users')->createToken($user);
            } finally {
                TenantContext::reset();
            }
            $user->notify(new ResetPasswordNotification($token, $tenantId));
        }

        return $this->mensagemGenerica();
    }

    private function mensagemGenerica(): JsonResponse
    {
        return response()->json([
            'mensagem' => 'Se este e-mail estiver cadastrado, você vai receber em alguns minutos uma mensagem com o link pra redefinir a senha. Confira sua caixa de entrada (e a pasta de spam, se não encontrar).',
        ]);
    }
}
