<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\LgpdCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LgpdCategory extends Model
{
    /** @use HasFactory<LgpdCategoryFactory> */
    use HasFactory;

    use ScopesToCurrentTenant;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'legal_basis',
        'retention_policy',
        'comment',
        'created_by_user_id',
    ];

    /** @var list<string> */
    public const array CODES = ['identificacao', 'contato', 'financeiro', 'tecnico'];

    /** @var list<string> */
    public const array LEGAL_BASES = ['execucao_contrato', 'obrigacao_legal', 'interesse_legitimo', 'consentimento'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<ConsentRecord, $this> */
    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }
}
