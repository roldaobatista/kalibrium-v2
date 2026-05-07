<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\ServiceOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ServiceOrder extends Model
{
    /** @use HasFactory<ServiceOrderFactory> */
    use HasFactory, HasUuids, ScopesToCurrentTenant, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'client_name',
        'instrument_description',
        'status',
        'mode',
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

    /** @return HasMany<ServiceOrderPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(ServiceOrderPhoto::class);
    }

    /** @return HasMany<ServiceOrderMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(ServiceOrderMember::class);
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
