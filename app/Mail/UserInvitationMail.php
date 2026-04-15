<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;

final class UserInvitationMail extends Mailable
{
    public function __construct(
        public readonly string $invitationUrl,
        public readonly string $tenantName = '',
        public readonly string $role = '',
        public readonly string $inviterName = '',
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Convite Kalibrium')
            ->with([
                'invitationUrl' => $this->invitationUrl,
                'tenantName' => $this->tenantName,
                'role' => $this->role,
                'inviterName' => $this->inviterName,
            ])
            ->text('emails.user-invitation');
    }
}
