<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Kalibrium' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="font-sans bg-neutral-50 min-h-screen"
      x-data="{ sidebarOpen: false }"
      @keydown.escape.window="sidebarOpen = false">

    {{-- =====================================================================
         HEADER — fixo no topo, z-40
    ====================================================================== --}}
    <header class="fixed top-0 left-0 right-0 h-16 bg-white shadow z-40 flex items-center px-4 lg:px-6 justify-between">

        {{-- Esquerda: hamburger (mobile) + logo --}}
        <div class="flex items-center gap-3">
            {{-- Hamburger — só no mobile --}}
            <button
                type="button"
                class="md:hidden p-2 rounded-md text-neutral-500 hover:bg-neutral-100 transition-colors"
                aria-label="Abrir menu de navegação"
                @click="sidebarOpen = true"
            >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary-600 rounded-md flex items-center justify-center">
                    <span class="text-white font-bold text-sm select-none">K</span>
                </div>
                <span class="font-semibold text-neutral-900 text-sm hidden sm:inline">Kalibrium</span>
            </a>
        </div>

        {{-- Direita: busca + notificações + avatar --}}
        <div class="flex items-center gap-2">
            {{-- Busca — ícone em mobile, input em desktop --}}
            <button
                type="button"
                class="lg:hidden p-2 rounded-md text-neutral-500 hover:bg-neutral-100 transition-colors"
                aria-label="Buscar"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </button>

            {{-- Notificações --}}
            <button
                type="button"
                class="relative p-2 rounded-md text-neutral-500 hover:bg-neutral-100 transition-colors"
                aria-label="Notificações"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </button>

            {{-- Avatar + menu do usuário --}}
            @auth
            <div class="relative" x-data="{ userMenuOpen: false }">
                <button
                    type="button"
                    class="flex items-center gap-2 p-1.5 rounded-md hover:bg-neutral-100 transition-colors"
                    aria-label="Menu do usuário"
                    @click="userMenuOpen = !userMenuOpen"
                    @click.outside="userMenuOpen = false"
                >
                    <div class="w-8 h-8 bg-primary-700 rounded-full flex items-center justify-center">
                        <span class="text-white text-xs font-semibold select-none">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <span class="hidden md:inline text-sm font-medium text-neutral-700 max-w-[120px] truncate">
                        {{ auth()->user()->name ?? '' }}
                    </span>
                    <svg class="w-4 h-4 text-neutral-400 hidden md:inline" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                {{-- Dropdown do usuário --}}
                <div
                    x-show="userMenuOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 top-full mt-1 w-56 bg-white rounded-lg shadow-md border border-neutral-200 py-1 z-50"
                    role="menu"
                    aria-label="Ações do usuário"
                    style="display: none"
                >
                    <div class="px-4 py-3 border-b border-neutral-100">
                        <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()->name ?? '' }}</p>
                        <p class="text-xs text-neutral-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full text-left px-4 py-2 text-sm text-danger-600 hover:bg-danger-50 transition-colors"
                            role="menuitem"
                        >
                            Sair
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </div>
    </header>

    {{-- =====================================================================
         SIDEBAR — desktop: fixa à esquerda; mobile: drawer overlay
    ====================================================================== --}}

    {{-- Drawer overlay — mobile only --}}
    <div
        x-show="sidebarOpen"
        class="fixed inset-0 z-50 md:hidden"
        style="display: none"
        role="dialog"
        aria-modal="true"
        aria-label="Menu de navegação"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50"
            @click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        {{-- Drawer --}}
        <aside
            class="absolute top-0 left-0 bottom-0 w-64 bg-primary-900 z-10 flex flex-col overflow-y-auto"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
        >
            {{-- Cabeçalho do drawer --}}
            <div class="flex items-center justify-between px-4 py-4 border-b border-primary-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary-600 rounded-md flex items-center justify-center">
                        <span class="text-white font-bold text-sm select-none">K</span>
                    </div>
                    <span class="text-white font-semibold text-sm">Kalibrium</span>
                </div>
                <button
                    type="button"
                    class="p-1.5 rounded-md text-primary-300 hover:bg-primary-800 transition-colors"
                    aria-label="Fechar menu"
                    @click="sidebarOpen = false"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @include('layouts.partials.sidebar-nav', ['mobile' => true])
        </aside>
    </div>

    {{-- Sidebar desktop — fixa, escondida em mobile --}}
    <aside class="fixed top-16 left-0 bottom-0 z-30 w-64 bg-primary-900 hidden md:flex flex-col overflow-y-auto">
        @include('layouts.partials.sidebar-nav', ['mobile' => false])
    </aside>

    {{-- =====================================================================
         CONTEÚDO PRINCIPAL
    ====================================================================== --}}
    <main class="mt-16 md:ml-64 min-h-[calc(100vh-4rem)] bg-neutral-50">
        <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-6">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
