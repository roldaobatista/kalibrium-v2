<section class="mx-auto max-w-3xl space-y-6 p-6">
    <div class="space-y-2">
        <h1 class="text-2xl font-semibold">Configurações do laboratório</h1>
        <p class="text-sm text-slate-700">
            Atualize os dados cadastrais usados como base do laboratório.
        </p>
    </div>

    @if (session('status'))
        <p class="rounded border border-emerald-400 bg-emerald-50 p-3 text-sm">
            {{ session('status') }}
        </p>
    @endif

    @if ($readOnly)
        <p class="rounded border border-amber-400 bg-amber-50 p-3 text-sm">
            Este laboratório está em modo somente leitura.
        </p>
    @endif

    <form method="POST" action="/settings/tenant" class="space-y-4">
        @csrf

        <div class="space-y-1">
            <label for="legal_name" class="block text-sm font-medium">Razão social</label>
            <input
                id="legal_name"
                name="legal_name"
                value="{{ old('legal_name', $form['legal_name']) }}"
                @disabled($readOnly)
                class="w-full rounded border border-slate-300 bg-white px-3 py-2"
            >
            @error('legal_name')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="document_number" class="block text-sm font-medium">CNPJ</label>
            <input
                id="document_number"
                name="document_number"
                value="{{ old('document_number', $form['document_number']) }}"
                @disabled($readOnly)
                class="w-full rounded border border-slate-300 bg-white px-3 py-2"
            >
            @error('document_number')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="trade_name" class="block text-sm font-medium">Nome fantasia</label>
            <input
                id="trade_name"
                name="trade_name"
                value="{{ old('trade_name', $form['trade_name']) }}"
                @disabled($readOnly)
                class="w-full rounded border border-slate-300 bg-white px-3 py-2"
            >
            @error('trade_name')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="main_email" class="block text-sm font-medium">E-mail principal</label>
            <input
                id="main_email"
                name="main_email"
                type="email"
                value="{{ old('main_email', $form['main_email']) }}"
                @disabled($readOnly)
                class="w-full rounded border border-slate-300 bg-white px-3 py-2"
            >
            @error('main_email')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="phone" class="block text-sm font-medium">Telefone</label>
            <input
                id="phone"
                name="phone"
                value="{{ old('phone', $form['phone']) }}"
                @disabled($readOnly)
                class="w-full rounded border border-slate-300 bg-white px-3 py-2"
            >
            @error('phone')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="operational_profile" class="block text-sm font-medium">Perfil operacional</label>
            <select
                id="operational_profile"
                name="operational_profile"
                @disabled($readOnly)
                class="w-full rounded border border-slate-300 bg-white px-3 py-2"
            >
                <option value="basic" @selected(old('operational_profile', $form['operational_profile']) === 'basic')>Básico</option>
                <option value="intermediate" @selected(old('operational_profile', $form['operational_profile']) === 'intermediate')>Intermediário</option>
                <option value="accredited" @selected(old('operational_profile', $form['operational_profile']) === 'accredited')>Acreditado</option>
            </select>
            @error('operational_profile')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm font-medium">
            <input
                type="checkbox"
                name="emits_metrological_certificate"
                value="1"
                @checked(old('emits_metrological_certificate', $form['emits_metrological_certificate']))
                @disabled($readOnly)
                class="rounded border-slate-300"
            >
            Emissão de certificado metrológico
        </label>

        @unless ($readOnly)
            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                Salvar
            </button>
        @endunless
    </form>
</section>
