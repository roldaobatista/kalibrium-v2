<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\SyncPushRequest;
use App\Models\Note;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SyncEngine;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SyncPushController extends Controller
{
    public function __construct(private readonly SyncEngine $syncEngine) {}

    public function __invoke(SyncPushRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $deviceId = $request->string('device_id')->toString();

        $applied = [];
        $rejected = [];

        foreach ($request->input('changes', []) as $change) {
            $localId = (string) $change['local_id'];
            $entityType = (string) $change['entity_type'];
            $entityId = (string) $change['entity_id'];
            $action = (string) $change['action'];
            /** @var array<string, mixed> $payload */
            $payload = (array) $change['payload'];
            $incomingUpdatedAt = Carbon::parse((string) $payload['updated_at']);

            try {
                $result = DB::transaction(function () use (
                    $user, $deviceId, $localId, $entityType, $entityId, $action, $payload, $incomingUpdatedAt,
                ): array {
                    return match ($entityType) {
                        'note' => $this->handleNote(
                            $user, $deviceId, $localId, $entityId, $action, $payload, $incomingUpdatedAt,
                        ),
                        default => ['rejected' => ['local_id' => $localId, 'reason' => 'unknown_entity_type']],
                    };
                });

                if (isset($result['applied'])) {
                    $applied[] = $result['applied'];
                } else {
                    $rejected[] = $result['rejected'];
                }
            } catch (\Throwable) {
                $rejected[] = ['local_id' => $localId, 'reason' => 'internal_error'];
            }
        }

        return response()->json(compact('applied', 'rejected'));
    }

    private function resolveTenantId(): int
    {
        $tenant = request()->attributes->get('current_tenant');
        if ($tenant instanceof Tenant) {
            return (int) $tenant->id;
        }

        $id = TenantContext::getTenantId();
        if ($id !== null) {
            return $id;
        }

        throw new \RuntimeException('SyncPushController: tenant_id não disponível.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{applied: array<string, mixed>}|array{rejected: array<string, mixed>}
     */
    private function handleNote(
        User $user,
        string $deviceId,
        string $localId,
        string $entityId,
        string $action,
        array $payload,
        Carbon $incomingUpdatedAt,
    ): array {
        if ($action === 'create') {
            $newId = Str::uuid()->toString();
            $title = (string) ($payload['title'] ?? '');
            $body = (string) ($payload['body'] ?? '');

            $note = Note::create([
                'id' => $newId,
                'tenant_id' => $this->resolveTenantId(),
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'version' => 1,
                'last_modified_by_device' => $deviceId,
                'created_at' => $incomingUpdatedAt,
                'updated_at' => $incomingUpdatedAt,
            ]);

            $syncChange = $this->syncEngine->recordChange(
                entityType: 'note',
                entityId: $note->id,
                action: 'create',
                payloadBefore: null,
                payloadAfter: $note->toArray(),
                deviceId: $deviceId,
                userId: $user->id,
            );

            return ['applied' => [
                'local_id' => $localId,
                'server_id' => $note->id,
                'ulid' => $syncChange->ulid,
                'version' => $note->version,
            ]];
        }

        // update or delete — triple barrier: tenant + user + id (nunca depender só do global scope)
        $note = Note::where('id', $entityId)
            ->where('tenant_id', $this->resolveTenantId())
            ->where('user_id', $user->id)
            ->first();

        if (! $note instanceof Note) {
            return ['rejected' => ['local_id' => $localId, 'reason' => 'not_found']];
        }

        if ($action === 'update') {
            // Last-write-wins: reject if incoming is older
            if ($incomingUpdatedAt->lessThan($note->updated_at)) {
                return ['rejected' => [
                    'local_id' => $localId,
                    'reason' => 'stale_update',
                    'current_updated_at' => $note->updated_at?->toIso8601String(),
                ]];
            }

            $before = $note->toArray();

            $note->title = (string) ($payload['title'] ?? $note->title);
            $note->body = (string) ($payload['body'] ?? $note->body);
            $note->version += 1;
            $note->last_modified_by_device = $deviceId;
            $note->updated_at = $incomingUpdatedAt;
            $note->saveQuietly();

            $syncChange = $this->syncEngine->recordChange(
                entityType: 'note',
                entityId: $note->id,
                action: 'update',
                payloadBefore: $before,
                payloadAfter: $note->toArray(),
                deviceId: $deviceId,
                userId: $user->id,
            );

            return ['applied' => [
                'local_id' => $localId,
                'server_id' => $note->id,
                'ulid' => $syncChange->ulid,
                'version' => $note->version,
            ]];
        }

        if ($action === 'delete') {
            $before = $note->toArray();
            $note->delete(); // soft delete

            $syncChange = $this->syncEngine->recordChange(
                entityType: 'note',
                entityId: $entityId,
                action: 'delete',
                payloadBefore: $before,
                payloadAfter: null,
                deviceId: $deviceId,
                userId: $user->id,
            );

            return ['applied' => [
                'local_id' => $localId,
                'server_id' => $entityId,
                'ulid' => $syncChange->ulid,
                'version' => $note->version,
            ]];
        }

        return ['rejected' => ['local_id' => $localId, 'reason' => 'unknown_action']];
    }
}
