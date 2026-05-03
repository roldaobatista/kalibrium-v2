<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Celulares dos técnicos</h1>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por nome ou e-mail..."
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:max-w-xs"
        />

        <select
            wire:model.live="statusFilter"
            class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tabela --}}
    @if ($devices->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 px-6 py-12 text-center text-sm text-gray-500">
            Nenhum técnico pediu acesso pelo celular ainda.
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Técnico</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Celular</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Última atividade</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Aprovado por</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($devices as $device)
                        <tr wire:key="{{ $device->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $device->user?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $device->user?->email ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $device->device_label ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($device->status->value === 'pending')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                        Aguardando aprovação
                                    </span>
                                @elseif ($device->status->value === 'approved')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                        Aprovado
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                        Bloqueado
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $device->last_seen_at?->locale('pt_BR')->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                @if ($device->approver)
                                    <div>{{ $device->approver->name }}</div>
                                    <div class="text-xs">{{ $device->approved_at?->locale('pt_BR')->diffForHumans() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($device->status->value === 'pending')
                                        <button
                                            wire:click="aprovar('{{ $device->id }}')"
                                            wire:loading.attr="disabled"
                                            class="rounded bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700 disabled:opacity-50"
                                        >
                                            Aprovar
                                        </button>
                                        <button
                                            wire:click="recusar('{{ $device->id }}')"
                                            wire:loading.attr="disabled"
                                            class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                        >
                                            Recusar
                                        </button>
                                    @elseif ($device->status->value === 'approved')
                                        <button
                                            wire:click="bloquear('{{ $device->id }}')"
                                            wire:loading.attr="disabled"
                                            class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                        >
                                            Bloquear
                                        </button>
                                    @else
                                        <button
                                            wire:click="reativar('{{ $device->id }}')"
                                            wire:loading.attr="disabled"
                                            class="rounded bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                        >
                                            Reativar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $devices->links() }}
        </div>
    @endif
</div>
