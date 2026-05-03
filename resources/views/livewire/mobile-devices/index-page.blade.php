<div>
    {{-- Breadcrumb --}}
    <nav class="text-sm text-neutral-500 mb-4 font-medium" aria-label="Caminho de navegação">
        <a href="/" class="hover:text-primary-600 transition-colors">Início</a>
        <span class="mx-2 text-neutral-300" aria-hidden="true">›</span>
        <span class="text-neutral-700">Celulares dos técnicos</span>
    </nav>

    {{-- Cabeçalho da página --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Celulares dos técnicos</h1>
            <p class="text-sm text-neutral-500 mt-1">
                {{ $devicesCount }} {{ $devicesCount === 1 ? 'registro' : 'registros' }}
            </p>
        </div>
    </div>

    {{-- Filtros + busca --}}
    <div class="flex flex-wrap gap-3 mb-6 items-center">
        <select
            wire:model.live="statusFilter"
            class="rounded-md border border-neutral-300 px-3 py-2 text-sm bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-colors"
        >
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por técnico ou e-mail"
            class="flex-1 min-w-[240px] rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-colors"
        />
    </div>

    {{-- =========================================================
         TABELA — desktop / tablet (>= md)
    ========================================================== --}}
    <div class="hidden md:block bg-white border border-neutral-200 rounded-lg overflow-hidden shadow-sm">
        <table class="w-full">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800">Técnico</th>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800">Modelo do celular</th>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800">Status</th>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800 hidden lg:table-cell">Última atividade</th>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800 hidden xl:table-cell">Aprovado por</th>
                    <th class="text-right px-3 py-3.5 text-sm font-semibold text-neutral-800">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($devices as $device)
                    <tr wire:key="{{ $device->id }}" class="hover:bg-neutral-50 transition-colors">
                        <td class="px-3 py-3 text-sm">
                            <div class="font-medium text-neutral-900">{{ $device->user?->name ?? '—' }}</div>
                            <div class="text-neutral-500 text-xs">{{ $device->user?->email ?? '' }}</div>
                        </td>
                        <td class="px-3 py-3 text-sm text-neutral-700">
                            {{ $device->device_label ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-sm">
                            @if ($device->status->value === 'pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-700">
                                    Aguardando
                                </span>
                            @elseif ($device->status->value === 'approved')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">
                                    Aprovado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-700">
                                    Bloqueado
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm text-neutral-600 hidden lg:table-cell">
                            {{ $device->last_seen_at?->locale('pt_BR')->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-sm text-neutral-600 hidden xl:table-cell">
                            @if ($device->approved_at)
                                <div>{{ $device->approver?->name ?? '—' }}</div>
                                <div class="text-xs text-neutral-500">{{ $device->approved_at->format('d/m/Y H:i') }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($device->status->value === 'pending')
                                    <button
                                        wire:click="aprovar('{{ $device->id }}')"
                                        wire:loading.attr="disabled"
                                        class="bg-success-700 text-white px-3 py-1.5 rounded-md text-xs font-medium hover:bg-success-700 transition-colors disabled:opacity-50"
                                    >
                                        Aprovar
                                    </button>
                                    <button
                                        wire:click="recusar('{{ $device->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="Tem certeza que quer recusar o pedido de {{ $device->user?->name ?? 'este técnico' }}? O celular não vai conseguir entrar até ser reativado."
                                        class="bg-white border border-danger-600 text-danger-700 px-3 py-1.5 rounded-md text-xs font-medium hover:bg-danger-50 transition-colors disabled:opacity-50"
                                    >
                                        Recusar
                                    </button>
                                @elseif ($device->status->value === 'approved')
                                    <button
                                        wire:click="bloquear('{{ $device->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="Tem certeza que quer bloquear o celular de {{ $device->user?->name ?? 'este técnico' }}? Ele não vai conseguir entrar até você reativar."
                                        class="bg-white border border-danger-600 text-danger-700 px-3 py-1.5 rounded-md text-xs font-medium hover:bg-danger-50 transition-colors disabled:opacity-50"
                                    >
                                        Bloquear
                                    </button>
                                @else
                                    <button
                                        wire:click="reativar('{{ $device->id }}')"
                                        wire:loading.attr="disabled"
                                        class="bg-white border border-neutral-300 text-neutral-700 px-3 py-1.5 rounded-md text-xs font-medium hover:bg-neutral-50 transition-colors disabled:opacity-50"
                                    >
                                        Reativar
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-12 text-center text-neutral-500 text-sm">
                            Nenhum técnico pediu acesso pelo celular ainda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- =========================================================
         CARDS — mobile (< md)
    ========================================================== --}}
    <div class="md:hidden space-y-3">
        @forelse ($devices as $device)
            <div wire:key="card-{{ $device->id }}" class="bg-white rounded-lg border border-neutral-200 shadow-sm p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-neutral-900 truncate">{{ $device->user?->name ?? '—' }}</p>
                        <p class="text-xs text-neutral-500 truncate">{{ $device->user?->email ?? '' }}</p>
                        @if ($device->device_label)
                            <p class="text-xs text-neutral-600 mt-0.5">{{ $device->device_label }}</p>
                        @endif
                    </div>
                    {{-- Badge de status --}}
                    @if ($device->status->value === 'pending')
                        <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-700">
                            Aguardando
                        </span>
                    @elseif ($device->status->value === 'approved')
                        <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">
                            Aprovado
                        </span>
                    @else
                        <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-700">
                            Bloqueado
                        </span>
                    @endif
                </div>

                @if ($device->last_seen_at)
                    <p class="text-xs text-neutral-400 mt-2">
                        Último acesso: {{ $device->last_seen_at->locale('pt_BR')->diffForHumans() }}
                    </p>
                @endif

                {{-- Botões de ação --}}
                <div class="flex flex-wrap gap-2 mt-3">
                    @if ($device->status->value === 'pending')
                        <button
                            wire:click="aprovar('{{ $device->id }}')"
                            wire:loading.attr="disabled"
                            class="flex-1 min-h-[44px] bg-success-700 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-success-700 transition-colors disabled:opacity-50"
                        >
                            Aprovar
                        </button>
                        <button
                            wire:click="recusar('{{ $device->id }}')"
                            wire:loading.attr="disabled"
                            wire:confirm="Tem certeza que quer recusar o pedido de {{ $device->user?->name ?? 'este técnico' }}? O celular não vai conseguir entrar até ser reativado."
                            class="flex-1 min-h-[44px] bg-white border border-danger-600 text-danger-700 px-3 py-2 rounded-md text-sm font-medium hover:bg-danger-50 transition-colors disabled:opacity-50"
                        >
                            Recusar
                        </button>
                    @elseif ($device->status->value === 'approved')
                        <button
                            wire:click="bloquear('{{ $device->id }}')"
                            wire:loading.attr="disabled"
                            wire:confirm="Tem certeza que quer bloquear o celular de {{ $device->user?->name ?? 'este técnico' }}? Ele não vai conseguir entrar até você reativar."
                            class="w-full min-h-[44px] bg-white border border-danger-600 text-danger-700 px-3 py-2 rounded-md text-sm font-medium hover:bg-danger-50 transition-colors disabled:opacity-50"
                        >
                            Bloquear
                        </button>
                    @else
                        <button
                            wire:click="reativar('{{ $device->id }}')"
                            wire:loading.attr="disabled"
                            class="w-full min-h-[44px] bg-white border border-neutral-300 text-neutral-700 px-3 py-2 rounded-md text-sm font-medium hover:bg-neutral-50 transition-colors disabled:opacity-50"
                        >
                            Reativar
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg border border-dashed border-neutral-300 px-6 py-12 text-center text-sm text-neutral-500">
                Nenhum técnico pediu acesso pelo celular ainda.
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    @if ($devices->hasPages())
        <div class="mt-4">
            {{ $devices->links() }}
        </div>
    @endif
</div>
