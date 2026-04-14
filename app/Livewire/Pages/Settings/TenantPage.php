<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Settings;

use App\Models\Company;
use App\Models\User;
use App\Support\Tenancy\CurrentTenantResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

final class TenantPage extends Component
{
    /** @var array<string, mixed> */
    public array $form = [
        'legal_name' => '',
        'document_number' => '',
        'trade_name' => '',
        'main_email' => '',
        'phone' => '',
        'operational_profile' => 'basic',
        'emits_metrological_certificate' => false,
    ];

    public bool $readOnly = false;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $resolver = app(CurrentTenantResolver::class);
        $context = $resolver->resolve($user);
        $resolver->assertManager($context);

        $tenant = $context['tenant'];
        $rootCompany = Company::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_root', true)
            ->first();
        $rootCompanyDocumentNumber = $rootCompany instanceof Company ? $rootCompany->document_number : null;
        $rootCompanyTradeName = $rootCompany instanceof Company ? $rootCompany->trade_name : null;
        $certificateDefault = old(
            'emits_metrological_certificate',
            $tenant->emits_metrological_certificate ? '1' : '0',
        );

        $this->readOnly = $context['access_mode'] === 'read-only'
            || session('tenant.access_mode') === 'read-only';
        $this->form = [
            'legal_name' => old('legal_name', $tenant->legal_name ?? $tenant->name),
            'document_number' => old('document_number', $tenant->document_number ?? $rootCompanyDocumentNumber ?? ''),
            'trade_name' => old('trade_name', $tenant->trade_name ?? $rootCompanyTradeName ?? ''),
            'main_email' => old('main_email', $tenant->main_email ?? ''),
            'phone' => old('phone', $tenant->phone ?? ''),
            'operational_profile' => old('operational_profile', $tenant->operational_profile ?? 'basic'),
            'emits_metrological_certificate' => in_array($certificateDefault, [true, 1, '1', 'on'], true),
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.settings.tenant-page')
            ->layout('layouts.app');
    }
}
