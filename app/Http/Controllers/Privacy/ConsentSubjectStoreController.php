<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy;

use App\Exceptions\LgpdBaseLegalAusenteException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ConsentRecordService;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class ConsentSubjectStoreController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentTenantResolver $resolver,
        ConsentRecordService $service,
    ): JsonResponse {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $context = $resolver->resolve($user);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'Conta suspenso. Operacao nao permitida.',
            ], 422);
        }

        $tenant = $context['tenant'];

        $validated = $request->validate([
            'subject_type' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'channel' => ['nullable', 'string', 'max:32'],
            'opt_in' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $service->createForSubject((int) $tenant->id, $validated);
        } catch (LgpdBaseLegalAusenteException $e) {
            return response()->json([
                'message' => 'Registre a base legal LGPD em Configuracoes > LGPD antes de capturar consentimentos',
            ], 422);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['message' => 'ok'], 201);
    }
}
