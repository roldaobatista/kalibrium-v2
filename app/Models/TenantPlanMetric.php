<?php

namespace App\Models;

use Database\Factories\TenantPlanMetricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'users_used',
    'monthly_os_used',
    'storage_used_bytes',
    'sampled_at',
])]
class TenantPlanMetric extends Model
{
    /** @use HasFactory<TenantPlanMetricFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'users_used' => 'integer',
            'monthly_os_used' => 'integer',
            'storage_used_bytes' => 'integer',
            'sampled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
