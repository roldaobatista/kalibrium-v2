<div>
    {{-- Breadcrumb --}}
    <nav class="text-sm text-neutral-500 mb-4 font-medium" aria-label="Caminho de navegação">
        <a href="/" class="hover:text-primary-600 transition-colors">Início</a>
        <span class="mx-2 text-neutral-300" aria-hidden="true">›</span>
        <span class="text-neutral-700">Técnicos</span>
    </nav>

    {{-- Cabeçalho da página --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Técnicos</h1>
            <p class="text-sm text-neutral-500 mt-1">
                {{ $techniciansCount }} {{ $techniciansCount === 1 ? 'técnico' : 'técnicos' }}
            </p>
        </div>
        <button
            wire:click="openCreateModal"
            class="inline-flex items-center gap-2 bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-800 transition-colors"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Cadastrar técnico
        </button>
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
            placeholder="Buscar por nome ou e-mail"
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
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800">Nome</th>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800">E-mail</th>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800">Status</th>
                    <th class="text-left px-3 py-3.5 text-sm font-semibold text-neutral-800 hidden lg:table-cell">Cadastrado em</th>
                    <th class="text-right px-3 py-3.5 text-sm font-semibold text-neutral-800">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($technicians as $tenantUser)
                    <tr wire:key="{{ $tenantUser->id }}" class="hover:bg-neutral-50 transition-colors">
                        <td class="px-3 py-3 text-sm font-medium text-neutral-900">
                            {{ $tenantUser->user?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-sm text-neutral-600">
                            {{ $tenantUser->user?->email ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-sm">
                            @if ($tenantUser->status?->value === 'active')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">
                                    Ativo
                                </span>
                            @elseif ($tenantUser->status?->value === 'inactive')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-200 text-neutral-700">
                                    Inativo
                                </span>
                            @elseif ($tenantUser->status?->value === 'invited')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-700">
                                    Convidado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-200 text-neutral-700">
                                    —
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm text-neutral-600 hidden lg:table-cell">
                            {{ $tenantUser->created_at?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-3 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    wire:click="editar({{ $tenantUser->id }})"
                                    wire:loading.attr="disabled"
                                    class="bg-white border border-neutral-300 text-neutral-700 px-3 py-1.5 rounded-md text-xs font-medium hover:bg-neutral-50 transition-colors disabled:opacity-50"
                                >
                                    Editar
                                </button>
                                @if ($tenantUser->status?->value === 'active')
                                    <button
                                        wire:click="desativar({{ $tenantUser->id }})"
                                        wire:loading.attr="disabled"
                                        wire:confirm="Tem certeza que quer desativar {{ $tenantUser->user?->name ?? 'este técnico' }}? O acesso será bloqueado imediatamente."
                                        class="bg-white border border-danger-600 text-danger-700 px-3 py-1.5 rounded-md text-xs font-medium hover:bg-danger-50 transition-colors disabled:opacity-50"
                                    >
                                        Desativar
                                    </button>
                                @else
                                    <button
                                        wire:click="reativar({{ $tenantUser->id }})"
                                        wire:loading.attr="disabled"
                                        class="bg-white border border-success-600 text-success-700 px-3 py-1.5 rounded-md text-xs font-medium hover:bg-success-50 transition-colors disabled:opacity-50"
                                    >
                                        Reativar
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-12 text-center text-neutral-500 text-sm">
                            Nenhum técnico cadastrado ainda. Clique em "Cadastrar técnico" pra começar.
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
        @forelse ($technicians as $tenantUser)
            <div wire:key="card-{{ $tenantUser->id }}" class="bg-white rounded-lg border border-neutral-200 shadow-sm p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-neutral-900 truncate">{{ $tenantUser->user?->name ?? '—' }}</p>
                        <p class="text-xs text-neutral-500 truncate">{{ $tenantUser->user?->email ?? '' }}</p>
                    </div>
                    @if ($tenantUser->status?->value === 'active')
                        <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">
                            Ativo
                        </span>
                    @elseif ($tenantUser->status?->value === 'inactive')
                        <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-200 text-neutral-700">
                            Inativo
                        </span>
                    @else
                        <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-700">
                            Convidado
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 mt-3">
                    <button
                        wire:click="editar({{ $tenantUser->id }})"
                        wire:loading.attr="disabled"
                        class="flex-1 min-h-[44px] bg-white border border-neutral-300 text-neutral-700 px-3 py-2 rounded-md text-sm font-medium hover:bg-neutral-50 transition-colors disabled:opacity-50"
                    >
                        Editar
                    </button>
                    @if ($tenantUser->status?->value === 'active')
                        <button
                            wire:click="desativar({{ $tenantUser->id }})"
                            wire:loading.attr="disabled"
                            wire:confirm="Tem certeza que quer desativar {{ $tenantUser->user?->name ?? 'este técnico' }}? O acesso será bloqueado imediatamente."
                            class="flex-1 min-h-[44px] bg-white border border-danger-600 text-danger-700 px-3 py-2 rounded-md text-sm font-medium hover:bg-danger-50 transition-colors disabled:opacity-50"
                        >
                            Desativar
                        </button>
                    @else
                        <button
                            wire:click="reativar({{ $tenantUser->id }})"
                            wire:loading.attr="disabled"
                            class="flex-1 min-h-[44px] bg-white border border-success-600 text-success-700 px-3 py-2 rounded-md text-sm font-medium hover:bg-success-50 transition-colors disabled:opacity-50"
                        >
                            Reativar
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg border border-dashed border-neutral-300 px-6 py-12 text-center text-sm text-neutral-500">
                Nenhum técnico cadastrado ainda. Clique em "Cadastrar técnico" pra começar.
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    @if ($technicians->hasPages())
        <div class="mt-4">
            {{ $technicians->links() }}
        </div>
    @endif

    {{-- =========================================================
         MODAL CRIAR
    ========================================================== --}}
    @if ($showCreateModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.fecharModal()"
        >
            <div class="absolute inset-0 bg-black/50" wire:click="fecharModal"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-neutral-900">Cadastrar técnico</h2>
                    <button wire:click="fecharModal" class="text-neutral-400 hover:text-neutral-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="criar" class="space-y-4">
                    <div>
                        <label for="create-name" class="block text-sm font-medium text-neutral-700 mb-1">Nome completo</label>
                        <input
                            id="create-name"
                            type="text"
                            wire:model="name"
                            autocomplete="name"
                            class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-colors @error('name') border-danger-500 @enderror"
                            placeholder="Ex: Carlos Silva"
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="create-email" class="block text-sm font-medium text-neutral-700 mb-1">E-mail</label>
                        <input
                            id="create-email"
                            type="email"
                            wire:model="email"
                            autocomplete="email"
                            class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-colors @error('email') border-danger-500 @enderror"
                            placeholder="carlos@laboratorio.com"
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="create-password" class="block text-sm font-medium text-neutral-700 mb-1">Senha inicial</label>
                        <input
                            id="create-password"
                            type="password"
                            wire:model="password"
                            autocomplete="new-password"
                            class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-colors @error('password') border-danger-500 @enderror"
                            placeholder="Mínimo 8 caracteres com pelo menos 1 número"
                        />
                        @error('password')
                            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-neutral-500">Passe essa senha pro técnico — ele pode alterá-la depois.</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            wire:click="fecharModal"
                            class="flex-1 bg-white border border-neutral-300 text-neutral-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-neutral-50 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="flex-1 bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove>Cadastrar</span>
                            <span wire:loading>Cadastrando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- =========================================================
         MODAL EDITAR
    ========================================================== --}}
    @if ($showEditModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.fecharModal()"
        >
            <div class="absolute inset-0 bg-black/50" wire:click="fecharModal"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-neutral-900">Editar técnico</h2>
                    <button wire:click="fecharModal" class="text-neutral-400 hover:text-neutral-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="salvar" class="space-y-4">
                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-neutral-700 mb-1">Nome completo</label>
                        <input
                            id="edit-name"
                            type="text"
                            wire:model="name"
                            autocomplete="name"
                            class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-colors @error('name') border-danger-500 @enderror"
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-email" class="block text-sm font-medium text-neutral-700 mb-1">E-mail</label>
                        <input
                            id="edit-email"
                            type="email"
                            wire:model="email"
                            autocomplete="email"
                            class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-colors @error('email') border-danger-500 @enderror"
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            wire:click="fecharModal"
                            class="flex-1 bg-white border border-neutral-300 text-neutral-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-neutral-50 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="flex-1 bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-800 transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove>Salvar</span>
                            <span wire:loading>Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
