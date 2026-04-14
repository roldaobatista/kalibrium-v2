<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;

final class UserInvitationMail extends Mailable
{
    public function __construct(
        public readonly string $invitationUrl,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Convite Kalibrium')
            ->with(['invitationUrl' => $this->invitationUrl])
            ->text('emails.user-invitation');
    }
}
