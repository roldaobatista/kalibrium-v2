<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Privacy;

use App\Mail\RevocationConfirmationMail;
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

        $outcome = $service->processRevocationAttempt($token);

        if ($outcome['status'] === 'valid') {
            $this->tokenModel = $outcome['token'];
            $this->subjectModel = $outcome['token']->consentSubject;

            return;
        }

        if ($outcome['status'] === 'renewed') {
            $this->expired = true;
            $this->subjectModel = $outcome['subject'];
            $service->dispatchRenewalLink($outcome);

            return;
        }

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
