<?php

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\TenantUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'user_id',
    'company_id',
    'branch_id',
    'role',
    'status',
    'requires_2fa',
    'invited_at',
    'accepted_at',
    'invitation_token_hash',
    'invitation_expires_at',
])]
class TenantUser extends Model
{
    /** @use HasFactory<TenantUserFactory> */
    use HasFactory;

    use ScopesToCurrentTenant;

    #[\Override]
    protected function casts(): array
    {
        return [
            'requires_2fa' => 'boolean',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
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

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
