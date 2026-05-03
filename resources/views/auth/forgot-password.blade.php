<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha — Kalibrium</title>
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

            <h1 class="text-2xl font-bold text-neutral-900">Esqueci minha senha</h1>
            <p class="text-sm text-neutral-500 mt-1 mb-6">
                Vamos enviar um link pra você redefinir sua senha. Confirme seu e-mail abaixo.
            </p>

            {{-- Sucesso --}}
            @if (session('status'))
                <div class="mb-4 rounded-md bg-success-50 border border-success-200 px-4 py-3">
                    <p class="text-sm text-success-700">
                        Se este e-mail estiver cadastrado, você vai receber em alguns minutos uma mensagem com o link pra redefinir a senha.
                    </p>
                </div>
            @endif

            {{-- Erro de e-mail --}}
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-danger-50 border border-danger-200 px-4 py-3">
                    <p class="text-sm text-danger-600">
                        {{ $errors->first('email') }}
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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

                {{-- Botão --}}
                <button
                    type="submit"
                    class="w-full rounded-md bg-primary-600 px-4 py-2.5 text-base font-semibold text-white
                           hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                           transition-colors"
                >
                    Enviar instruções
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

</body>
</html>
