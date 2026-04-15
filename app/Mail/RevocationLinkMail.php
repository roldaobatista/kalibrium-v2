<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ConsentSubject;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class RevocationLinkMail extends Mailable
{
    public function __construct(
        public readonly ConsentSubject $consentSubject,
        public readonly string $channel,
        private readonly string $rawToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->consentSubject->email ?? ''],
            subject: 'Link de revogação de consentimento',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revocation-link',
            with: [
                'revokeUrl' => route('lgpd.revoke', ['token' => $this->rawToken]),
                'channel' => $this->channel,
            ],
        );
    }
}
