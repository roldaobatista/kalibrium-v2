<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenantId = $this->resolveTenantId($request);

        $members = TenantUser::query()
            ->with('user:id,name,email')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->map(function (TenantUser $tu): ?array {
                $user = $tu->user;
                if ($user === null) {
                    return null;
                }

                return [
                    'id' => $tu->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $tu->role,
                ];
            })
            ->filter()
            ->values();

        return response()->json(['members' => $members]);
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

        throw new \RuntimeException('TeamController: tenant_id não disponível.');
    }
}
