<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ClienteController extends Controller
{
    public function store(StoreClienteRequest $request): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('clientes.create', $tenantUser);

        $data = $request->validatedForStorage();
        $data['tenant_id'] = $tenant->id;
        $data['created_by'] = $tenantUser->id;
        $data['updated_by'] = $tenantUser->id;

        $cliente = Cliente::create($data);
        $cliente->refresh();

        return (new ClienteResource($cliente))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // Use withTrashed so we can find soft-deleted records too,
        // but scope to current tenant via DB query
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('clientes.delete', $tenantUser);

        $cliente = Cliente::withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $cliente->ativo) {
            return response()->json([
                'message' => 'Este cliente ja esta desativado.',
                'code' => 'cliente_ja_inativo',
            ], 409);
        }

        $cliente->ativo = false;
        $cliente->save();
        $cliente->delete(); // SoftDeletes sets deleted_at

        return response()->json([
            'message' => 'Cliente desativado com sucesso.',
            'data' => [
                'id' => $cliente->id,
                'ativo' => false,
                'updated_at' => $cliente->updated_at?->toIso8601String(),
            ],
        ], 200);
    }
}
