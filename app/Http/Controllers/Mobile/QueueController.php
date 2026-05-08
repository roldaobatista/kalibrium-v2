<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QueueController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = $this->resolveTenantId($request);

        $orders = ServiceOrder::query()
            ->withoutGlobalScope('current_tenant')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($mq) use ($user): void {
                        $mq->where('user_id', $user->id);
                    });
            })
            ->with([
                'members' => fn ($q) => $q->select('service_order_members.*'),
                'events' => fn ($q) => $q->with('user:id,name')->limit(5),
            ])
            ->orderByRaw(<<<'SQL'
                CASE status
                    WHEN 'received' THEN 1
                    WHEN 'assigned' THEN 2
                    WHEN 'in_progress' THEN 3
                    WHEN 'paused' THEN 4
                    WHEN 'dispatch_started' THEN 5
                    WHEN 'arrived_client' THEN 6
                    WHEN 'left_client' THEN 7
                    WHEN 'in_calibration' THEN 8
                    WHEN 'awaiting_approval' THEN 9
                    WHEN 'completed' THEN 10
                    WHEN 'cancelled' THEN 11
                    ELSE 12
                END ASC
            SQL)
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];
        foreach ($orders as $order) {
            $members = [];
            foreach ($order->members as $m) {
                $members[] = [
                    'user_id' => $m->user_id,
                    'role' => $m->role,
                ];
            }

            $events = [];
            foreach ($order->events as $e) {
                $events[] = [
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

            $result[] = [
                'id' => $order->id,
                'client_name' => $order->client_name,
                'instrument_description' => $order->instrument_description,
                'status' => $order->status,
                'mode' => $order->mode,
                'notes' => $order->notes,
                'created_at' => \Carbon\Carbon::parse($order->created_at)->toIso8601String(),
                'updated_at' => \Carbon\Carbon::parse($order->updated_at)->toIso8601String(),
                'members' => $members,
                'events' => $events,
            ];
        }

        return response()->json(['orders' => $result]);
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

        throw new \RuntimeException('QueueController: tenant_id não disponível.');
    }
}
