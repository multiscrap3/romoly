<?php

namespace App\Console\Commands;

use App\Mail\GeneralNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim email contoh untuk memverifikasi konfigurasi SMTP saat deploy.
 * Mengirim SECARA LANGSUNG (sendNow) — bukan via antrian — agar error SMTP
 * langsung terlihat di terminal.
 *
 * Contoh: php artisan mail:test nama@domain.com
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {email : Alamat email tujuan}';

    protected $description = 'Kirim email contoh untuk menguji konfigurasi SMTP/mailer';

    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info('Mengirim email uji ke: ' . $email);
        $this->line('Mailer aktif : ' . config('mail.default'));
        $this->line('From         : ' . config('mail.from.address') . ' (' . config('mail.from.name') . ')');

        try {
            Mail::to($email)->sendNow(new GeneralNotificationMail(
                judul: 'Email Uji Coba ' . config('app.name'),
                pesan: 'Selamat! Jika Anda menerima email ini, berarti konfigurasi pengiriman email ' . config('app.name') . ' sudah berfungsi dengan benar. Email ini dikirim pada ' . now()->translatedFormat('d F Y, H:i') . ' WIB.',
                tipe: 'success',
                ctaUrl: config('app.url'),
                ctaLabel: 'Buka ' . config('app.name'),
            ));

            $this->newLine();
            $this->info('✔ Email berhasil dikirim ke mailer.');

            if (config('mail.default') === 'log') {
                $this->warn('Mailer = "log": email TIDAK terkirim ke inbox, melainkan ditulis ke storage/logs/laravel.log.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Gagal mengirim email: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
