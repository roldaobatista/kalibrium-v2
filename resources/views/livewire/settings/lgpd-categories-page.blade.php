<div>
    <h1>Bases Legais LGPD</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <form wire:submit="save">
        <div>
            <label for="lgpd-code">Categoria</label>
            <select id="lgpd-code" wire:model="code">
                <option value="">Selecione...</option>
                @foreach ($codes as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
            @error('code') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="lgpd-name">Nome</label>
            <input id="lgpd-name" type="text" wire:model="name" />
            @error('name') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="lgpd-legal-basis">Base Legal</label>
            <select id="lgpd-legal-basis" wire:model="legal_basis">
                <option value="">Selecione...</option>
                @foreach ($legalBases as $lb)
                    <option value="{{ $lb }}">{{ $lb }}</option>
                @endforeach
            </select>
            @error('legal_basis') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="lgpd-comment">Comentário</label>
            <textarea id="lgpd-comment" wire:model="comment"></textarea>
            @error('comment') <span>{{ $message }}</span> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Salvar</span>
            <span wire:loading wire:target="save">Salvando...</span>
        </button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Nome</th>
                <th>Base Legal</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td>{{ $category->code }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->legal_basis }}</td>
                    <td>
                        <button wire:click="deleteCategory('{{ $category->id }}')">Remover</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Nenhuma categoria cadastrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
