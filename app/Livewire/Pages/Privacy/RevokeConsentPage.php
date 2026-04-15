<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Privacy;

use App\Mail\RevocationConfirmationMail;
use App\Mail\RevocationLinkMail;
use App\Models\ConsentSubject;
use App\Models\RevocationToken;
use App\Services\ConsentRecordService;
use App\Services\RevocationTokenService;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;

final class RevokeConsentPage extends Component
{
    public string $rawToken = '';

    public bool $confirmed = false;

    public bool $alreadyRevoked = false;

    public bool $expired = false;

    public bool $invalid = false;

    public string $selectedReason = 'other_without_details';

    public bool $noActiveConsent = false;

    private ?RevocationToken $tokenModel = null;

    private ?ConsentSubject $subjectModel = null;

    public function mount(string $token, RevocationTokenService $service): void
    {
        $this->rawToken = $token;

        // Primeiro tenta encontrar token válido
        $validToken = $service->findValidToken($token);

        if ($validToken !== null) {
            $this->tokenModel = $validToken;
            $this->subjectModel = $validToken->consentSubject;

            return;
        }

        // Tenta encontrar token expirado (não usado)
        $anyToken = $service->findByRaw($token);

        if ($anyToken !== null && $anyToken->used_at === null && $anyToken->expires_at !== null && $anyToken->expires_at->isPast()) {
            $this->expired = true;

            // Gera novo token e reenvia link de revogação
            $renewed = $service->handleExpiredToken($anyToken);
            if ($renewed !== null) {
                $this->subjectModel = $renewed['subject'];
                Mail::send(new RevocationLinkMail(
                    $renewed['subject'],
                    $anyToken->channel,
                    $renewed['rawToken']
                ));
            }

            return;
        }

        // Token inválido — 404
        $this->invalid = true;
        abort(404);
    }

    public function confirm(
        ConsentRecordService $consentService,
        RevocationTokenService $tokenService
    ): void {
        if ($this->tokenModel === null || $this->subjectModel === null) {
            return;
        }

        $channel = $this->tokenModel->channel;
        $tenantId = (int) $this->tokenModel->tenant_id;
        $subjectId = (int) $this->subjectModel->id;

        $record = $consentService->revokeConsent(
            $tenantId,
            $subjectId,
            $channel,
            $this->selectedReason,
            ['ip_address' => request()->ip(), 'user_agent' => request()->userAgent() ?? '']
        );

        if ($record === null) {
            $this->noActiveConsent = true;

            return;
        }

        $tokenService->consume($this->tokenModel);

        Mail::send(new RevocationConfirmationMail(
            $this->subjectModel,
            $channel,
            now()
        ));

        $this->confirmed = true;
    }

    public function render(): View
    {
        return view('livewire.pages.privacy.revoke-consent-page')
            ->layout('layouts.guest');
    }
}
