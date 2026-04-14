<section class="mx-auto max-w-xl space-y-6 p-6">
    <div class="space-y-2">
        <h1 class="text-2xl font-semibold">Aceitar convite</h1>
        <p class="text-sm text-slate-700">Defina sua senha para entrar no laboratorio.</p>
    </div>

    <form method="POST" action="/auth/invitations/{{ $token }}" class="space-y-4">
        @csrf

        <label class="block space-y-1 text-sm font-medium">
            <span>Senha</span>
            <input name="password" type="password" class="w-full rounded border border-slate-300 px-3 py-2">
            @error('password')
                <span class="text-sm text-red-700">{{ $message }}</span>
            @enderror
        </label>

        <label class="block space-y-1 text-sm font-medium">
            <span>Confirmar senha</span>
            <input name="password_confirmation" type="password" class="w-full rounded border border-slate-300 px-3 py-2">
        </label>

        <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Aceitar convite
        </button>
    </form>
</section>
