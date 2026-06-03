<?php

namespace App\Mail;

use App\Models\HouseholdInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email undangan bergabung ke household, dikirim via antrian database.
 * Diproses oleh endpoint cron /cron/process-mail (lihat email-context.md).
 */
class HouseholdInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly HouseholdInvitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        $household = $this->invitation->household?->nama ?? config('app.name');

        return new Envelope(
            subject: 'Undangan Bergabung ke ' . $household . ' — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        $inviter   = $this->invitation->invitedBy?->name ?? 'Seseorang';
        $household  = $this->invitation->household?->nama ?? config('app.name');
        $registerUrl = route('register', ['token' => $this->invitation->token]);

        return new Content(
            markdown: 'emails.household-invitation',
            with: [
                'inviterName'   => $inviter,
                'householdName' => $household,
                'role'          => $this->invitation->role ?? 'member',
                'registerUrl'   => $registerUrl,
                'expiresAt'     => $this->invitation->expires_at,
            ],
        );
    }
}
