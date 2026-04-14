<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\Cnpj;
use App\Support\Tenancy\CurrentTenantResolver;
use App\Support\Tenancy\TenantSettingsUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TenantSettingsController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentTenantResolver $resolver,
        TenantSettingsUpdater $updater,
    ): RedirectResponse {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $context = $resolver->resolve($user);
        $resolver->assertManager($context);

        if ($context['access_mode'] === 'read-only' || $request->attributes->get('tenant_read_only') === true) {
            abort(403, 'Conta em modo somente leitura.');
        }

        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'document_number' => ['required', 'string', 'max:32', new Cnpj($context['tenant']->id)],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'main_email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'operational_profile' => ['required', 'string', Rule::in(['basic', 'intermediate', 'accredited'])],
            'emits_metrological_certificate' => ['sometimes', 'boolean'],
        ]);
        $data['emits_metrological_certificate'] = $request->boolean('emits_metrological_certificate');

        $updater->update($user, $context['tenant_user'], $data, $request);

        return redirect('/settings/tenant')->with('status', 'Dados do laboratorio salvos.');
    }
}
