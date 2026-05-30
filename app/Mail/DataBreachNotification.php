<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DataBreachNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $incidentDate,
        public readonly string $affectedData,
        public readonly string $actionsTaken,
        public readonly string $userActions,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[PENTING] Pemberitahuan Insiden Keamanan Data - Romoly',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.data-breach-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
