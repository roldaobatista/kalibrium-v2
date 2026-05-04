<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopesToCurrentTenant;
use Database\Factories\ServiceOrderPhotoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ServiceOrderPhoto extends Model
{
    /** @use HasFactory<ServiceOrderPhotoFactory> */
    use HasFactory, HasUuids, ScopesToCurrentTenant, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'id',
        'tenant_id',
        'service_order_id',
        'user_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'uploaded_at',
        'version',
        'last_modified_by_device',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'version' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ServiceOrder, $this> */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
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
