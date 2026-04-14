<section class="mx-auto max-w-lg space-y-4 p-6">
    <h1 class="text-2xl font-semibold">Desafio de 2FA</h1>

    @include('livewire.pages.auth.partials.feedback')

    <form method="POST" action="/auth/two-factor-challenge" class="space-y-3">
        @csrf

        <label class="block">
            <span class="block text-sm font-medium">Codigo</span>
            <input name="code" type="text" class="mt-1 w-full rounded border px-3 py-2">
        </label>

        <label class="block">
            <span class="block text-sm font-medium">Codigo de recuperacao</span>
            <input name="recovery_code" type="text" class="mt-1 w-full rounded border px-3 py-2">
        </label>

        <button type="submit" class="rounded bg-black px-4 py-2 text-white">Validar</button>
    </form>
</section>
