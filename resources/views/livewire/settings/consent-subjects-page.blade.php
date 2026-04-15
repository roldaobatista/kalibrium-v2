<div>
    <h1>Consentimentos</h1>

    <div>
        <select wire:model.live="statusFilter">
            <option value="">Todos</option>
            <option value="ativo">Ativo</option>
            <option value="revogado">Revogado</option>
            <option value="nao_informado">Não informado</option>
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th>Identificador</th>
                <th>Canal</th>
                <th>Status</th>
                <th>Última atualização</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subjects as $subject)
                <tr>
                    <td>{{ substr($subject->id, 0, 8) }}</td>
                    <td>-</td>
                    <td>-</td>
                    <td>{{ $subject->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $subjects->links() }}
</div>
