<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final readonly class PlanSummaryService
{
    public function __construct(
        private TenantPlanMetricsUpdater $metricsUpdater,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summaryFor(Tenant $tenant): array
    {
        $metric = $this->metricsUpdater->refreshForTenant($tenant);
        $subscription = $this->subscription($tenant->id);
        $planId = (int) ($subscription['plan_id'] ?? 0);
        $planName = $this->planName($planId);
        $limits = [
            'users' => $this->limit($planId, 'users', 10),
            'monthly_os' => $this->limit($planId, 'monthly_os', 100),
            'storage' => $this->limit($planId, 'storage', 10737418240),
        ];
        $usage = [
            'users' => (int) $metric->users_used,
            'monthly_os' => (int) $metric->monthly_os_used,
            'storage' => (int) $metric->storage_used_bytes,
        ];

        return [
            'plan_name' => $planName,
            'status' => (string) ($subscription['status'] ?? $tenant->status),
            'limits' => $limits,
            'usage' => $usage,
            'percentages' => [
                'users' => $this->percent($usage['users'], $limits['users']),
                'monthly_os' => $this->percent($usage['monthly_os'], $limits['monthly_os']),
                'storage' => $this->percent($usage['storage'], $limits['storage']),
            ],
            'alerts' => $this->alerts($usage, $limits),
            'modules' => $this->modules($planId),
        ];
    }

    /**
     * @return array{plan_id:int|null,status:string}|null
     */
    private function subscription(int $tenantId): ?array
    {
        if (! Schema::hasTable('subscriptions')) {
            return null;
        }

        $subscription = DB::table('subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->first();

        if ($subscription === null) {
            return null;
        }

        return [
            'plan_id' => $subscription->plan_id === null ? null : (int) $subscription->plan_id,
            'status' => (string) $subscription->status,
        ];
    }

    private function planName(int $planId): string
    {
        if ($planId > 0 && Schema::hasTable('plans')) {
            $name = DB::table('plans')->where('id', $planId)->value('name');
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return 'Starter';
    }

    private function limit(int $planId, string $key, int $fallback): int
    {
        if ($planId > 0 && Schema::hasTable('plan_entitlements')) {
            $value = DB::table('plan_entitlements')
                ->where('plan_id', $planId)
                ->where(static function ($query) use ($key): void {
                    $query->where('limit_key', $key)
                        ->orWhere('feature_code', $key);
                })
                ->value('limit_value');

            if ($value !== null) {
                return max(1, (int) $value);
            }
        }

        return $fallback;
    }

    /**
     * @return array<int, array{code:string,name:string,enabled:bool}>
     */
    private function modules(int $planId): array
    {
        if (! Schema::hasTable('features')) {
            return [[
                'code' => 'fiscal',
                'name' => 'Modulo Fiscal',
                'enabled' => false,
            ]];
        }

        $features = DB::table('features')->orderBy('id')->get();
        if ($features->isEmpty()) {
            return [[
                'code' => 'fiscal',
                'name' => 'Modulo Fiscal',
                'enabled' => false,
            ]];
        }

        return $features->map(function (object $feature) use ($planId): array {
            $enabled = false;
            if ($planId > 0 && Schema::hasTable('plan_entitlements')) {
                $enabled = DB::table('plan_entitlements')
                    ->where('plan_id', $planId)
                    ->where(static function ($query) use ($feature): void {
                        $query->where('feature_id', $feature->id)
                            ->orWhere('feature_code', $feature->code);
                    })
                    ->where('enabled', true)
                    ->exists();
            }

            return [
                'code' => (string) $feature->code,
                'name' => (string) $feature->name,
                'enabled' => $enabled,
            ];
        })->all();
    }

    private function percent(int $used, int $limit): int
    {
        return (int) min(100, floor(($used / max(1, $limit)) * 100));
    }

    /**
     * @param  array<string, int>  $usage
     * @param  array<string, int>  $limits
     * @return array<int, array{key:string,percent:int,severity:string}>
     */
    private function alerts(array $usage, array $limits): array
    {
        $alerts = [];

        foreach ($usage as $key => $used) {
            $percent = $this->percent($used, $limits[$key]);
            if ($percent >= 95) {
                $alerts[] = ['key' => $key, 'percent' => $percent, 'severity' => 'alerta forte'];
            } elseif ($percent >= 80) {
                $alerts[] = ['key' => $key, 'percent' => $percent, 'severity' => 'alerta leve'];
            }
        }

        return $alerts;
    }
}
