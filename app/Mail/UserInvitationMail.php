<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;

final class UserInvitationMail extends Mailable
{
    public function build(): self
    {
        return $this
            ->subject('Convite Kalibrium')
            ->text('emails.user-invitation');
    }
}
