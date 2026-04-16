<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreContatoRequest;
use App\Http\Requests\UpdateContatoRequest;
use App\Http\Resources\ContatoResource;
use App\Models\Cliente;
use App\Models\Contato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ContatoController extends Controller
{
    public function index(Request $request, int $clienteId): AnonymousResourceCollection
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('contatos.viewAny', $tenantUser);

        // Verifica que o cliente pertence ao tenant atual (404 se não)
        $cliente = Cliente::where('tenant_id', $tenant->id)
            ->where('id', $clienteId)
            ->firstOrFail();

        $contatos = Contato::where('cliente_id', $cliente->id)
            ->where('ativo', true)
            ->get();

        return ContatoResource::collection($contatos);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('contatos.view', $tenantUser);

        // where tenant_id explícito (defense-in-depth, mesmo padrão do destroy())
        $contato = Contato::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        return (new ContatoResource($contato))
            ->response()
            ->setStatusCode(200);
    }

    public function store(StoreContatoRequest $request, int $clienteId): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('contatos.create', $tenantUser);

        // Verifica que o cliente pertence ao tenant atual (404 se não)
        $cliente = Cliente::where('tenant_id', $tenant->id)
            ->where('id', $clienteId)
            ->firstOrFail();

        $data = $request->validated();
        $data['tenant_id'] = $tenant->id;
        $data['cliente_id'] = $cliente->id;
        $data['created_by'] = $tenantUser->user_id;
        $data['updated_by'] = $tenantUser->user_id;

        $contato = Contato::create($data);
        $contato->refresh();

        return (new ContatoResource($contato))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateContatoRequest $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('contatos.update', $tenantUser);

        // where tenant_id explícito (defense-in-depth, mesmo padrão do destroy())
        $contato = Contato::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validated();

        $contato->fill($data);
        $contato->updated_by = $tenantUser->user_id;
        $contato->save();

        return (new ContatoResource($contato))
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('contatos.delete', $tenantUser);

        // withTrashed para detectar contato já inativo (409)
        // where tenant_id explícito para isolar (404 cross-tenant)
        $contato = Contato::withTrashed()
            ->where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $contato->ativo) {
            return response()->json([
                'message' => 'Este contato ja esta desativado.',
                'code' => 'contato_ja_inativo',
            ], 409);
        }

        $contato->ativo = false;
        $contato->updated_by = $tenantUser->user_id;
        $contato->save();
        $contato->delete(); // SoftDeletes sets deleted_at

        return response()->json([
            'message' => 'Contato desativado com sucesso.',
            'data' => [
                'id' => $contato->id,
                'ativo' => false,
                'updated_at' => $contato->updated_at?->toIso8601String(),
            ],
        ], 200);
    }
}
