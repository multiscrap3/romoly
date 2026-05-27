<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\Kategori;
use App\Models\Plan;
use App\Models\User;
use App\Models\ConsentLog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        $invitation = null;
        if ($request->filled('token')) {
            $invitation = HouseholdInvitation::pending()
                ->where('token', $request->token)
                ->with('household')
                ->first();
        }

        return view('auth.register', compact('invitation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'token'    => ['nullable', 'string'],
            'consent'  => ['required', 'accepted'],
        ], [
            'consent.required' => 'Anda harus menyetujui Kebijakan Privasi dan Syarat & Ketentuan untuk mendaftar.',
            'consent.accepted' => 'Anda harus menyetujui Kebijakan Privasi dan Syarat & Ketentuan untuk mendaftar.',
        ]);

        DB::transaction(function () use ($request, &$user) {
            $invitation = null;
            $household  = null;

            if ($request->filled('token')) {
                $invitation = HouseholdInvitation::pending()
                    ->where('token', $request->token)
                    ->lockForUpdate()
                    ->firstOrFail();
                $household = $invitation->household;
            }

            if (! $household) {
                $freePlan = Plan::where('slug', 'free')->firstOrFail();
                $household = Household::create([
                    'nama'     => $request->name . "'s Household",
                    'slug'     => \Illuminate\Support\Str::slug($request->name . '-' . uniqid()),
                    'plan_id'  => $freePlan->id,
                    'status'   => 'active',
                ]);
                $this->createDefaultKategori($household->id);
            }

            $role = $invitation ? ($invitation->role ?? 'member') : 'owner';

            $user = User::create([
                'name'                   => $request->name,
                'email'                  => $request->email,
                'password'               => Hash::make($request->password),
                'household_id'           => $household->id,
                'is_active'              => true,
                'consent_given_at'       => now(),
                'consent_ip'             => $request->ip(),
                'privacy_policy_version' => '1.0',
            ]);

            $user->assignRole($role);

            ConsentLog::create([
                'user_id'        => $user->id,
                'type'           => 'register',
                'policy_version' => '1.0',
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'created_at'     => now(),
            ]);

            if ($invitation) {
                $invitation->update([
                    'status'      => 'accepted',
                    'accepted_at' => now(),
                ]);
            }
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        if ($user->hasRole('owner')) {
            return redirect()->route('onboarding.index')
                ->with('success', 'Selamat datang di Finanku! Mari mulai setup household Anda.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Akun berhasil dibuat. Selamat bergabung!');
    }

    private function createDefaultKategori(int $householdId): void
    {
        $pengeluaran = [
            ['nama' => 'Makanan & Minuman', 'icon' => '🍽️', 'warna' => '#F59E0B'],
            ['nama' => 'Transportasi',       'icon' => '🚗', 'warna' => '#3B82F6'],
            ['nama' => 'Tagihan & Utilitas', 'icon' => '💡', 'warna' => '#8B5CF6'],
            ['nama' => 'Belanja',            'icon' => '🛍️', 'warna' => '#EC4899'],
            ['nama' => 'Kesehatan',          'icon' => '🏥', 'warna' => '#EF4444'],
            ['nama' => 'Pendidikan',         'icon' => '📚', 'warna' => '#06B6D4'],
            ['nama' => 'Hiburan',            'icon' => '🎬', 'warna' => '#F97316'],
            ['nama' => 'Lainnya',            'icon' => '📦', 'warna' => '#6B7280'],
        ];

        $pemasukan = [
            ['nama' => 'Gaji',     'icon' => '💼', 'warna' => '#10B981'],
            ['nama' => 'Bonus',    'icon' => '🎁', 'warna' => '#34D399'],
            ['nama' => 'Investasi','icon' => '📈', 'warna' => '#059669'],
            ['nama' => 'Bisnis',   'icon' => '🏢', 'warna' => '#047857'],
            ['nama' => 'Lainnya',  'icon' => '💰', 'warna' => '#6B7280'],
        ];

        $now = now()->toDateTimeString();

        foreach ($pengeluaran as $i => $k) {
            Kategori::withoutGlobalScope('household')->insert([
                'household_id' => $householdId,
                'nama'         => $k['nama'],
                'jenis'        => 'pengeluaran',
                'icon'         => $k['icon'],
                'warna'        => $k['warna'],
                'urutan'       => $i + 1,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        foreach ($pemasukan as $i => $k) {
            Kategori::withoutGlobalScope('household')->insert([
                'household_id' => $householdId,
                'nama'         => $k['nama'],
                'jenis'        => 'pemasukan',
                'icon'         => $k['icon'],
                'warna'        => $k['warna'],
                'urutan'       => $i + 1,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
