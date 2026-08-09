<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class WelcomeUserMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to OrderFlow');
    }

    public function content(): Content
    {
        return new Content(
            view: 'notification::welcome-user',
            text: 'notification::welcome-user-text',
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
