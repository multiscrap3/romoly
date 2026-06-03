<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email notifikasi generik (budget alert, reminder hutang, tabungan tercapai, dll).
 * Dikirim via antrian database, diproses cron /cron/process-mail (lihat email-context.md).
 *
 * Best practice: hanya dipakai untuk user yang opt-in & email terverifikasi
 * (lihat NotifikasiService::maybeEmail()).
 */
class GeneralNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $judul,
        public readonly string $pesan,
        public readonly string $tipe = 'info',
        public readonly ?string $ctaUrl = null,
        public readonly ?string $ctaLabel = null,
        public readonly ?string $greetingName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->judul . ' — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.general-notification',
            with: [
                'judul'        => $this->judul,
                'pesan'        => $this->pesan,
                'buttonColor'  => $this->buttonColor(),
                'ctaUrl'       => $this->ctaUrl,
                'ctaLabel'     => $this->ctaLabel,
                'greetingName' => $this->greetingName,
            ],
        );
    }

    /**
     * Petakan tipe notifikasi ke warna tombol komponen mail Laravel.
     */
    private function buttonColor(): string
    {
        return match ($this->tipe) {
            'success'           => 'success',
            'danger', 'warning' => 'error',
            default             => 'primary',
        };
    }
}
