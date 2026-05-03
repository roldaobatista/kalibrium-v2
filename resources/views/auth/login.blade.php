<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Kalibrium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans bg-neutral-50 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-[480px]">

        {{-- Logo --}}
        <div class="flex justify-center mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-600 rounded-md flex items-center justify-center">
                    <span class="text-white font-bold text-lg select-none">K</span>
                </div>
                <span class="font-bold text-neutral-900 text-xl">Kalibrium</span>
            </div>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-lg border border-neutral-200 shadow-sm p-8">

            <h1 class="text-2xl font-bold text-neutral-900 mb-6">Entrar no laboratório</h1>

            {{-- Erro de credenciais --}}
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-danger-50 border border-danger-200 px-4 py-3">
                    <p class="text-sm text-danger-600">
                        {{ $errors->first() ?: 'E-mail ou senha incorretos.' }}
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                {{-- E-mail --}}
                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-neutral-700">
                        E-mail
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="voce@laboratorio.com.br"
                        class="w-full rounded border border-neutral-300 bg-white px-3 py-2 text-base text-neutral-900 placeholder-neutral-400
                               focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20
                               @error('email') border-danger-500 ring-2 ring-danger-500/20 @enderror"
                    >
                </div>

                {{-- Senha --}}
                <div class="space-y-1.5" x-data="{ mostrarSenha: false }">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-neutral-700">
                            Senha
                        </label>
                    </div>
                    <div class="relative">
                        <input
                            id="password"
                            :type="mostrarSenha ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded border border-neutral-300 bg-white px-3 py-2 pr-10 text-base text-neutral-900
                                   focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                   @error('password') border-danger-500 ring-2 ring-danger-500/20 @enderror"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-neutral-400 hover:text-neutral-600"
                            aria-label="Mostrar ou ocultar senha"
                            @click="mostrarSenha = !mostrarSenha"
                        >
                            <svg x-show="!mostrarSenha" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="mostrarSenha" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" style="display:none">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Manter conectado --}}
                <div class="flex items-center gap-2">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                    >
                    <label for="remember" class="text-sm text-neutral-600">
                        Manter conectado neste dispositivo
                    </label>
                </div>

                {{-- Botão --}}
                <button
                    type="submit"
                    class="w-full rounded-md bg-primary-600 px-4 py-2.5 text-base font-semibold text-white
                           hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                           transition-colors"
                >
                    Entrar
                </button>

            </form>

            {{-- Link esqueci senha --}}
            <div class="mt-4 text-center">
                <a
                    href="{{ route('password.request') }}"
                    class="text-sm text-primary-600 hover:underline"
                >
                    Esqueci minha senha
                </a>
            </div>

        </div>

    </div>

    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
