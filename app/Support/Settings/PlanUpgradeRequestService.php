<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\PlanUpgradeRequest;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Settings\Concerns\AuthorizesTenantSettings;
use App\Support\Tenancy\TenantAuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class PlanUpgradeRequestService
{
    use AuthorizesTenantSettings;

    public function __construct(
        private TenantAuditRecorder $auditRecorder,
    ) {}

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function requestUpgrade(
        User $actor,
        TenantUser $actorTenantUser,
        string $featureCode,
        ?string $justification = null,
    ): PlanUpgradeRequest {
        $tenant = $this->assertActiveManager($actor, $actorTenantUser);
        $data = Validator::make([
            'feature_code' => $featureCode,
            'justification' => $justification,
        ], [
            'feature_code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.-]+$/'],
            'justification' => ['nullable', 'string', 'max:1000'],
        ])->validate();
        if (isset($data['justification']) && preg_match('/<[^>]*>|drop\s+table|or\s+1\s*=\s*1/i', (string) $data['justification']) === 1) {
            throw ValidationException::withMessages([
                'justification' => 'Justificativa invalida.',
            ]);
        }
        $this->assertRequestableFeature((int) $tenant->id, strtolower((string) $data['feature_code']));

        return DB::transaction(function () use ($actor, $tenant, $data): PlanUpgradeRequest {
            $request = PlanUpgradeRequest::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $actor->id,
                'feature_code' => strtolower((string) $data['feature_code']),
                'justification' => isset($data['justification'])
                    ? strip_tags((string) $data['justification'])
                    : null,
                'status' => 'requested',
                'requested_at' => now(),
            ]);

            $this->auditRecorder->record(
                request(),
                (int) $tenant->id,
                $actor->id,
                'tenant.plan.upgrade_requested',
                ['feature_code', 'justification', 'status'],
            );

            return $request;
        });
    }

    /**
     * @throws ValidationException
     */
    private function assertRequestableFeature(int $tenantId, string $featureCode): void
    {
        if (! Schema::hasTable('features')) {
            throw ValidationException::withMessages(['feature_code' => 'Modulo indisponivel.']);
        }

        $feature = DB::table('features')->where('code', $featureCode)->first();
        if ($feature === null) {
            throw ValidationException::withMessages(['feature_code' => 'Modulo indisponivel.']);
        }

        if (! Schema::hasTable('subscriptions') || ! Schema::hasTable('plan_entitlements')) {
            return;
        }

        $planId = DB::table('subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->value('plan_id');

        if ($planId === null) {
            return;
        }

        $alreadyEnabled = DB::table('plan_entitlements')
            ->where('plan_id', $planId)
            ->where(static function ($query) use ($feature, $featureCode): void {
                $query->where('feature_id', $feature->id)
                    ->orWhere('feature_code', $featureCode);
            })
            ->where('enabled', true)
            ->exists();

        if ($alreadyEnabled) {
            throw ValidationException::withMessages(['feature_code' => 'Modulo ja incluido no plano atual.']);
        }
    }
}
