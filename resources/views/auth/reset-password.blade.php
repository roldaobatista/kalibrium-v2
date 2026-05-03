<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definir nova senha — Kalibrium</title>
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

            <h1 class="text-2xl font-bold text-neutral-900">Definir nova senha</h1>
            <p class="text-sm text-neutral-500 mt-1 mb-6">
                Crie uma senha nova com pelo menos 8 caracteres e 1 número.
            </p>

            {{-- Token expirado / inválido --}}
            @if ($errors->has('email') && str_contains($errors->first('email'), 'inválido') || $errors->has('token'))
                <div class="mb-4 rounded-md bg-danger-50 border border-danger-200 px-4 py-3">
                    <p class="text-sm text-danger-600">
                        Este link expirou ou já foi usado. Peça um novo na tela de login.
                    </p>
                </div>
            @endif

            {{-- Erros gerais (senha, confirmação) --}}
            @if ($errors->hasAny(['password', 'password_confirmation']))
                <div class="mb-4 rounded-md bg-danger-50 border border-danger-200 px-4 py-3">
                    @foreach (['password', 'password_confirmation'] as $field)
                        @error($field)
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4" x-data="{ mostrarSenha: false }">
                @csrf

                {{-- Campos ocultos --}}
                <input type="hidden" name="token" value="{{ $token ?? request()->route('token') }}">
                <input type="hidden" name="email" value="{{ $email ?? old('email', request('email')) }}">

                {{-- Nova senha --}}
                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-neutral-700">
                        Nova senha
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            :type="mostrarSenha ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="new-password"
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

                {{-- Confirmar senha --}}
                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-sm font-medium text-neutral-700">
                        Confirme a nova senha
                    </label>
                    <input
                        id="password_confirmation"
                        :type="mostrarSenha ? 'text' : 'password'"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full rounded border border-neutral-300 bg-white px-3 py-2 text-base text-neutral-900
                               focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20
                               @error('password_confirmation') border-danger-500 ring-2 ring-danger-500/20 @enderror"
                    >
                </div>

                {{-- Botão --}}
                <button
                    type="submit"
                    class="w-full rounded-md bg-primary-600 px-4 py-2.5 text-base font-semibold text-white
                           hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                           transition-colors"
                >
                    Salvar nova senha
                </button>

            </form>

            {{-- Voltar ao login --}}
            <div class="mt-4 text-center">
                <a
                    href="{{ route('login') }}"
                    class="text-sm text-primary-600 hover:underline"
                >
                    Voltar ao login
                </a>
            </div>

        </div>

    </div>

    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
