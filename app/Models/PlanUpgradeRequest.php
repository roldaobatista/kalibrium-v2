<?php

namespace App\Models;

use Database\Factories\PlanUpgradeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'user_id',
    'feature_code',
    'justification',
    'status',
    'requested_at',
])]
class PlanUpgradeRequest extends Model
{
    /** @use HasFactory<PlanUpgradeRequestFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
