<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ListClientesRequest;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ClienteController extends Controller
{
    public function index(ListClientesRequest $request): AnonymousResourceCollection
    {
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('clientes.viewAny', $tenantUser);

        $search = $request->string('search')->toString();
        $tipoPessoa = $request->input('tipo_pessoa');
        $perPage = (int) ($request->input('per_page', 20));
        $sort = $request->input('sort', 'razao_social');

        // Determine if ativo filter was explicitly provided
        $ativoFilter = $request->has('ativo')
            ? filter_var($request->input('ativo'), FILTER_VALIDATE_BOOLEAN)
            : true;

        $query = Cliente::query();

        // Search filter: ILIKE on razao_social, nome_fantasia, and documento
        if ($search !== '') {
            $searchTerm = '%'.$search.'%';
            $digitsOnly = '%'.preg_replace('/\D+/', '', $search).'%';

            $query->where(function ($q) use ($searchTerm, $digitsOnly): void {
                $q->whereRaw('razao_social ILIKE ?', [$searchTerm])
                    ->orWhereRaw('nome_fantasia ILIKE ?', [$searchTerm]);

                // Only add documento filter if there are digits in the search
                $digits = preg_replace('/\D+/', '', ltrim($digitsOnly, '%'));
                $digits = rtrim($digits, '%');
                if ($digits !== '') {
                    $q->orWhereRaw('documento ILIKE ?', [$digitsOnly]);
                }
            });
        }

        // tipo_pessoa filter
        if ($tipoPessoa !== null) {
            $query->where('tipo_pessoa', $tipoPessoa);
        }

        // ativo filter (default: true)
        $query->where('ativo', $ativoFilter);

        // Sorting
        $sortMap = [
            'razao_social' => ['razao_social', 'asc'],
            '-razao_social' => ['razao_social', 'desc'],
            'created_at' => ['created_at', 'asc'],
            '-created_at' => ['created_at', 'desc'],
        ];

        [$column, $direction] = $sortMap[$sort] ?? ['razao_social', 'asc'];
        $query->orderBy($column, $direction);

        $paginator = $query->paginate($perPage)->withQueryString();

        return ClienteResource::collection($paginator);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('clientes.view', $tenantUser);

        // Global scope ScopesToCurrentTenant ensures 404 for cross-tenant access
        $cliente = Cliente::findOrFail($id);

        $resource = new ClienteResource($cliente);
        $resource->showDetail = true;

        return $resource->response()->setStatusCode(200);
    }

    public function update(UpdateClienteRequest $request, int $id): JsonResponse
    {
        $tenantUser = $request->attributes->get('current_tenant_user');

        Gate::authorize('clientes.update', $tenantUser);

        // Global scope ScopesToCurrentTenant ensures 404 for cross-tenant access
        $cliente = Cliente::findOrFail($id);

        $cliente->fill($request->validatedForStorage());
        $cliente->updated_by = $tenantUser->id;
        $cliente->save();

        $resource = new ClienteResource($cliente);

        return $resource->response()->setStatusCode(200);
    }

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
