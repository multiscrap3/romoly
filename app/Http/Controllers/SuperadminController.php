<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Household;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SuperadminController extends Controller
{
    public function dashboard(): View
    {
        $stats = Cache::remember('superadmin_stats', 300, function () {
            return [
                'total_household' => Household::count(),
                'household_aktif' => Household::where('status', 'active')->count(),
                'total_user'      => User::count(),
                'user_aktif'      => User::where('is_active', true)->count(),
                'new_household_7d' => Household::where('created_at', '>=', now()->subDays(7))->count(),
                'new_user_7d'      => User::where('created_at', '>=', now()->subDays(7))->count(),
            ];
        });

        $recentHouseholds = Household::with('plan')->latest()->limit(10)->get();
        $recentUsers      = User::with('household')->latest()->limit(10)->get();

        return view('superadmin.dashboard', compact('stats', 'recentHouseholds', 'recentUsers'));
    }

    public function households(Request $request): View
    {
        $households = Household::with(['plan', 'users'])
            ->when($request->search, fn ($q) => $q->where('nama', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->withCount('users')
            ->latest()
            ->paginate(25);

        return view('superadmin.households', compact('households'));
    }

    public function householdShow(Household $household): View
    {
        $household->load(['plan', 'users']);

        $stats = [
            'total_transaksi' => DB::table('transaksi')->where('household_id', $household->id)->count(),
            'total_anggaran'  => DB::table('anggaran')->where('household_id', $household->id)->count(),
            'total_tabungan'  => DB::table('tabungan')->where('household_id', $household->id)->count(),
        ];

        $recentActivity = AuditLog::where('household_id', $household->id)
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('superadmin.household-show', compact('household', 'stats', 'recentActivity'));
    }

    public function users(Request $request): View
    {
        $users = User::with('household')
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', '%' . $request->search . '%')
                   ->orWhere('email', 'like', '%' . $request->search . '%');
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'aktif'))
            ->latest()
            ->paginate(25);

        return view('superadmin.users', compact('users'));
    }

    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->hasRole('superadmin')) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun superadmin.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->name} berhasil {$status}.");
    }

    /**
     * Hapus (soft delete) akun pengguna.
     *
     * Aturan keamanan:
     *  - Hanya superadmin (dijaga middleware superadmin.global) yang bisa mengakses.
     *  - User ber-role `superadmin` TIDAK bisa dihapus.
     *  - Superadmin tidak bisa menghapus akunnya sendiri.
     *
     * Penghapusan bersifat soft delete: data transaksi, audit, dan gamifikasi
     * tetap utuh. Email pengguna "dilepas" (di-rename) agar alamat yang sama
     * dapat digunakan untuk mendaftar ulang.
     */
    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->hasRole('superadmin')) {
            return back()->with('error', 'Akun superadmin tidak dapat dihapus.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $originalEmail = $user->email;
        $originalName  = $user->name;

        DB::transaction(function () use ($user) {
            // Lepas (pseudonymisasi) email agar alamat yang sama bisa dipakai
            // mendaftar ulang. `id` menjamin tombstone unik; domain `.invalid`
            // adalah TLD yang dijamin RFC 2606 tak pernah menjadi email nyata,
            // sehingga tidak akan bentrok dengan registrasi mana pun.
            // Alamat asli tetap tercatat di SecurityLog untuk keperluan audit.
            $user->forceFill([
                'email'     => 'deleted_' . $user->id . '_' . now()->timestamp . '@deleted.invalid',
                'is_active' => false,
            ])->save();

            $user->delete(); // soft delete (deleted_at)
        });

        SecurityLog::record(
            eventType: 'superadmin.user_deleted',
            severity: 'high',
            context: [
                'deleted_user_id' => $user->id,
                'deleted_email'   => $originalEmail,
                'deleted_name'    => $originalName,
                'household_id'    => $user->household_id,
            ],
        );

        return back()->with('success', "Akun {$originalName} berhasil dihapus. Email {$originalEmail} kini bebas didaftarkan ulang.");
    }

    public function logs(Request $request): View
    {
        $logs = AuditLog::with(['user', 'household'])
            ->when($request->search, fn ($q) => $q->where('action', 'like', '%' . $request->search . '%'))
            ->when($request->household_id, fn ($q) => $q->where('household_id', $request->household_id))
            ->latest()
            ->paginate(50);

        $households = Household::orderBy('nama')->get();

        return view('superadmin.logs', compact('logs', 'households'));
    }

    public function health(): View
    {
        $checks = [
            'database'    => $this->checkDatabase(),
            'cache'       => $this->checkCache(),
            'storage'     => $this->checkStorage(),
            'queue'       => $this->checkQueue(),
        ];

        $allOk = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return view('superadmin.health', compact('checks', 'allOk'));
    }

    /**
     * Halaman Deploy & Migrasi — untuk shared hosting tanpa SSH.
     * Hanya dapat diakses role superadmin (middleware superadmin.global).
     */
    public function deploy(): View
    {
        $migrator = app('migrator');
        $repository = $migrator->getRepository();

        $ran     = $repository->repositoryExists() ? $repository->getRan() : [];
        $files   = $migrator->getMigrationFiles(database_path('migrations'));
        $allNames = array_keys($files);
        $pending = array_values(array_diff($allNames, $ran));

        return view('superadmin.deploy', [
            'ranCount'   => count($ran),
            'totalCount' => count($allNames),
            'pending'    => $pending,
            'output'     => session('deploy_output'),
        ]);
    }

    /**
     * Jalankan migrasi yang tertunda (production-safe, --force, idempoten).
     */
    public function runMigrate(): RedirectResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            SecurityLog::record('superadmin.migrate', 'high', ['output' => mb_substr($output, 0, 5000)]);

            return back()
                ->with('success', 'Migrasi selesai dijalankan.')
                ->with('deploy_output', $output);
        } catch (\Throwable $e) {
            Log::error('Superadmin migrate gagal', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'Migrasi gagal: ' . $e->getMessage())
                ->with('deploy_output', Artisan::output());
        }
    }

    /**
     * Bersihkan cache config/route/view/cache (pengganti optimize:clear tanpa SSH).
     */
    public function clearCache(): RedirectResponse
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

        SecurityLog::record('superadmin.clear_cache', 'medium', ['results' => $results]);

        $ok = array_keys(array_filter($results, fn ($r) => $r === 'ok'));

        return back()->with('success', 'Cache dibersihkan: ' . implode(', ', $ok) . '.');
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Koneksi database aktif.'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $val = Cache::get('health_check');
            return ['status' => $val === 'ok' ? 'ok' : 'error', 'message' => 'Cache ' . ($val === 'ok' ? 'aktif.' : 'bermasalah.')];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        $path = storage_path('app/public');
        return [
            'status'  => is_writable($path) ? 'ok' : 'warning',
            'message' => is_writable($path) ? 'Storage dapat ditulis.' : 'Storage tidak dapat ditulis.',
        ];
    }

    private function checkQueue(): array
    {
        try {
            $count = DB::table('jobs')->count();
            return ['status' => 'ok', 'message' => "Antrian aktif. {$count} job menunggu."];
        } catch (\Throwable $e) {
            return ['status' => 'warning', 'message' => 'Tabel jobs tidak ditemukan.'];
        }
    }
}
