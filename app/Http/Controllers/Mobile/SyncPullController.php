<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SyncChange;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SyncPullController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $cursor = $request->query('cursor', '');
        $limit = min((int) $request->query('limit', '200'), 500);

        // Validar formato ULID (26 chars Crockford Base32)
        if ($cursor !== '' && ! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $cursor)) {
            return response()->json(['message' => 'Cursor inválido.'], 422);
        }

        $tenantId = $this->resolveTenantId($request);

        $query = SyncChange::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($user): void {
                $q->where('source_user_id', $user->id)
                    ->orWhereJsonContains('payload_after->user_id', $user->id);
            })
            ->orderBy('ulid');

        if ($cursor !== '') {
            $query->where('ulid', '>', $cursor);
        }

        // Fetch limit+1 to detect has_more
        $items = $query->limit($limit + 1)->get();

        $hasMore = $items->count() > $limit;
        $items = $items->take($limit);

        $nextCursor = $hasMore ? $items->last()?->ulid : null;

        $changes = $items->map(fn (SyncChange $sc): array => [
            'ulid' => $sc->ulid,
            'entity_type' => $sc->entity_type,
            'entity_id' => $sc->entity_id,
            'action' => $sc->action,
            'payload' => $sc->payload_after ?? $sc->payload_before,
        ])->values()->all();

        return response()->json([
            'changes' => $changes,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
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

        throw new \RuntimeException('SyncPullController: tenant_id não disponível.');
    }
}
