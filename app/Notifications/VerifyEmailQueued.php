<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Email verifikasi akun (Bahasa Indonesia), dikirim via antrian database.
 * Diproses oleh endpoint cron /cron/process-mail (lihat email-context.md).
 */
class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Bangun isi email verifikasi.
     */
    protected function buildMailMessage($url): MailMessage
    {
        $appName = config('app.name', 'Romoly');

        return (new MailMessage)
            ->subject('Verifikasi Alamat Email Anda — ' . $appName)
            ->greeting('Halo! 👋')
            ->line('Terima kasih telah mendaftar di **' . $appName . '**. Satu langkah lagi: verifikasi alamat email Anda agar akun sepenuhnya aktif dan aman.')
            ->action('Verifikasi Email Saya', $url)
            ->line('Tautan ini berlaku selama **60 menit**. Anda tetap dapat menggunakan ' . $appName . ' sambil menunggu, namun beberapa fitur sensitif baru terbuka setelah email diverifikasi.')
            ->line('Jika Anda tidak merasa membuat akun ini, abaikan saja email ini — tidak ada tindakan lebih lanjut yang diperlukan.')
            ->salutation("Salam hangat,\nTim " . $appName);
    }
}
