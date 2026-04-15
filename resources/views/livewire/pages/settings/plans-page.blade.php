<section class="mx-auto max-w-5xl space-y-6 p-6">
    <div class="space-y-2">
        <h1 class="text-2xl font-semibold">Plano atual</h1>
        <p class="text-sm text-slate-700">Alertas em 80% e 95% ajudam a acompanhar limites.</p>
    </div>

    @if (session('status'))
        <p class="rounded border border-emerald-400 bg-emerald-50 p-3 text-sm">
            {{ session('status') }}
        </p>
    @endif

    @if ($readOnly)
        <p class="rounded border border-amber-400 bg-amber-50 p-3 text-sm">
            Este laboratorio esta em modo somente leitura.
        </p>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        @php
            $planStatusLabels = [
                'active' => 'Ativo',
                'trial' => 'Em avaliacao',
                'suspended' => 'Suspenso',
                'cancelled' => 'Cancelado',
                'past_due' => 'Inadimplente',
            ];
        @endphp
        <div class="rounded border border-slate-300 p-4">
            <p class="text-sm text-slate-600">Plano atual</p>
            <p class="text-xl font-semibold">{{ $summary['plan_name'] }}</p>
            <p class="text-sm">Status: {{ $planStatusLabels[$summary['status']] ?? ucfirst((string) $summary['status']) }}</p>
        </div>
        <div class="rounded border border-slate-300 p-4">
            <p class="text-sm text-slate-600">Usuarios</p>
            <p>{{ $summary['usage']['users'] }} de {{ $summary['limits']['users'] }}</p>
            <p>{{ $summary['percentages']['users'] }}%</p>
        </div>
        <div class="rounded border border-slate-300 p-4">
            <p class="text-sm text-slate-600">OS no mes</p>
            <p>{{ $summary['usage']['monthly_os'] }} de {{ $summary['limits']['monthly_os'] }}</p>
            <p>{{ $summary['percentages']['monthly_os'] }}%</p>
        </div>
        <div class="rounded border border-slate-300 p-4">
            <p class="text-sm text-slate-600">Armazenamento</p>
            <p>{{ $summary['usage']['storage'] }} de {{ $summary['limits']['storage'] }}</p>
            <p>{{ $summary['percentages']['storage'] }}%</p>
        </div>
    </div>

    @php
        $severityLabels = ['warning' => 'Atencao', 'critical' => 'Critico'];
        $metricLabels = ['monthly_os' => 'Ordens de servico no mes', 'storage_gb' => 'Armazenamento', 'storage' => 'Armazenamento', 'users' => 'Usuarios'];
    @endphp
    @foreach ($summary['alerts'] as $alert)
        <p class="rounded border border-amber-400 bg-amber-50 p-3 text-sm">
            {{ $severityLabels[$alert['severity']] ?? ucfirst((string) $alert['severity']) }}: {{ $metricLabels[$alert['key']] ?? $alert['key'] }} em {{ $alert['percent'] }}% do limite atingido
        </p>
    @endforeach

    <div class="space-y-3">
        <h2 class="text-lg font-semibold">Modulos do plano</h2>
        @foreach ($summary['modules'] as $module)
            <div class="flex items-center justify-between border-b border-slate-200 py-2">
                <span>{{ $module['name'] }}</span>
                <span>{{ $module['enabled'] ? 'ativo' : 'fora do plano' }}</span>
                @if ($canRequestUpgrade && ! $module['enabled'])
                    <button wire:click="requestUpgrade(@js($module['code']), @js('Solicitado pela tela de planos.'))" class="rounded bg-slate-900 px-3 py-2 text-sm font-medium text-white">
                        Pedir upgrade
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</section>
