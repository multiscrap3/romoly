<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Household;
use App\Models\ImportBank;
use App\Services\AnomalyDetectionService;
use App\Services\ChallengeService;
use App\Services\InsightService;
use App\Services\MomentumService;
use App\Services\NotifikasiService;
use App\Services\RecurringService;
use App\Services\WeeklyReviewService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CronController extends Controller
{
    public function __construct(
        private readonly RecurringService $recurringService,
        private readonly NotifikasiService $notifikasiService,
        private readonly InsightService $insightService,
        private readonly AnomalyDetectionService $anomalyDetectionService,
        private readonly MomentumService $momentumService,
        private readonly ChallengeService $challengeService,
        private readonly WeeklyReviewService $weeklyReviewService,
    ) {}

    public function processRecurring(Request $request): JsonResponse
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        try {
            $result = $this->recurringService->processAllDue($tanggal);

            Log::info('Cron processRecurring selesai', $result);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi rutin berhasil diproses.',
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron processRecurring gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses transaksi rutin.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function processNotifications(Request $request): JsonResponse
    {
        try {
            $households = Household::where('status', 'active')->get();
            $total      = 0;

            foreach ($households as $household) {
                $sent  = $this->notifikasiService->sendPendingNotifications($household->id);
                $total += $sent;
            }

            Log::info('Cron processNotifications selesai', ['total_terkirim' => $total]);

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil diproses.',
                'data'    => ['total_terkirim' => $total],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron processNotifications gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses notifikasi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function processInsights(Request $request): JsonResponse
    {
        $bulan = (int) $request->input('bulan', now()->subMonth()->month);
        $tahun = (int) $request->input('tahun', now()->subMonth()->year);

        try {
            $households = Household::where('status', 'active')->get();
            $total      = 0;

            foreach ($households as $household) {
                $periodeAwal   = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
                $periodeAkhir  = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

                $this->insightService->generateForHousehold($household->id, $periodeAwal, $periodeAkhir);
                $total++;
            }

            Log::info('Cron processInsights selesai', ['total_household' => $total, 'bulan' => $bulan, 'tahun' => $tahun]);

            return response()->json([
                'success' => true,
                'message' => 'Insight berhasil diproses.',
                'data'    => ['total_household' => $total, 'bulan' => $bulan, 'tahun' => $tahun],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron processInsights gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses insight.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function anomalyScan(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());

        try {
            $households = Household::where('status', 'active')->get();
            $total      = 0;

            foreach ($households as $household) {
                $this->anomalyDetectionService->scanPeriod($household->id, $startDate, $endDate, false);
                $total++;
            }

            Log::info('Cron anomalyScan selesai', ['total_household' => $total]);

            return response()->json([
                'success' => true,
                'message' => 'Scan anomali selesai.',
                'data'    => ['total_household' => $total, 'start_date' => $startDate, 'end_date' => $endDate],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron anomalyScan gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal scan anomali.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PDP F4: Purge audit_log yang lebih dari 2 tahun sesuai kebijakan retensi.
     */
    public function purgeAuditLog(): JsonResponse
    {
        try {
            $cutoff  = now()->subYears(2);
            $deleted = AuditLog::where('created_at', '<=', $cutoff)->delete();

            Log::info('Cron purgeAuditLog selesai', ['deleted' => $deleted, 'cutoff' => $cutoff->toDateString()]);

            return response()->json([
                'success' => true,
                'message' => "Purge audit log selesai. {$deleted} record dihapus.",
                'data'    => ['deleted' => $deleted, 'cutoff' => $cutoff->toDateString()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron purgeAuditLog gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal purge audit log.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PDP: Purge file bank statement yang sudah lebih dari 30 hari.
     * File dihapus dari storage, file_path di-null-kan.
     */
    public function purgeImportFiles(): JsonResponse
    {
        try {
            $cutoff  = now()->subDays(30);
            $imports = ImportBank::whereNotNull('file_path')
                ->where('created_at', '<=', $cutoff)
                ->get();

            $deleted = 0;
            foreach ($imports as $import) {
                if ($import->file_path && Storage::exists($import->file_path)) {
                    Storage::delete($import->file_path);
                }
                $import->update(['file_path' => null]);
                $deleted++;
            }

            Log::info('Cron purgeImportFiles selesai', ['deleted' => $deleted]);

            return response()->json([
                'success' => true,
                'message' => "Purge file import selesai. {$deleted} file dihapus.",
                'data'    => ['deleted' => $deleted, 'cutoff' => $cutoff->toDateString()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron purgeImportFiles gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal purge file import.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function gamifikasiDailyDecay(): JsonResponse
    {
        try {
            $users = \App\Models\User::whereNotNull('household_id')->get();
            foreach ($users as $user) {
                $this->momentumService->applyDailyDecay($user);
            }

            Log::info('Cron gamifikasiDailyDecay selesai', ['total_users' => $users->count()]);

            return response()->json([
                'success' => true,
                'message' => 'Daily decay gamifikasi selesai.',
                'data'    => ['total_users' => $users->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron gamifikasiDailyDecay gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal daily decay.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function gamifikasiGenerateChallenges(): JsonResponse
    {
        try {
            $users = \App\Models\User::whereNotNull('household_id')->get();
            foreach ($users as $user) {
                $this->challengeService->assignForUser($user);
            }

            Log::info('Cron gamifikasiGenerateChallenges selesai', ['total_users' => $users->count()]);

            return response()->json([
                'success' => true,
                'message' => 'Generate challenges gamifikasi selesai.',
                'data'    => ['total_users' => $users->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron gamifikasiGenerateChallenges gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate challenges.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function gamifikasiWeeklyReviews(): JsonResponse
    {
        try {
            $users = \App\Models\User::whereNotNull('household_id')->get();
            foreach ($users as $user) {
                $this->weeklyReviewService->generateForUser($user);
            }

            Log::info('Cron gamifikasiWeeklyReviews selesai', ['total_users' => $users->count()]);

            return response()->json([
                'success' => true,
                'message' => 'Weekly reviews gamifikasi selesai.',
                'data'    => ['total_users' => $users->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron gamifikasiWeeklyReviews gagal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate weekly reviews.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Cron endpoint aktif.',
            'data'    => [
                'server_time' => now()->toDateTimeString(),
                'timezone'    => config('app.timezone'),
            ],
        ]);
    }
}
