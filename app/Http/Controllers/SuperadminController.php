<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Household;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
