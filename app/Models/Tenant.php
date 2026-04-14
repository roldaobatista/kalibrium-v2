<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'legal_name',
    'document_number',
    'trade_name',
    'main_email',
    'phone',
    'operational_profile',
    'emits_metrological_certificate',
    'status',
])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'emits_metrological_certificate' => 'boolean',
        ];
    }

    /** @return Attribute<string|null, string|null> */
    protected function documentNumber(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value === null
                ? null
                : preg_replace('/\D+/', '', $value),
        );
    }

    /** @return HasMany<TenantUser, $this> */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /** @return HasMany<Company, $this> */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /** @return HasMany<Branch, $this> */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /** @return HasMany<TenantAuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(TenantAuditLog::class);
    }

    /** @return HasMany<PlanUpgradeRequest, $this> */
    public function planUpgradeRequests(): HasMany
    {
        return $this->hasMany(PlanUpgradeRequest::class);
    }

    /** @return HasMany<TenantPlanMetric, $this> */
    public function planMetrics(): HasMany
    {
        return $this->hasMany(TenantPlanMetric::class);
    }
}
