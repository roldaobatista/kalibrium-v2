<div>
    {{-- Saudação --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">{{ $this->saudacao() }}, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-neutral-500">Aqui estão as coisas que precisam da sua atenção hoje.</p>
    </div>

    {{-- Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- Card pendentes --}}
        <a href="{{ route('mobile-devices.index') }}?status=pending"
           class="block rounded-lg border border-neutral-200 bg-white p-6 transition-all hover:border-primary-500 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-500">Celulares aguardando aprovação</p>
                    <p class="mt-2 text-3xl font-bold {{ $pendingCount > 0 ? 'text-warning-700' : 'text-success-700' }}">
                        {{ $pendingCount }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500">
                        @if ($pendingCount === 0)
                            Tudo em dia. Nenhum pedido aguardando.
                        @else
                            {{ $pendingCount }} técnico(s) pediram acesso e estão esperando você liberar.
                        @endif
                    </p>
                </div>
                <svg class="h-6 w-6 flex-shrink-0 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </a>

        {{-- Card aprovados --}}
        <a href="{{ route('mobile-devices.index') }}?status=approved"
           class="block rounded-lg border border-neutral-200 bg-white p-6 transition-all hover:border-primary-500 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-500">Técnicos com acesso ativo</p>
                    <p class="mt-2 text-3xl font-bold text-neutral-700">{{ $approvedCount }}</p>
                    <p class="mt-1 text-xs text-neutral-500">Ver lista</p>
                </div>
                <svg class="h-6 w-6 flex-shrink-0 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3" />
                </svg>
            </div>
        </a>

        {{-- Card bloqueados --}}
        <a href="{{ route('mobile-devices.index') }}?status=revoked"
           class="block rounded-lg border border-neutral-200 bg-white p-6 transition-all hover:border-primary-500 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-500">Celulares bloqueados</p>
                    <p class="mt-2 text-3xl font-bold text-neutral-700">{{ $revokedCount }}</p>
                    <p class="mt-1 text-xs text-neutral-500">Ver lista</p>
                </div>
                <svg class="h-6 w-6 flex-shrink-0 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
        </a>
    </div>

    {{-- Atalhos rápidos --}}
    <div class="rounded-lg border border-neutral-200 bg-white p-6">
        <h2 class="mb-4 text-base font-semibold text-neutral-900">Atalhos rápidos</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('mobile-devices.index') }}?status=pending"
               class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-700">
                Aprovar pedidos pendentes
            </a>
            <a href="{{ route('mobile-devices.index') }}"
               class="rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-50">
                Bloquear/limpar um celular
            </a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit"
                        class="rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-50">
                    Sair
                </button>
            </form>
        </div>
    </div>
</div>
