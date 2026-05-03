<div>
    {{-- Breadcrumb --}}
    <nav class="text-sm text-neutral-500 mb-4 font-medium" aria-label="Caminho de navegação">
        <a href="/" class="hover:text-primary-600 transition-colors">Início</a>
        <span class="mx-2 text-neutral-300" aria-hidden="true">›</span>
        <a href="{{ route('technicians.index') }}" class="hover:text-primary-600 transition-colors">Técnicos</a>
        <span class="mx-2 text-neutral-300" aria-hidden="true">›</span>
        <span class="text-neutral-700">Anotações</span>
    </nav>

    {{-- Cabeçalho --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Anotações</h1>
            @if ($technician && $technician->user)
                <p class="text-sm text-neutral-500 mt-1">{{ $technician->user->name }}</p>
            @endif
        </div>
        <a
            href="{{ route('technicians.index') }}"
            class="inline-flex items-center gap-2 border border-neutral-300 text-neutral-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-neutral-50 transition-colors"
        >
            ← Voltar para técnicos
        </a>
    </div>

    {{-- Lista de anotações --}}
    @if ($notes->isEmpty())
        <div class="bg-white border border-neutral-200 rounded-lg p-12 text-center shadow-sm">
            <svg class="w-10 h-10 text-neutral-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <p class="text-neutral-500 font-medium">Este técnico ainda não fez anotações.</p>
        </div>
    @else
        <div class="bg-white border border-neutral-200 rounded-lg overflow-hidden shadow-sm divide-y divide-neutral-100">
            @foreach ($notes as $note)
                <div class="px-4 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-neutral-900 truncate">{{ $note->title }}</p>
                            <p class="text-sm text-neutral-600 mt-1 whitespace-pre-wrap">{{ $note->body }}</p>
                        </div>
                        <span class="text-xs text-neutral-400 whitespace-nowrap flex-shrink-0">
                            {{ $note->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Paginação --}}
        <div class="mt-4">
            {{ $notes->links() }}
        </div>
    @endif
</div>
