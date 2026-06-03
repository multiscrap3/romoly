<?php

namespace App\Services;

use App\Mail\GeneralNotificationMail;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifikasiService
{
    /**
     * Send notification to user.
     *
     * @param bool        $email    Jika true, kirim juga via email (bila user opt-in & verified).
     * @param string|null $ctaUrl   URL tombol pada email (opsional).
     * @param string|null $ctaLabel Label tombol pada email (opsional).
     */
    public function send(
        int $userId,
        string $judul,
        string $pesan,
        string $tipe = 'info',
        bool $email = false,
        ?string $ctaUrl = null,
        ?string $ctaLabel = null
    ): Notifikasi {
        $user = User::findOrFail($userId);

        $notifikasi = Notifikasi::create([
            'household_id' => $user->household_id,
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'dibaca' => false,
        ]);

        if ($email) {
            $this->maybeEmail($user, $judul, $pesan, $tipe, $ctaUrl, $ctaLabel);
        }

        return $notifikasi;
    }

    /**
     * Send notification to all household members.
     *
     * @param bool $email Jika true, kirim juga via email ke tiap anggota yang opt-in & verified.
     */
    public function sendToHousehold(
        int $householdId,
        string $judul,
        string $pesan,
        string $tipe = 'info',
        bool $email = false,
        ?string $ctaUrl = null,
        ?string $ctaLabel = null
    ): int {
        $users = User::where('household_id', $householdId)->get();
        $count = 0;

        foreach ($users as $user) {
            Notifikasi::create([
                'household_id' => $householdId,
                'user_id' => $user->id,
                'judul' => $judul,
                'pesan' => $pesan,
                'tipe' => $tipe,
                'dibaca' => false,
            ]);

            if ($email) {
                $this->maybeEmail($user, $judul, $pesan, $tipe, $ctaUrl, $ctaLabel);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Queue email notifikasi bila user mengaktifkannya & email terverifikasi.
     * Gagal queue dicatat ke log, tidak menggagalkan alur notifikasi in-app.
     */
    private function maybeEmail(
        User $user,
        string $judul,
        string $pesan,
        string $tipe,
        ?string $ctaUrl,
        ?string $ctaLabel
    ): void {
        if (! $user->wantsEmailNotifications()) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new GeneralNotificationMail(
                judul: $judul,
                pesan: $pesan,
                tipe: $tipe,
                ctaUrl: $ctaUrl,
                ctaLabel: $ctaLabel,
                greetingName: $user->name,
            ));
        } catch (\Throwable $e) {
            Log::error('Gagal queue email notifikasi: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'judul'   => $judul,
            ]);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notifikasiId): bool
    {
        $notifikasi = Notifikasi::findOrFail($notifikasiId);
        return $notifikasi->update(['dibaca' => true]);
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): int
    {
        return Notifikasi::where('user_id', $userId)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);
    }

    /**
     * Delete notification
     */
    public function delete(int $notifikasiId): bool
    {
        $notifikasi = Notifikasi::findOrFail($notifikasiId);
        return $notifikasi->delete();
    }

    /**
     * Delete all read notifications for user
     */
    public function deleteAllRead(int $userId): int
    {
        return Notifikasi::where('user_id', $userId)
            ->where('dibaca', true)
            ->delete();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return Notifikasi::where('user_id', $userId)
            ->where('dibaca', false)
            ->count();
    }

    /**
     * Get recent notifications for user
     */
    public function getRecent(int $userId, int $limit = 10)
    {
        return Notifikasi::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notifications for user with pagination
     */
    public function getAll(int $userId, array $filters = [])
    {
        $query = Notifikasi::where('user_id', $userId);

        // Filter by dibaca
        if (isset($filters['dibaca'])) {
            $query->where('dibaca', $filters['dibaca']);
        }

        // Filter by tipe
        if (!empty($filters['tipe'])) {
            $query->where('tipe', $filters['tipe']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Send budget alert notification
     */
    public function sendBudgetAlert(int $userId, string $kategori, float $persentase, float $target, float $terpakai): Notifikasi
    {
        $judul = 'Peringatan Anggaran';
        
        if ($persentase >= 100) {
            $pesan = "Anggaran untuk kategori '{$kategori}' telah melebihi target! Target: Rp " . number_format($target, 0, ',', '.') . ", Terpakai: Rp " . number_format($terpakai, 0, ',', '.');
            $tipe = 'danger';
        } else {
            $pesan = "Anggaran untuk kategori '{$kategori}' telah mencapai {$persentase}% dari target. Target: Rp " . number_format($target, 0, ',', '.') . ", Terpakai: Rp " . number_format($terpakai, 0, ',', '.');
            $tipe = 'warning';
        }

        return $this->send(
            $userId, $judul, $pesan, $tipe,
            email: true,
            ctaUrl: route('anggaran.index'),
            ctaLabel: 'Lihat Anggaran'
        );
    }

    /**
     * Send savings goal achieved notification
     */
    public function sendSavingsAchieved(int $userId, string $namaTabungan, float $target): Notifikasi
    {
        $judul = 'Target Tabungan Tercapai! 🎉';
        $pesan = "Selamat! Target tabungan '{$namaTabungan}' sebesar Rp " . number_format($target, 0, ',', '.') . " telah tercapai!";
        
        return $this->send($userId, $judul, $pesan, 'success');
    }

    /**
     * Send debt reminder notification
     */
    public function sendDebtReminder(int $userId, string $jenis, string $namaPihak, float $sisa, string $jatuhTempo): Notifikasi
    {
        $judul = 'Reminder Jatuh Tempo';
        $pesan = ucfirst($jenis) . " kepada {$namaPihak} sebesar Rp " . number_format($sisa, 0, ',', '.') . " akan jatuh tempo pada {$jatuhTempo}";

        return $this->send(
            $userId, $judul, $pesan, 'warning',
            email: true,
            ctaUrl: route('hutang-piutang.index'),
            ctaLabel: 'Lihat Hutang/Piutang'
        );
    }

    /**
     * Send debt paid off notification
     */
    public function sendDebtPaidOff(int $userId, string $jenis, string $namaPihak, float $jumlah): Notifikasi
    {
        $judul = ucfirst($jenis) . ' Lunas! 🎉';
        $pesan = ucfirst($jenis) . " kepada {$namaPihak} sebesar Rp " . number_format($jumlah, 0, ',', '.') . " telah lunas!";
        
        return $this->send($userId, $judul, $pesan, 'success');
    }

    /**
     * Send household invitation notification
     */
    public function sendHouseholdInvitation(int $userId, string $householdName): Notifikasi
    {
        $judul = 'Undangan Household';
        $pesan = "Anda diundang untuk bergabung dengan household '{$householdName}'";
        
        return $this->send($userId, $judul, $pesan, 'info');
    }

    /**
     * Send recurring transaction executed notification
     */
    public function sendRecurringExecuted(int $userId, string $jenis, string $kategori, float $jumlah): Notifikasi
    {
        $judul = 'Transaksi Berulang Dieksekusi';
        $pesan = "Transaksi berulang " . ucfirst($jenis) . " untuk kategori '{$kategori}' sebesar Rp " . number_format($jumlah, 0, ',', '.') . " telah dieksekusi";
        
        return $this->send($userId, $judul, $pesan, 'info');
    }

    /**
     * Clean old notifications (older than 30 days and read)
     */
    public function cleanOldNotifications(int $days = 30): int
    {
        $date = now()->subDays($days);
        
        return Notifikasi::where('dibaca', true)
            ->where('created_at', '<', $date)
            ->delete();
    }
}
