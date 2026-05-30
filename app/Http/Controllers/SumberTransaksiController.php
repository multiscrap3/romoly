<?php

namespace App\Http\Controllers;

use App\Models\SumberTransaksi;
use Illuminate\Http\Request;

class SumberTransaksiController extends Controller
{
    public function index()
    {
        $aktif = SumberTransaksi::active()
            ->withCount(['transaksi', 'transaksiTransferMasuk'])
            ->orderBy('nama')
            ->get();

        $arsip = SumberTransaksi::where('is_active', false)
            ->withCount(['transaksi', 'transaksiTransferMasuk'])
            ->orderBy('nama')
            ->get();

        $totalSaldo = $aktif->sum('saldo_saat_ini');

        return view('sumber-transaksi.index', compact('aktif', 'arsip', 'totalSaldo'));
    }

    public function create()
    {
        return view('sumber-transaksi.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'           => 'required|string|max:100',
            'jenis'          => 'required|in:bank,e-wallet,cash,kartu_kredit,investasi,lainnya',
            'saldo'          => 'required|numeric|min:0',
            'nomor_rekening' => 'nullable|string|max:50',
            'nama_bank'      => 'nullable|string|max:100',
            'icon'           => 'nullable|string|max:50',
            'warna'          => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        SumberTransaksi::create([
            'household_id'   => auth()->user()->household_id,
            'nama'           => $validated['nama'],
            'jenis'          => $validated['jenis'],
            'saldo_awal'     => $validated['saldo'],
            'saldo_saat_ini' => $validated['saldo'],
            'nomor_rekening' => $validated['nomor_rekening'] ?? null,
            'nama_bank'      => $validated['nama_bank'] ?? null,
            'icon'           => $validated['icon'] ?? null,
            'warna'          => $validated['warna'] ?? '#6c757d',
            'is_active'      => true,
        ]);

        return redirect()->route('sumber-transaksi.index')
            ->with('success', __('sumber.stored'));
    }

    public function edit(SumberTransaksi $sumberTransaksi)
    {
        return view('sumber-transaksi.edit', compact('sumberTransaksi'));
    }

    public function update(Request $request, SumberTransaksi $sumberTransaksi)
    {
        $validated = $request->validate([
            'nama'           => 'required|string|max:100',
            'jenis'          => 'required|in:bank,e-wallet,cash,kartu_kredit,investasi,lainnya',
            'nomor_rekening' => 'nullable|string|max:50',
            'nama_bank'      => 'nullable|string|max:100',
            'icon'           => 'nullable|string|max:50',
            'warna'          => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $sumberTransaksi->update($validated);

        return redirect()->route('sumber-transaksi.index')
            ->with('success', __('sumber.updated'));
    }

    public function destroy(SumberTransaksi $sumberTransaksi)
    {
        // Block if saldo is not zero
        if ((float) $sumberTransaksi->saldo_saat_ini !== 0.0) {
            $formatted = 'Rp ' . number_format($sumberTransaksi->saldo_saat_ini, 0, ',', '.');
            return back()->with('error', __('sumber.error_has_saldo', ['saldo' => $formatted]));
        }

        // Block if has transactions — offer archive instead
        $jumlah = $sumberTransaksi->transaksi()->count()
                + $sumberTransaksi->transaksiTransferMasuk()->count();

        if ($jumlah > 0) {
            return back()->with('warning', __('sumber.error_has_transaksi', ['count' => $jumlah]));
        }

        $sumberTransaksi->delete();

        return redirect()->route('sumber-transaksi.index')
            ->with('success', __('sumber.deleted'));
    }

    public function deactivate(SumberTransaksi $sumberTransaksi)
    {
        $sumberTransaksi->update(['is_active' => false]);

        return redirect()->route('sumber-transaksi.index')
            ->with('success', __('sumber.deactivated'));
    }

    public function activate(SumberTransaksi $sumberTransaksi)
    {
        $sumberTransaksi->update(['is_active' => true]);

        return redirect()->route('sumber-transaksi.index')
            ->with('success', __('sumber.activated'));
    }

    public function getSaldo(Request $request)
    {
        $sumber = SumberTransaksi::find($request->sumber_id);

        if (! $sumber) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'id'              => $sumber->id,
            'nama'            => $sumber->nama,
            'saldo'           => $sumber->saldo_saat_ini,
            'saldo_formatted' => 'Rp ' . number_format($sumber->saldo_saat_ini, 0, ',', '.'),
        ]);
    }

    public function adjustSaldo(Request $request, SumberTransaksi $sumberTransaksi)
    {
        $request->validate([
            'saldo_baru' => 'required|numeric',
            'keterangan' => 'required|string|max:500',
        ]);

        $saldoLama = $sumberTransaksi->saldo_saat_ini;
        $saldoBaru = $request->saldo_baru;

        $sumberTransaksi->update(['saldo_saat_ini' => $saldoBaru]);

        \App\Models\AuditLog::create([
            'household_id' => auth()->user()->household_id,
            'user_id'      => auth()->id(),
            'action'       => 'adjust_saldo',
            'model'        => 'SumberTransaksi',
            'model_id'     => $sumberTransaksi->id,
            'data'         => json_encode([
                'saldo_lama' => $saldoLama,
                'saldo_baru' => $saldoBaru,
                'selisih'    => $saldoBaru - $saldoLama,
                'keterangan' => $request->keterangan,
            ]),
        ]);

        return back()->with('success', __('sumber.saldo_adjusted'));
    }
}
