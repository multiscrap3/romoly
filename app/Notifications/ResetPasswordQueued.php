<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Lang;

/**
 * Email reset password (Bahasa Indonesia), dikirim via antrian database.
 * Diproses oleh endpoint cron /cron/process-mail (lihat email-context.md).
 */
class ResetPasswordQueued extends ResetPassword implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Bangun isi email reset password.
     */
    protected function buildMailMessage($url): MailMessage
    {
        $appName = config('app.name', 'Romoly');
        $expire  = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Atur Ulang Password Anda — ' . $appName)
            ->greeting('Permintaan Reset Password')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun **' . $appName . '** yang terkait dengan email ini.')
            ->action('Atur Ulang Password', $url)
            ->line('Tautan ini akan kedaluwarsa dalam **' . $expire . ' menit**.')
            ->line('Jika Anda tidak meminta reset password, **abaikan email ini** — password Anda tetap aman dan tidak berubah.')
            ->salutation("Salam hangat,\nTim " . $appName);
    }
}
