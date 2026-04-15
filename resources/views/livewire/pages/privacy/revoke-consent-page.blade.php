<div>
    @if ($expired)
        <h1>Link expirado</h1>
        <p>Link expirado. Solicite um novo link de revogação. Um novo link foi enviado para o seu e-mail.</p>
    @elseif ($confirmed)
        <h1>Consentimento revogado</h1>
        <p>Seu consentimento foi revogado com sucesso.</p>
    @elseif ($noActiveConsent)
        <h1>Sem consentimento ativo</h1>
        <p>Voce nao tem consentimento ativo para este canal</p>
    @elseif (!$invalid)
        <h1>Revogar consentimento</h1>
        <form wire:submit="confirm">
            <div>
                <label>Motivo da revogação</label>
                <select wire:model="selectedReason">
                    <option value="automated">Automatizado</option>
                    <option value="privacy_concern">Preocupação com privacidade</option>
                    <option value="duplicate_contact">Contato duplicado</option>
                    <option value="no_longer_interested">Não tenho mais interesse</option>
                    <option value="other_without_details">Outro</option>
                </select>
            </div>
            <button type="submit">Confirmar revogação</button>
        </form>
    @endif
</div>
