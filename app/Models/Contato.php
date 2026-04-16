<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\ContatoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Contato extends Model
{
    /** @use HasFactory<ContatoFactory> */
    use HasFactory;

    use ScopesToCurrentTenant;
    use SoftDeletes;

    /**
     * Campos PII (LGPD Art. 5 I): email e whatsapp armazenados em texto plano.
     * Criptografia em repouso (ex: Laravel encrypted cast) é candidata a hardening
     * futuro — aceita como risco MVP (SEC-003, accepted risk, slice-014).
     * Base legal de tratamento: execução de contrato (LGPD Art. 7 V).
     */

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'cliente_id',
        'nome',
        'email',
        'whatsapp',
        'papel',
        'principal',
        'ativo',
        'created_by',
        'updated_by',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
            'ativo' => 'boolean',
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

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
