<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\ConsentSubjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentSubject extends Model
{
    /** @use HasFactory<ConsentSubjectFactory> */
    use HasFactory;

    use ScopesToCurrentTenant;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'email',
        'phone',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<ConsentRecord, $this> */
    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    /** @return HasMany<RevocationToken, $this> */
    public function revocationTokens(): HasMany
    {
        return $this->hasMany(RevocationToken::class);
    }

    /**
     * Retorna true se o consent_record mais recente para o canal tiver status=ativo.
     */
    public function canReceiveOn(string $channel): bool
    {
        $latest = ConsentRecord::withoutGlobalScopes()
            ->where('consent_subject_id', $this->id)
            ->where('channel', $channel)
            ->orderByDesc('created_at')
            ->first();

        return $latest !== null && $latest->status === 'ativo';
    }

    /**
     * Scope para filtrar subjects com status informado no canal informado (registro mais recente).
     *
     * @param  Builder<ConsentSubject>  $query
     * @return Builder<ConsentSubject>
     */
    public function scopeWithConsentStatus(Builder $query, string $channel, string $status): Builder
    {
        return $query->whereExists(function ($sub) use ($channel, $status): void {
            $sub->selectRaw('1')
                ->from('consent_records as cr_inner')
                ->whereColumn('cr_inner.consent_subject_id', 'consent_subjects.id')
                ->where('cr_inner.channel', $channel)
                ->where('cr_inner.status', $status)
                ->whereRaw('cr_inner.created_at = (
                    SELECT MAX(cr2.created_at)
                    FROM consent_records cr2
                    WHERE cr2.consent_subject_id = consent_subjects.id
                      AND cr2.channel = ?
                )', [$channel]);
        });
    }
}
