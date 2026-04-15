<?php

declare(strict_types=1);

namespace App\Support\Lgpd;

use App\Models\LgpdCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class LgpdCategoryService
{
    /**
     * Declara uma base legal para o tenant.
     *
     * @param  array<string, mixed>  $data
     * @throws ValidationException
     */
    public function declare(Tenant $tenant, User $actor, array $data): LgpdCategory
    {
        $code = (string) ($data['code'] ?? '');
        $legalBasis = (string) ($data['legal_basis'] ?? '');
        $name = strip_tags((string) ($data['name'] ?? ''));
        $comment = isset($data['comment']) ? strip_tags((string) $data['comment']) : null;
        $retentionPolicy = isset($data['retention_policy']) ? strip_tags((string) $data['retention_policy']) : null;

        if (! in_array($code, LgpdCategory::CODES, true)) {
            throw ValidationException::withMessages(['code' => 'Categoria inválida.']);
        }

        if (! in_array($legalBasis, LgpdCategory::LEGAL_BASES, true)) {
            throw ValidationException::withMessages(['legal_basis' => 'Base legal inválida.']);
        }

        // Máximo 4 bases por categoria
        $count = LgpdCategory::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('code', $code)
            ->count();

        if ($count >= 4) {
            throw ValidationException::withMessages(['legal_basis' => 'Máximo 4 bases por categoria']);
        }

        // Unicidade (tenant_id, code, legal_basis)
        $exists = LgpdCategory::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('code', $code)
            ->where('legal_basis', $legalBasis)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['legal_basis' => 'Esta base legal já está registrada para esta categoria.']);
        }

        return LgpdCategory::create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'name' => $name,
            'legal_basis' => $legalBasis,
            'retention_policy' => $retentionPolicy,
            'comment' => $comment,
            'created_by_user_id' => $actor->id,
        ]);
    }

    /**
     * Lista todas as categorias do tenant.
     *
     * @return Collection<int, LgpdCategory>
     */
    public function listForTenant(Tenant $tenant): Collection
    {
        return LgpdCategory::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('code')
            ->orderBy('legal_basis')
            ->get();
    }

    /**
     * Remove uma categoria (rejeita se houver consent_records associados).
     *
     * @throws ValidationException
     */
    public function delete(Tenant $tenant, LgpdCategory $category): void
    {
        $hasRecords = \App\Models\ConsentRecord::withoutGlobalScopes()
            ->where('lgpd_category_id', $category->id)
            ->exists();

        if ($hasRecords) {
            throw ValidationException::withMessages(['id' => 'Categoria em uso por registros de consentimento.']);
        }

        $category->delete();
    }
}
