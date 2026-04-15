<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Carbon\CarbonImmutable;
use Database\Factories\RevocationTokenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $granted_at
 * @property Carbon|null $used_at
 */
class RevocationToken extends Model
{
    /** @use HasFactory<RevocationTokenFactory> */
    use HasFactory;

    use ScopesToCurrentTenant;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'consent_subject_id',
        'channel',
        'token_hash',
        'expires_at',
        'granted_at',
        'used_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'granted_at' => 'immutable_datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ConsentSubject, $this> */
    public function consentSubject(): BelongsTo
    {
        return $this->belongsTo(ConsentSubject::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  Builder<RevocationToken>  $query
     * @return Builder<RevocationToken>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }
}
