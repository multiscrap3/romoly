<?php

namespace App\Http\Controllers;

use App\Models\SecurityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint deploy untuk shared hosting tanpa akses SSH/terminal.
 *
 * Semua endpoint dijaga middleware `cron.secret` (header `X-Cron-Secret`
 * atau query `?secret=`), memakai CRON_SECRET_KEY yang sama dengan cron.
 *
 * Cara pakai (via browser/cPanel, setelah upload kode terbaru):
 *   1. Pratinjau dulu  : GET /deploy/migrate-status?secret=XXXX
 *   2. Jalankan migrasi: GET /deploy/migrate?secret=XXXX
 */
class DeployController extends Controller
{
    /**
     * Pratinjau status migrasi (READ-ONLY, aman dijalankan kapan saja).
     * Menampilkan migrasi mana yang sudah jalan (Ran) dan yang tertunda (Pending).
     */
    public function migrateStatus(): JsonResponse
    {
        try {
            Artisan::call('migrate:status');

            return response()->json([
                'success' => true,
                'command' => 'migrate:status',
                'output'  => $this->lines(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca status migrasi: ' . $e->getMessage(),
                'hint'    => 'Pastikan koneksi DB di .env benar dan tabel migrations ada.',
            ], 500);
        }
    }

    /**
     * Jalankan migrasi yang tertunda (production-safe, --force).
     * Hanya memproses migrasi yang BELUM tercatat di tabel migrations,
     * jadi aman dipanggil berulang (idempoten).
     */
    public function migrate(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            $this->audit('deploy.migrate', $output);

            return response()->json([
                'success' => true,
                'message' => 'Migrasi selesai dijalankan.',
                'output'  => $this->lines($output),
            ]);
        } catch (\Throwable $e) {
            Log::error('Deploy migrate gagal', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Migrasi gagal: ' . $e->getMessage(),
                'output'  => $this->lines(Artisan::output()),
            ], 500);
        }
    }

    /**
     * Bersihkan cache config/route/view setelah deploy (pengganti perintah
     * `php artisan optimize:clear` yang tidak bisa dijalankan tanpa SSH).
     */
    public function clearCache(): JsonResponse
    {
        $results = [];

        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
                $results[$command] = 'ok';
            } catch (\Throwable $e) {
                $results[$command] = 'gagal: ' . $e->getMessage();
            }
        }

        $this->audit('deploy.clear_cache', json_encode($results));

        return response()->json([
            'success' => true,
            'message' => 'Cache dibersihkan.',
            'results' => $results,
        ]);
    }

    /**
     * Catat aksi deploy ke SecurityLog (best-effort, tidak boleh menggagalkan respons).
     */
    private function audit(string $event, string $output): void
    {
        try {
            SecurityLog::record(
                eventType: $event,
                severity: 'high',
                context: ['output' => mb_substr($output, 0, 5000)],
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat SecurityLog deploy', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Pecah output artisan menjadi array baris yang rapi (buang baris kosong).
     */
    private function lines(string $output): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $output)), fn ($l) => $l !== ''));
    }
}
