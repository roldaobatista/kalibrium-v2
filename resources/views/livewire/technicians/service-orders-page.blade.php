<div
    x-data="{ photoModal: null }"
    @keydown.escape.window="photoModal = null"
>
    {{-- Breadcrumb --}}
    <nav class="text-sm text-neutral-500 mb-4 font-medium" aria-label="Caminho de navegação">
        <a href="/" class="hover:text-primary-600 transition-colors">Início</a>
        <span class="mx-2 text-neutral-300" aria-hidden="true">›</span>
        <a href="{{ route('technicians.index') }}" class="hover:text-primary-600 transition-colors">Técnicos</a>
        <span class="mx-2 text-neutral-300" aria-hidden="true">›</span>
        <span class="text-neutral-700">Ordens de Serviço</span>
    </nav>

    {{-- Cabeçalho --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Ordens de Serviço</h1>
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

    {{-- Tabela de OS --}}
    @if ($serviceOrders->isEmpty())
        <div class="bg-white border border-neutral-200 rounded-lg p-12 text-center shadow-sm">
            <svg class="w-10 h-10 text-neutral-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
            </svg>
            <p class="text-neutral-500 font-medium">Este técnico ainda não registrou ordens de serviço.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($serviceOrders as $order)
                @php
                    $badgeClass = match($order->status) {
                        'received'           => 'bg-blue-100 text-blue-800',
                        'in_calibration'     => 'bg-yellow-100 text-yellow-800',
                        'awaiting_approval'  => 'bg-orange-100 text-orange-800',
                        'completed'          => 'bg-green-100 text-green-800',
                        'cancelled'          => 'bg-neutral-100 text-neutral-500',
                        default              => 'bg-neutral-100 text-neutral-600',
                    };
                    $statusLabel = match($order->status) {
                        'received'           => 'Recebido',
                        'in_calibration'     => 'Em calibração',
                        'awaiting_approval'  => 'Aguardando aprovação',
                        'completed'          => 'Concluído',
                        'cancelled'          => 'Cancelado',
                        default              => $order->status,
                    };
                    $orderPhotos = $photosByOrder->get((string) $order->id, collect());
                @endphp

                <div class="bg-white border border-neutral-200 rounded-lg shadow-sm overflow-hidden">
                    {{-- Cabeçalho da OS --}}
                    <div class="px-4 py-3 flex items-start justify-between gap-4 border-b border-neutral-100">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-neutral-900 truncate">{{ $order->client_name }}</p>
                            <p class="text-xs text-neutral-500 mt-0.5">{{ $order->instrument_description }}</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                {{ $statusLabel }}
                            </span>
                            <span class="text-xs text-neutral-400 whitespace-nowrap">{{ $order->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    {{-- Fotos da OS --}}
                    @if ($orderPhotos->isNotEmpty())
                        <div class="px-4 py-3">
                            <p class="text-xs font-medium text-neutral-500 uppercase tracking-wide mb-2">Fotos do serviço</p>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                @foreach ($orderPhotos as $photo)
                                    <button
                                        type="button"
                                        class="relative aspect-square rounded-md overflow-hidden bg-neutral-100 border border-neutral-200 hover:ring-2 hover:ring-primary-500 transition focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        @click="photoModal = {{ json_encode(['url' => $photo['signed_url'], 'filename' => $photo['original_filename']]) }}"
                                        aria-label="Ampliar foto {{ $photo['original_filename'] }}"
                                    >
                                        <img
                                            src="{{ $photo['signed_url'] }}"
                                            alt="{{ $photo['original_filename'] }}"
                                            class="w-full h-full object-cover"
                                            loading="lazy"
                                        />
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Paginação --}}
        <div class="mt-4">
            {{ $serviceOrders->links() }}
        </div>
    @endif

    {{-- Modal de foto ampliada --}}
    <div
        x-show="photoModal !== null"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        style="display: none"
        role="dialog"
        aria-modal="true"
        :aria-label="photoModal ? 'Foto: ' + photoModal.filename : 'Foto'"
        @click.self="photoModal = null"
    >
        <div class="relative max-w-4xl max-h-full w-full">
            <button
                type="button"
                class="absolute -top-10 right-0 text-white/80 hover:text-white text-sm font-medium"
                @click="photoModal = null"
                aria-label="Fechar"
            >
                ✕ Fechar
            </button>
            <template x-if="photoModal">
                <img
                    :src="photoModal.url"
                    :alt="photoModal.filename"
                    class="w-full h-auto max-h-[85vh] object-contain rounded-lg"
                />
            </template>
        </div>
    </div>
</div>
