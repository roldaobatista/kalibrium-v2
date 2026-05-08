<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TimelineController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = $this->resolveTenantId($request);

        // Triple-barrier: validar tenant + ownership/membership + entity_id
        $order = ServiceOrder::query()
            ->withoutGlobalScope('current_tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($mq) use ($user): void {
                        $mq->where('user_id', $user->id);
                    });
            })
            ->first();

        if (! $order instanceof ServiceOrder) {
            return response()->json(['message' => 'Não encontrado.'], 404);
        }

        $events = ServiceOrderEvent::query()
            ->where('service_order_id', $id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];
        foreach ($events as $e) {
            $result[] = [
                'id' => $e->id,
                'user_id' => $e->user_id,
                'user_name' => $e->user?->name,
                'event_type' => $e->event_type,
                'old_value' => $e->old_value,
                'new_value' => $e->new_value,
                'metadata' => $e->metadata,
                'created_at' => \Carbon\Carbon::parse($e->created_at)->toIso8601String(),
            ];
        }

        return response()->json(['events' => $result]);
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

        throw new \RuntimeException('TimelineController: tenant_id não disponível.');
    }
}
