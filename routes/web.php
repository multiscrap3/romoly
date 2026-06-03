<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\ImportBankController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\OCRController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\SuperadminAiMonitorController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Privacy & PDP routes (public)
Route::prefix('privacy')->name('privacy.')->group(function () {
    Route::get('/policy', [PrivacyController::class, 'policy'])->name('policy');
    Route::get('/terms', [PrivacyController::class, 'terms'])->name('terms');
});

// Privacy & PDP routes (authenticated)
Route::prefix('privacy')->name('privacy.')->middleware('auth')->group(function () {
    Route::get('/data', [PrivacyController::class, 'dataExport'])->name('export');
    Route::get('/download', [PrivacyController::class, 'downloadData'])->name('download');
});

// Guest routes
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // PDP D9: max 5 percobaan login per menit per IP
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    // PDP D9: max 3 pendaftaran per menit per IP
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('register.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    
    // Gamifikasi
    Route::prefix('gamifikasi')->name('gamifikasi.')->group(function () {
        Route::get('/', [GamificationController::class, 'index'])->name('index');
        Route::get('/review/{id}', [GamificationController::class, 'weeklyReview'])->name('review.show');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    Route::post('/dashboard/layout', [DashboardController::class, 'saveLayout'])->name('dashboard.layout.save');
    
    // Transaksi
    Route::resource('transaksi', TransaksiController::class);
    Route::post('/transaksi/{id}/restore', [TransaksiController::class, 'restore'])->name('transaksi.restore');
    Route::get('/transaksi-summary', [TransaksiController::class, 'summary'])->name('transaksi.summary');
    Route::post('/transaksi/export', [TransaksiController::class, 'export'])->name('transaksi.export');
    
    // Laporan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/harian', [LaporanController::class, 'harian'])->name('harian');
        Route::get('/mingguan', [LaporanController::class, 'mingguan'])->name('mingguan');
        Route::get('/bulanan', [LaporanController::class, 'bulanan'])->name('bulanan');
        Route::get('/tahunan', [LaporanController::class, 'tahunan'])->name('tahunan');
        Route::get('/perbandingan', [LaporanController::class, 'perbandingan'])->name('perbandingan');
        Route::post('/export', [LaporanController::class, 'export'])->name('export');
        Route::get('/tag/{tag}', [LaporanController::class, 'byTag'])->name('tag');
    });
    
    // Kategori
    Route::resource('kategori', \App\Http\Controllers\KategoriController::class)->except(['show']);
    Route::get('/kategori/{kategori}/check-delete', [\App\Http\Controllers\KategoriController::class, 'checkDelete'])->name('kategori.checkDelete');
    
    // Sumber Transaksi
    Route::resource('sumber-transaksi', \App\Http\Controllers\SumberTransaksiController::class)->except(['show']);
    Route::patch('/sumber-transaksi/{sumberTransaksi}/deactivate', [\App\Http\Controllers\SumberTransaksiController::class, 'deactivate'])->name('sumber-transaksi.deactivate');
    Route::patch('/sumber-transaksi/{sumberTransaksi}/activate',   [\App\Http\Controllers\SumberTransaksiController::class, 'activate'])->name('sumber-transaksi.activate');
    
    // Anggaran
    Route::resource('anggaran', \App\Http\Controllers\AnggaranController::class);
    Route::get('/anggaran-summary', [\App\Http\Controllers\AnggaranController::class, 'summary'])->name('anggaran.summary');
    
    // Tabungan
    Route::resource('tabungan', \App\Http\Controllers\TabunganController::class);
    Route::post('/tabungan/{tabungan}/setor', [\App\Http\Controllers\TabunganController::class, 'setor'])->name('tabungan.setor');
    Route::post('/tabungan/{tabungan}/tarik', [\App\Http\Controllers\TabunganController::class, 'tarik'])->name('tabungan.tarik');
    
    // Hutang Piutang
    Route::resource('hutang-piutang', \App\Http\Controllers\HutangPiutangController::class);
    Route::post('/hutang-piutang/{hutangPiutang}/bayar', [\App\Http\Controllers\HutangPiutangController::class, 'bayar'])->name('hutang-piutang.bayar');
    Route::get('/pembayaran/{pembayaran}/edit', [\App\Http\Controllers\PembayaranController::class, 'edit'])->name('pembayaran.edit');
    Route::put('/pembayaran/{pembayaran}', [\App\Http\Controllers\PembayaranController::class, 'update'])->name('pembayaran.update');
    Route::delete('/pembayaran/{pembayaran}', [\App\Http\Controllers\PembayaranController::class, 'destroy'])->name('pembayaran.destroy');

    // Recurring Transaksi
    Route::resource('recurring', \App\Http\Controllers\RecurringTransaksiController::class);
    Route::post('/recurring/{recurring}/toggle', [\App\Http\Controllers\RecurringTransaksiController::class, 'toggle'])->name('recurring.toggle');
    
    // Tags (inline editing/create via Alpine.js on index — no separate pages)
    Route::resource('tags', \App\Http\Controllers\TagController::class)->except(['show', 'edit', 'create']);
    
    // Household
    Route::prefix('household')->name('household.')->group(function () {
        Route::get('/', [\App\Http\Controllers\HouseholdController::class, 'index'])->name('index');
        Route::get('/members', [\App\Http\Controllers\HouseholdController::class, 'members'])->name('members');
        Route::post('/invite', [\App\Http\Controllers\HouseholdController::class, 'invite'])->name('invite');
        Route::post('/join', [\App\Http\Controllers\HouseholdController::class, 'join'])->name('join');
        Route::put('/members/{user}/role', [\App\Http\Controllers\HouseholdController::class, 'updateRole'])->name('members.update-role');
        Route::delete('/members/{user}', [\App\Http\Controllers\HouseholdController::class, 'removeMember'])->name('members.remove');
        Route::delete('/invitations/{invitation}', [\App\Http\Controllers\HouseholdController::class, 'cancelInvitation'])->name('invitations.cancel');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SettingController::class, 'index'])->name('index');
        Route::put('/profile', [\App\Http\Controllers\SettingController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [\App\Http\Controllers\SettingController::class, 'updatePassword'])->name('password.update');
        Route::put('/household', [\App\Http\Controllers\SettingController::class, 'updateHousehold'])->name('household.update');
        Route::put('/preferences', [\App\Http\Controllers\SettingController::class, 'updatePreferences'])->name('preferences.update');
        Route::delete('/reset-data', [\App\Http\Controllers\SettingController::class, 'resetTransaksiData'])->name('reset-data');
    });
    
    // Notifikasi
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotifikasiController::class, 'index'])->name('index');
        Route::post('/{notifikasi}/read', [\App\Http\Controllers\NotifikasiController::class, 'markAsRead'])->name('mark-read');
        Route::post('/read-all', [\App\Http\Controllers\NotifikasiController::class, 'markAllAsRead'])->name('mark-all-read');
    });
});

    // Onboarding
    Route::prefix('onboarding')->name('onboarding.')->middleware('auth')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index');
        Route::post('/household', [OnboardingController::class, 'storeHousehold'])->name('household');
        Route::post('/rekening', [OnboardingController::class, 'storeRekening'])->name('rekening');
        Route::post('/anggaran', [OnboardingController::class, 'storeAnggaran'])->name('anggaran');
        Route::get('/skip', [OnboardingController::class, 'skip'])->name('skip');
    });

    // Import Bank
    Route::prefix('import-bank')->name('import-bank.web.')->middleware('auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\ImportBankController::class, 'webIndex'])->name('index');
        Route::get('/form', [\App\Http\Controllers\ImportBankController::class, 'webForm'])->name('form');
        Route::get('/template', [\App\Http\Controllers\ImportBankController::class, 'downloadTemplate'])->name('template');
        Route::delete('/{importBank}/file', [\App\Http\Controllers\ImportBankController::class, 'deleteFile'])->name('delete-file');
    });

