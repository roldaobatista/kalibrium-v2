<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ConsentSubject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class RevocationLinkMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly ConsentSubject $subject,
        public readonly string $channel,
        public readonly string $rawToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->subject->email ?? '',
            subject: 'Link de revogação de consentimento',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revocation-link',
            with: [
                'revokeUrl' => route('lgpd.revoke', ['token' => $this->rawToken]),
                'channel'   => $this->channel,
            ],
        );
    }
}
