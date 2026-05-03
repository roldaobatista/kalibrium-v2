<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\SyncChangeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SyncChange extends Model
{
    /** @use HasFactory<SyncChangeFactory> */
    use HasFactory, ScopesToCurrentTenant;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'ulid',
        'tenant_id',
        'entity_type',
        'entity_id',
        'action',
        'payload_before',
        'payload_after',
        'source_device_id',
        'source_user_id',
        'applied_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload_before' => 'array',
            'payload_after' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }
}