// Cron (internal, protected by cron.secret middleware)
Route::prefix('cron')->name('cron.')->middleware('cron.secret')->group(function () {
    Route::post('/recurring', [CronController::class, 'processRecurring'])->name('recurring');
    Route::post('/notifications', [CronController::class, 'processNotifications'])->name('notifications');
    Route::post('/insights', [CronController::class, 'processInsights'])->name('insights');
    Route::post('/anomaly-scan', [CronController::class, 'anomalyScan'])->name('anomaly-scan');
    Route::post('/purge-import-files', [CronController::class, 'purgeImportFiles'])->name('purge-import-files');
    Route::post('/purge-audit-log', [CronController::class, 'purgeAuditLog'])->name('purge-audit-log');
    Route::post('/gamifikasi/daily-decay', [CronController::class, 'gamifikasiDailyDecay'])->name('gamifikasi.daily-decay');
    Route::post('/gamifikasi/generate-challenges', [CronController::class, 'gamifikasiGenerateChallenges'])->name('gamifikasi.generate-challenges');
    Route::post('/gamifikasi/weekly-reviews', [CronController::class, 'gamifikasiWeeklyReviews'])->name('gamifikasi.weekly-reviews');
    Route::get('/health', [CronController::class, 'health'])->name('health');
});

// Superadmin
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'superadmin.global'])->group(function () {
    Route::get('/', [SuperadminController::class, 'dashboard'])->name('dashboard');
    Route::get('/households', [SuperadminController::class, 'households'])->name('households');
    Route::get('/households/{household}', [SuperadminController::class, 'householdShow'])->name('household-show');
    Route::get('/users', [SuperadminController::class, 'users'])->name('users');
    Route::put('/users/{user}/status', [SuperadminController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::delete('/users/{user}', [SuperadminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/logs', [SuperadminController::class, 'logs'])->name('logs');
    Route::get('/health', [SuperadminController::class, 'health'])->name('health');
    Route::get('/ai-monitor', [SuperadminAiMonitorController::class, 'index'])->name('ai-monitor');
    Route::get('/deploy', [SuperadminController::class, 'deploy'])->name('deploy');
    Route::post('/deploy/migrate', [SuperadminController::class, 'runMigrate'])->name('deploy.migrate');
    Route::post('/deploy/clear-cache', [SuperadminController::class, 'clearCache'])->name('deploy.clear-cache');
    Route::post('/deploy/seed', [SuperadminController::class, 'runSeed'])->name('deploy.seed');
});

// API-like routes for AJAX calls
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/kategori/search', [\App\Http\Controllers\KategoriController::class, 'search'])->name('kategori.search');
    Route::get('/sumber-transaksi/saldo', [\App\Http\Controllers\SumberTransaksiController::class, 'getSaldo'])->name('sumber.saldo');
    Route::get('/transaksi/suggest', [\App\Http\Controllers\TransaksiController::class, 'suggest'])->name('transaksi.suggest');

    // Guided tour / user guide progress
    Route::prefix('tour')->name('tour.')->group(function () {
        Route::post('/seen', [TourController::class, 'seen'])->name('seen');
        Route::post('/welcome', [TourController::class, 'welcome'])->name('welcome');
        Route::delete('/reset', [TourController::class, 'reset'])->name('reset');
    });

    Route::prefix('ocr')->name('ocr.')->group(function () {
        Route::post('/extract', [OCRController::class, 'extract'])->name('extract');
        Route::get('/history', [OCRController::class, 'history'])->name('history');
    });

    Route::prefix('import-bank')->name('import-bank.')->group(function () {
        Route::get('/', [ImportBankController::class, 'index'])->name('index');
        Route::post('/', [ImportBankController::class, 'store'])->name('store');
        Route::post('/preview', [ImportBankController::class, 'preview'])->name('preview');
        Route::get('/{importBank}', [ImportBankController::class, 'show'])->name('show');
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('/duplicate-check', [AIController::class, 'checkDuplicate'])->name('duplicate-check');
        Route::post('/anomaly-detect', [AIController::class, 'detectAnomaly'])->name('anomaly-detect');
        Route::get('/anomalies/scan', [AIController::class, 'scanAnomalies'])->name('anomalies.scan');
        Route::post('/insights/generate', [AIController::class, 'generateInsights'])->name('insights.generate');
    });
});
