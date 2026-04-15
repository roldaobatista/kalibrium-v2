<section class="mx-auto max-w-5xl space-y-6 p-6">
    <div class="space-y-2">
        <h1 class="text-2xl font-semibold">Usuarios e papeis</h1>
        <p class="text-sm text-slate-700">Gerencie os acessos internos do laboratorio.</p>
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

    <div class="grid gap-3 md:grid-cols-2">
        <label class="space-y-1 text-sm font-medium">
            <span>Buscar</span>
            <input wire:model.live="search" class="w-full rounded border border-slate-300 px-3 py-2">
        </label>

        <label class="space-y-1 text-sm font-medium">
            <span>Papel</span>
            <select wire:model.live="role" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                <option value="gerente">gerente</option>
                <option value="tecnico">tecnico</option>
                <option value="administrativo">administrativo</option>
                <option value="visualizador">visualizador</option>
            </select>
        </label>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-300 text-left">
                    <th class="py-2">Nome</th>
                    <th class="py-2">E-mail</th>
                    <th class="py-2">Papel</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">2FA</th>
                    @unless ($readOnly)
                        <th class="py-2">Acoes</th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $tenantUser)
                    <tr class="border-b border-slate-200">
                        <td class="py-2">{{ $tenantUser->user?->name }}</td>
                        <td class="py-2">{{ $tenantUser->user?->email }}</td>
                        <td class="py-2">{{ $tenantUser->role }}</td>
                        <td class="py-2">{{ $tenantUser->status }}</td>
                        <td class="py-2">{{ $tenantUser->requires_2fa ? 'obrigatoria' : 'opcional' }}</td>
                        @unless ($readOnly)
                            <td class="space-y-2 py-2">
                                @if ($tenantUser->status === 'active')
                                    <label class="block text-xs font-medium">
                                        <span>Alterar papel</span>
                                        <select wire:change="updateRole({{ $tenantUser->id }}, $event.target.value)" class="mt-1 rounded border border-slate-300 px-2 py-1">
                                            <option value="gerente" @selected($tenantUser->role === 'gerente')>gerente</option>
                                            <option value="tecnico" @selected($tenantUser->role === 'tecnico')>tecnico</option>
                                            <option value="administrativo" @selected($tenantUser->role === 'administrativo')>administrativo</option>
                                            <option value="visualizador" @selected($tenantUser->role === 'visualizador')>visualizador</option>
                                        </select>
                                    </label>
                                    <button wire:click="deactivateUser({{ $tenantUser->id }})" class="rounded border border-red-500 px-3 py-1 text-xs font-medium text-red-700">
                                        Remover acesso
                                    </button>
                                @else
                                    <span class="text-xs text-slate-600">Sem acoes para convite pendente.</span>
                                @endif
                            </td>
                        @endunless
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @unless ($readOnly)
        <div class="space-y-3 border-t border-slate-300 pt-4">
            <h2 class="text-lg font-semibold">Convidar usuario</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1 text-sm font-medium">
                    <span>Nome</span>
                    <input wire:model="form.name" placeholder="Nome" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('form.name')
                        <span class="block text-xs text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>E-mail</span>
                    <input wire:model="form.email" placeholder="E-mail" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('form.email')
                        <span class="block text-xs text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>Papel</span>
                    <select wire:model="form.role" class="w-full rounded border border-slate-300 px-3 py-2">
                        <option value="gerente">gerente</option>
                        <option value="tecnico">tecnico</option>
                        <option value="administrativo">administrativo</option>
                        <option value="visualizador">visualizador</option>
                    </select>
                    @error('form.role')
                        <span class="block text-xs text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <div class="space-y-1 text-sm">
                    @error('form.company_id')
                        <span class="block text-xs text-red-700">{{ $message }}</span>
                    @enderror
                    @error('form.branch_id')
                        <span class="block text-xs text-red-700">{{ $message }}</span>
                    @enderror
                </div>
                <button wire:click="inviteUser" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                    Enviar convite
                </button>
            </div>
        </div>
    @endunless
</section>
