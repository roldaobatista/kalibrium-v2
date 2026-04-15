<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ConsentSubject;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

final class RevocationConfirmationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly ConsentSubject $consentSubject,
        public readonly string $channel,
        public readonly Carbon $revokedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->consentSubject->email ?? ''],
            subject: 'Confirmação de revogação de consentimento',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.revocation-confirmation',
            with: [
                'channel' => $this->channel,
                'revokedAt' => $this->revokedAt->utc()->format('d/m/Y H:i:s').' UTC',
            ],
        );
    }
}
