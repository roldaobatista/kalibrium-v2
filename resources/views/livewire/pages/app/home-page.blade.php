<section class="mx-auto max-w-3xl space-y-4 p-6">
    <h1 class="text-2xl font-semibold">Area do aplicativo</h1>

    @if (session('tenant.access_mode') === 'read-only')
        <p class="rounded border border-amber-400 bg-amber-50 p-3 text-sm">
            Seu tenant esta em modo somente leitura.
        </p>
    @endif
</section>
