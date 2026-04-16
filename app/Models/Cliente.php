<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    use ScopesToCurrentTenant;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'tipo_pessoa',
        'documento',
        'razao_social',
        'nome_fantasia',
        'regime_tributario',
        'limite_credito',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'telefone',
        'email',
        'observacoes',
        'ativo',
        'created_by',
        'updated_by',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'limite_credito' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<TenantUser, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    /** @return BelongsTo<TenantUser, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }
}
