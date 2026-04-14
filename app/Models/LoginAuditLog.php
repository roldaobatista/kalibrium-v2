<?php

namespace App\Models;

use Database\Factories\LoginAuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event',
    'user_id',
    'tenant_id',
    'ip_address',
    'user_agent_hash',
    'context',
])]
class LoginAuditLog extends Model
{
    /** @use HasFactory<LoginAuditLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
