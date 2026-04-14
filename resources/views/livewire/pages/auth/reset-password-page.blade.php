<section class="mx-auto max-w-lg space-y-4 p-6">
    <h1 class="text-2xl font-semibold">Redefinir senha</h1>

    <form method="POST" action="/auth/reset-password" class="space-y-3">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="block">
            <span class="block text-sm font-medium">E-mail</span>
            <input name="email" type="email" required class="mt-1 w-full rounded border px-3 py-2">
        </label>

        <label class="block">
            <span class="block text-sm font-medium">Nova senha</span>
            <input name="password" type="password" required class="mt-1 w-full rounded border px-3 py-2">
        </label>

        <label class="block">
            <span class="block text-sm font-medium">Confirmar senha</span>
            <input name="password_confirmation" type="password" required class="mt-1 w-full rounded border px-3 py-2">
        </label>

        <button type="submit" class="rounded bg-black px-4 py-2 text-white">Redefinir</button>
    </form>
</section>
