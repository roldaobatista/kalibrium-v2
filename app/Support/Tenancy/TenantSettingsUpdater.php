<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Rules\Cnpj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class TenantSettingsUpdater
{
    public function __construct(
        private TenantAuditRecorder $auditRecorder,
    ) {}

    /**
     * @param  array{
     *   legal_name: string,
     *   document_number: string,
     *   trade_name?: string|null,
     *   main_email: string,
     *   phone?: string|null,
     *   operational_profile: string,
     *   emits_metrological_certificate: bool
     * }  $data
     */
    public function update(User $user, TenantUser $tenantUser, array $data, Request $request): void
    {
        DB::transaction(function () use ($user, $tenantUser, $data, $request): void {
            /** @var Tenant $tenant */
            $tenant = Tenant::query()
                ->whereKey($tenantUser->tenant_id)
                ->lockForUpdate()
                ->firstOrFail();
            $tradeName = array_key_exists('trade_name', $data) && $data['trade_name'] !== null
                ? trim((string) $data['trade_name'])
                : null;
            $phone = array_key_exists('phone', $data) && $data['phone'] !== null
                ? trim((string) $data['phone'])
                : null;

            $normalized = [
                'name' => $tradeName !== null && $tradeName !== ''
                    ? $tradeName
                    : trim($data['legal_name']),
                'legal_name' => trim($data['legal_name']),
                'document_number' => Cnpj::normalize($data['document_number']),
                'trade_name' => $tradeName,
                'main_email' => mb_strtolower(trim($data['main_email'])),
                'phone' => $phone,
                'operational_profile' => $data['operational_profile'],
                'emits_metrological_certificate' => $data['emits_metrological_certificate'],
            ];
            $tenant->forceFill($normalized)->save();

            $company = Company::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_root', true)
                ->lockForUpdate()
                ->first();
            if ($company === null) {
                $company = new Company([
                    'tenant_id' => $tenant->id,
                    'is_root' => true,
                ]);
            }

            $company->fill([
                'legal_name' => $normalized['legal_name'],
                'document_number' => $normalized['document_number'],
                'trade_name' => $normalized['trade_name'],
                'is_root' => true,
            ])->save();

            $branch = Branch::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_root', true)
                ->lockForUpdate()
                ->first();
            if ($branch === null) {
                $branch = new Branch([
                    'tenant_id' => $tenant->id,
                    'is_root' => true,
                ]);
            }

            $branch->fill([
                'company_id' => $company->id,
                'name' => $normalized['trade_name'] ?: $normalized['legal_name'],
                'document_number' => $normalized['document_number'],
                'is_root' => true,
            ])->save();

            $this->auditRecorder->record(
                $request,
                $tenant->id,
                $user->id,
                'tenant.settings.updated',
                [
                    'legal_name',
                    'document_number',
                    'trade_name',
                    'main_email',
                    'phone',
                    'operational_profile',
                    'emits_metrological_certificate',
                    'company.root',
                    'branch.root',
                ],
            );
        });
    }
}
