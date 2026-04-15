<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\TenantAuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'user_id',
    'action',
    'changed_fields',
    'ip_address',
    'user_agent_hash',
])]
class TenantAuditLog extends Model
{
    /** @use HasFactory<TenantAuditLogFactory> */
    use HasFactory, ScopesToCurrentTenant;

    #[\Override]
    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
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
