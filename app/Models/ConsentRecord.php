<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\ConsentRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConsentRecord extends Model
{
    /** @use HasFactory<ConsentRecordFactory> */
    use HasFactory;

    use ScopesToCurrentTenant;

    /**
     * Desabilitar timestamps automáticos: evita UPDATE implícito via updated_at.
     * created_at é gerenciado manualmente pelo service.
     */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'consent_subject_id',
        'lgpd_category_id',
        'channel',
        'status',
        'granted_at',
        'revoked_at',
        'ip_address',
        'user_agent_hash',
        'revocation_reason',
        'created_at',
        'updated_at',
    ];

    /** @var list<string> */
    public const array REVOCATION_REASONS = [
        'automated',
        'privacy_concern',
        'duplicate_contact',
        'no_longer_interested',
        'other_without_details',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'granted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ConsentSubject, $this> */
    public function consentSubject(): BelongsTo
    {
        return $this->belongsTo(ConsentSubject::class);
    }

    /** @return BelongsTo<LgpdCategory, $this> */
    public function lgpdCategory(): BelongsTo
    {
        return $this->belongsTo(LgpdCategory::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  Builder<ConsentRecord>  $query
     * @return Builder<ConsentRecord>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ativo');
    }

    /**
     * @param  Builder<ConsentRecord>  $query
     * @return Builder<ConsentRecord>
     */
    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', 'revogado');
    }
}
