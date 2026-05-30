<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\Household;
use App\Models\Kategori;
use App\Models\RecurringTransaksi;
use App\Models\SumberTransaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user      = $request->user();
        $household = $user->household;

        if (! $household) {
            return redirect()->route('dashboard')
                ->with('error', 'Household tidak ditemukan.');
        }

        $step = session('onboarding_step', 1);

        $kategori = Kategori::where(function ($q) use ($household) {
            $q->whereNull('household_id')->orWhere('household_id', $household->id);
        })->whereNull('parent_id')->orderBy('nama')->get();

        $sumberTransaksi = SumberTransaksi::where('household_id', $household->id)
            ->orderBy('nama')->get();

        return view('onboarding.index', compact('household', 'step', 'kategori', 'sumberTransaksi'));
    }

    public function storeHousehold(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_household' => ['required', 'string', 'max:255'],
            'mata_uang'      => ['nullable', 'string', 'max:10'],
        ]);

        $household = $request->user()->household;

        $household->update([
            'nama' => $validated['nama_household'],
        ]);

        if (! empty($validated['mata_uang'])) {
            $household->settings()->updateOrCreate(
                ['key' => 'mata_uang'],
                ['value' => $validated['mata_uang']]
            );
        }

        session(['onboarding_step' => 2]);

        return redirect()->route('onboarding.index')
            ->with('success', 'Nama household berhasil disimpan.');
    }

    public function storeRekening(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rekening'                => ['required', 'array', 'min:1'],
            'rekening.*.nama'         => ['required', 'string', 'max:255'],
            'rekening.*.jenis'        => ['required', 'in:cash,bank,e-wallet,kartu_kredit,investasi,lainnya'],
            'rekening.*.saldo_awal'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $householdId = $request->user()->household_id;

        DB::transaction(function () use ($validated, $householdId) {
            foreach ($validated['rekening'] as $rekening) {
                SumberTransaksi::create([
                    'household_id'  => $householdId,
                    'nama'          => $rekening['nama'],
                    'jenis'         => $rekening['jenis'],
                    'saldo_awal'    => $rekening['saldo_awal'] ?? 0,
                    'saldo_saat_ini'=> $rekening['saldo_awal'] ?? 0,
                    'is_active'     => true,
                ]);
            }
        });

        session(['onboarding_step' => 3]);

        return redirect()->route('onboarding.index')
            ->with('success', 'Sumber dana berhasil ditambahkan.');
    }

    public function storeAnggaran(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'anggaran'              => ['nullable', 'array'],
            'anggaran.*.kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'anggaran.*.limit'       => ['required', 'numeric', 'min:1000'],
        ]);

        if (! empty($validated['anggaran'])) {
            $householdId = $request->user()->household_id;
            $bulan       = now()->month;
            $tahun       = now()->year;

            DB::transaction(function () use ($validated, $householdId, $bulan, $tahun) {
                foreach ($validated['anggaran'] as $item) {
                    Anggaran::updateOrCreate(
                        [
                            'household_id' => $householdId,
                            'kategori_id'  => $item['kategori_id'],
                            'bulan'        => $bulan,
                            'tahun'        => $tahun,
                        ],
                        ['limit' => $item['limit']]
                    );
                }
            });
        }

        session(['onboarding_step' => 4]);

        return redirect()->route('onboarding.index')
            ->with('success', 'Anggaran berhasil disimpan.');
    }

    public function storeRecurring(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recurring'                    => ['nullable', 'array'],
            'recurring.*.nama'             => ['required', 'string', 'max:255'],
            'recurring.*.jenis'            => ['required', 'in:pemasukan,pengeluaran'],
            'recurring.*.jumlah'           => ['required', 'numeric', 'min:1'],
            'recurring.*.frekuensi'        => ['required', 'in:harian,mingguan,bulanan,tahunan'],
            'recurring.*.tanggal_mulai'    => ['required', 'date'],
            'recurring.*.sumber_transaksi_id' => ['nullable', 'integer', 'exists:sumber_transaksi,id'],
        ]);

        if (! empty($validated['recurring'])) {
            $householdId = $request->user()->household_id;
            $userId      = $request->user()->id;

            DB::transaction(function () use ($validated, $householdId, $userId) {
                foreach ($validated['recurring'] as $item) {
                    RecurringTransaksi::create([
                        'household_id'        => $householdId,
                        'user_id'             => $userId,
                        'nama'                => $item['nama'],
                        'jenis'               => $item['jenis'],
                        'jumlah'              => $item['jumlah'],
                        'frekuensi'           => $item['frekuensi'],
                        'tanggal_mulai'       => $item['tanggal_mulai'],
                        'sumber_transaksi_id' => $item['sumber_transaksi_id'] ?? null,
                        'is_active'           => true,
                    ]);
                }
            });
        }

        session(['onboarding_step' => 5]);

        return redirect()->route('onboarding.index')
            ->with('success', 'Transaksi rutin berhasil disimpan.');
    }

    public function selesai(Request $request): RedirectResponse
    {
        $request->user()->household->update([
            'onboarding_completed' => true,
        ]);

        session()->forget('onboarding_step');

        return redirect()->route('dashboard')
            ->with('success', 'Setup selesai! Selamat menggunakan Romoly.');
    }

    public function skip(Request $request): RedirectResponse
    {
        session()->forget('onboarding_step');

        return redirect()->route('dashboard')
            ->with('info', 'Kamu bisa melengkapi pengaturan nanti di menu Pengaturan.');
    }
}
