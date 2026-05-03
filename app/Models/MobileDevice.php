<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MobileDeviceStatus;
use Database\Factories\MobileDeviceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property MobileDeviceStatus $status
 * @property Carbon|null $approved_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_seen_at
 */
final class MobileDevice extends Model
{
    /** @use HasFactory<MobileDeviceFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'id',
        'user_id',
        'device_identifier',
        'device_label',
        'status',
        'approved_at',
        'approved_by_user_id',
        'revoked_at',
        'last_seen_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => MobileDeviceStatus::class,
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
