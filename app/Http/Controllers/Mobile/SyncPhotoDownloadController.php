<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderPhoto;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

final class SyncPhotoDownloadController extends Controller
{
    public function __invoke(Request $request, string $id): Response|JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'URL inválida ou expirada.'], 403);
        }

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $tenantId = $this->resolveTenantId($request);

        $photo = ServiceOrderPhoto::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $photo instanceof ServiceOrderPhoto) {
            return response()->json(['message' => 'Foto não encontrada.'], 404);
        }

        if ($user->cannot('view', $photo)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $contents = Storage::disk($photo->disk)->get($photo->path);

        if ($contents === null) {
            return response()->json(['message' => 'Arquivo não encontrado no storage.'], 404);
        }

        return response($contents, 200, [
            'Content-Type' => $photo->mime_type,
            'Content-Disposition' => 'inline; filename="'.$photo->original_filename.'"',
        ]);
    }

    private function resolveTenantId(Request $request): int
    {
        $tenant = $request->attributes->get('current_tenant');
        if ($tenant instanceof Tenant) {
            return (int) $tenant->id;
        }

        $id = TenantContext::getTenantId();
        if ($id !== null) {
            return $id;
        }

        throw new \RuntimeException('SyncPhotoDownloadController: tenant_id não disponível.');
    }
}
