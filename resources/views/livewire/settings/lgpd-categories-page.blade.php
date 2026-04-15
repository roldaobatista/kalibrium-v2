<div>
    <h1>Bases Legais LGPD</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <form wire:submit="save">
        <div>
            <label>Categoria</label>
            <select wire:model="code">
                <option value="">Selecione...</option>
                @foreach ($codes as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
            @error('code') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Nome</label>
            <input type="text" wire:model="name" />
            @error('name') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Base Legal</label>
            <select wire:model="legal_basis">
                <option value="">Selecione...</option>
                @foreach ($legalBases as $lb)
                    <option value="{{ $lb }}">{{ $lb }}</option>
                @endforeach
            </select>
            @error('legal_basis') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label>Comentário</label>
            <textarea wire:model="comment"></textarea>
            @error('comment') <span>{{ $message }}</span> @enderror
        </div>

        <button type="submit">Salvar</button>
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
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->code }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->legal_basis }}</td>
                    <td>
                        <button wire:click="deleteCategory('{{ $category->id }}')">Remover</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
