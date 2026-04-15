<div>
    <h1>Consentimentos</h1>

    @php
        $statusLabels = [
            'ativo' => 'Ativo',
            'revogado' => 'Revogado',
            'nao_informado' => 'Não informado',
        ];
    @endphp

    <div>
        <label for="consent-status-filter">Filtrar por status</label>
        <select id="consent-status-filter" wire:model.live="statusFilter">
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
            @forelse ($subjects as $subject)
                @php($latest = $latestRecords['id:'.$subject->id] ?? null)
                <tr>
                    <td>{{ substr((string) $subject->id, 0, 8) }}</td>
                    <td>{{ $latest['channel'] ?? '-' }}</td>
                    <td>{{ $statusLabels[$latest['status'] ?? ''] ?? '-' }}</td>
                    <td>{{ $latest['updated_at'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Nenhum titular encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $subjects->links() }}
</div>
