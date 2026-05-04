<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderPhoto;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SyncEngine;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SyncPhotoUploadController extends Controller
{
    public function __construct(private readonly SyncEngine $syncEngine) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'service_order_id' => ['required_without:service_order_local_id', 'nullable', 'string', 'uuid'],
            'service_order_local_id' => ['required_without:service_order_id', 'nullable', 'string', 'max:36'],
            'client_uuid' => ['required', 'string', 'max:36'],
            'photo' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:8192'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $tenantId = $this->resolveTenantId($request);
        $deviceId = $request->string('device_id', 'unknown')->toString();

        // Resolve service_order_id (pode vir como server_id direto ou via local_id que precisa lookup)
        $serviceOrderId = $request->input('service_order_id');

        if ($serviceOrderId === null) {
            // Não há como fazer lookup por local_id no servidor — app deve sempre enviar service_order_id
            // após push bem-sucedido da OS. Retorna erro claro.
            return response()->json(['message' => 'service_order_id é obrigatório para upload de foto.'], 422);
        }

        // Barreira tripla: tenant + user (técnico dono) ou gerente do tenant
        $serviceOrder = ServiceOrder::where('id', $serviceOrderId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $serviceOrder instanceof ServiceOrder) {
            return response()->json(['message' => 'Ordem de serviço não encontrada.'], 404);
        }

        // Técnico só pode anexar foto na própria OS
        if ((int) $serviceOrder->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Sem permissão para anexar foto nesta OS.'], 403);
        }

        /** @var UploadedFile $file */
        $file = $request->file('photo');

        $ulid = (string) Str::ulid();
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = "tenants/{$tenantId}/service_orders/{$serviceOrderId}/{$ulid}.{$ext}";
        $disk = 'local';

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()) ?: '');

        $photo = ServiceOrderPhoto::create([
            'id' => Str::uuid()->toString(),
            'tenant_id' => $tenantId,
            'service_order_id' => $serviceOrderId,
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_at' => now(),
            'version' => 1,
            'last_modified_by_device' => $deviceId,
        ]);

        // Registra evento no outbox para que o pull entregue metadados ao gerente
        $syncChange = $this->syncEngine->recordChange(
            entityType: 'service_order_photo',
            entityId: $photo->id,
            action: 'create',
            payloadBefore: null,
            payloadAfter: [
                'id' => $photo->id,
                'tenant_id' => $photo->tenant_id,
                'service_order_id' => $photo->service_order_id,
                'user_id' => $photo->user_id,
                'original_filename' => $photo->original_filename,
                'mime_type' => $photo->mime_type,
                'size_bytes' => $photo->size_bytes,
                'uploaded_at' => now()->toIso8601String(),
                'version' => $photo->version,
            ],
            deviceId: $deviceId,
            userId: $user->id,
        );

        $signedUrl = $this->buildSignedUrl($photo->id, $request);

        return response()->json([
            'id' => $request->input('client_uuid'),
            'server_id' => $photo->id,
            'ulid' => $syncChange->ulid,
            'url_signed_get' => $signedUrl,
        ], 201);
    }

    public function signedUrl(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = $this->resolveTenantId($request);

        // Busca a foto garantindo tenant correto (barreira multi-tenant explícita)
        // Usa withoutGlobalScope('current_tenant') para não depender do contexto estático,
        // mas mantém o SoftDeletes scope — foto deletada retorna 404.
        $photo = ServiceOrderPhoto::withoutGlobalScope('current_tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $photo instanceof ServiceOrderPhoto) {
            return response()->json(['message' => 'Foto não encontrada.'], 404);
        }

        // Autorização via policy ($user já garantido acima via @var)
        if ($user->cannot('view', $photo)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        $url = $this->buildSignedUrl($photo->id, $request);

        return response()->json([
            'url' => $url,
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
        ]);
    }

    private function buildSignedUrl(string $photoId, Request $request): string
    {
        return url()->temporarySignedRoute(
            'mobile.sync.photo.download',
            now()->addMinutes(30),
            ['id' => $photoId],
        );
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

        throw new \RuntimeException('SyncPhotoUploadController: tenant_id não disponível.');
    }
}
