<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ServiceOrder extends Model
{
    use HasUuids, ScopesToCurrentTenant, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'client_name',
        'instrument_description',
        'status',
        'notes',
        'version',
        'last_modified_by_device',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
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
