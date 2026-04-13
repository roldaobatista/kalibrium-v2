<main class="mx-auto max-w-xl space-y-4 p-6">
    <h1 class="text-2xl font-semibold">Livewire OK</h1>
    <!-- AC-002: /ping deve expor o texto Livewire OK. -->
    <!-- AC-003: /ping deve carregar o JS versionado gerado pelo build. -->
    <!-- AC-003: /ping deve carregar o CSS versionado gerado pelo build. -->
    <!-- AC-008: /ping deve carregar o JS versionado gerado pelo build. -->
    <!-- AC-008: /ping deve carregar o CSS versionado gerado pelo build. -->

    <p class="text-sm text-slate-600">
        Contador: <span>{{ $counter }}</span>
    </p>

    <button
        type="button"
        wire:click="increment"
        class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white"
    >
        Incrementar
    </button>
</main>
