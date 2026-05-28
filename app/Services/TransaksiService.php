<?php

namespace App\Services;

use App\Models\OcrHistory;
use App\Models\Transaksi;
use App\Models\SumberTransaksi;
use App\Models\Anggaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransaksiService
{
    public function __construct(
        private readonly XpService $xpService,
        private readonly MomentumService $momentumService,
    ) {}

    /**
     * Create transaksi baru
     */
    public function create(array $data): Transaksi
    {
        return DB::transaction(function () use ($data) {
            // Create transaksi
            $ocrItems = null;
            if (!empty($data['ocr_items'])) {
                $decoded = is_array($data['ocr_items'])
                    ? $data['ocr_items']
                    : json_decode($data['ocr_items'], true);
                $ocrItems = is_array($decoded) && count($decoded) ? $decoded : null;
            }

            $transaksi = Transaksi::create([
                'household_id' => auth()->user()->household_id,
                'user_id' => auth()->id(),
                'kategori_id' => $data['kategori_id'],
                'sumber_transaksi_id' => $data['sumber_transaksi_id'],
                'jenis' => $data['jenis'],
                'jumlah' => $data['jumlah'],
                'tanggal' => $data['tanggal'],
                'keterangan' => $data['keterangan'] ?? null,
                'bukti_transaksi' => $data['bukti_transaksi'] ?? null,
                'transfer_ke_id' => $data['transfer_ke_id'] ?? null,
                'ocr_history_id' => $data['ocr_history_id'] ?? null,
                'ocr_items' => $ocrItems,
            ]);

            if (!empty($data['ocr_history_id'])) {
                OcrHistory::where('id', $data['ocr_history_id'])->update(['transaksi_id' => $transaksi->id]);
            }

            // Update saldo sumber transaksi
            $this->updateSaldo($transaksi);

            // Update anggaran terpakai (jika pengeluaran)
            if ($transaksi->jenis === 'pengeluaran') {
                $this->updateAnggaranTerpakai($transaksi);
            }

            // Attach tags jika ada
            if (!empty($data['tags'])) {
                $transaksi->tags()->attach($data['tags']);
            }

            // Award XP dan catat momentum activity
            $user = auth()->user();
            if ($user) {
                $this->xpService->award($user, 'transaction', [
                    'transaksi_id' => $transaksi->id,
                    'amount'       => $transaksi->jumlah,
                    'jenis'        => $transaksi->jenis,
                ]);
                $this->momentumService->recordActivity($user, 'transaction_logged');
            }

            return $transaksi->load(['kategori', 'sumberTransaksi', 'tags']);
        });
    }

    /**
     * Update transaksi
     */
    public function update(Transaksi $transaksi, array $data): Transaksi
    {
        return DB::transaction(function () use ($transaksi, $data) {
            // Simpan data lama untuk rollback saldo
            $oldJumlah = $transaksi->jumlah;
            $oldJenis = $transaksi->jenis;
            $oldSumberId = $transaksi->sumber_transaksi_id;

            // Rollback saldo lama
            $this->rollbackSaldo($transaksi);

            // Update transaksi
            $transaksi->update([
                'kategori_id' => $data['kategori_id'] ?? $transaksi->kategori_id,
                'sumber_transaksi_id' => $data['sumber_transaksi_id'] ?? $transaksi->sumber_transaksi_id,
                'jenis' => $data['jenis'] ?? $transaksi->jenis,
                'jumlah' => $data['jumlah'] ?? $transaksi->jumlah,
                'tanggal' => $data['tanggal'] ?? $transaksi->tanggal,
                'keterangan' => $data['keterangan'] ?? $transaksi->keterangan,
                'transfer_ke_id' => $data['transfer_ke_id'] ?? $transaksi->transfer_ke_id,
            ]);

            // Update saldo baru
            $this->updateSaldo($transaksi);

            // Update anggaran
            if ($oldJenis === 'pengeluaran') {
                $this->rollbackAnggaranTerpakai($oldJumlah, $transaksi->kategori_id, $transaksi->tanggal);
            }
            if ($transaksi->jenis === 'pengeluaran') {
                $this->updateAnggaranTerpakai($transaksi);
            }

            // Sync tags
            if (isset($data['tags'])) {
                $transaksi->tags()->sync($data['tags']);
            }

            return $transaksi->fresh(['kategori', 'sumberTransaksi', 'tags']);
        });
    }

    /**
     * Delete transaksi (soft delete)
     */
    public function delete(Transaksi $transaksi): bool
    {
        return DB::transaction(function () use ($transaksi) {
            // Rollback saldo
            $this->rollbackSaldo($transaksi);

            // Rollback anggaran
            if ($transaksi->jenis === 'pengeluaran') {
                $this->rollbackAnggaranTerpakai($transaksi->jumlah, $transaksi->kategori_id, $transaksi->tanggal);
            }

            // Hapus bukti transaksi jika ada
            if ($transaksi->bukti_transaksi) {
                Storage::disk('public')->delete($transaksi->bukti_transaksi);
            }

            return $transaksi->delete();
        });
    }

    /**
     * Upload bukti transaksi
     */
    public function uploadBukti($file): string
    {
        $path = $file->store('transaksi/bukti', 'public');
        return $path;
    }

    /**
     * Update saldo sumber transaksi
     */
    protected function updateSaldo(Transaksi $transaksi): void
    {
        $sumber = SumberTransaksi::find($transaksi->sumber_transaksi_id);
        
        if ($transaksi->jenis === 'pemasukan') {
            $sumber->updateSaldo($transaksi->jumlah, 'add');
        } elseif ($transaksi->jenis === 'pengeluaran') {
            $sumber->updateSaldo($transaksi->jumlah, 'subtract');
        } elseif ($transaksi->jenis === 'transfer' && $transaksi->transfer_ke_id) {
            // Kurangi dari sumber asal
            $sumber->updateSaldo($transaksi->jumlah, 'subtract');
            
            // Tambah ke sumber tujuan
            $sumberTujuan = SumberTransaksi::find($transaksi->transfer_ke_id);
            $sumberTujuan->updateSaldo($transaksi->jumlah, 'add');
        }
    }

    /**
     * Rollback saldo sumber transaksi
     */
    protected function rollbackSaldo(Transaksi $transaksi): void
    {
        $sumber = SumberTransaksi::find($transaksi->sumber_transaksi_id);
        
        if ($transaksi->jenis === 'pemasukan') {
            $sumber->updateSaldo($transaksi->jumlah, 'subtract');
        } elseif ($transaksi->jenis === 'pengeluaran') {
            $sumber->updateSaldo($transaksi->jumlah, 'add');
        } elseif ($transaksi->jenis === 'transfer' && $transaksi->transfer_ke_id) {
            $sumber->updateSaldo($transaksi->jumlah, 'add');
            $sumberTujuan = SumberTransaksi::find($transaksi->transfer_ke_id);
            $sumberTujuan->updateSaldo($transaksi->jumlah, 'subtract');
        }
    }

    /**
     * Update anggaran terpakai
     */
    protected function updateAnggaranTerpakai(Transaksi $transaksi): void
    {
        $anggaran = Anggaran::where('household_id', $transaksi->household_id)
            ->where('kategori_id', $transaksi->kategori_id)
            ->where('bulan', $transaksi->tanggal->month)
            ->where('tahun', $transaksi->tanggal->year)
            ->first();

        if ($anggaran) {
            $anggaran->increment('terpakai', $transaksi->jumlah);
        }
    }

    /**
     * Rollback anggaran terpakai
     */
    protected function rollbackAnggaranTerpakai(float $jumlah, int $kategoriId, $tanggal): void
    {
        $anggaran = Anggaran::where('household_id', auth()->user()->household_id)
            ->where('kategori_id', $kategoriId)
            ->where('bulan', $tanggal->month)
            ->where('tahun', $tanggal->year)
            ->first();

        if ($anggaran) {
            $anggaran->decrement('terpakai', $jumlah);
        }
    }

    /**
     * Get transaksi dengan filter
     */
    public function getTransaksi(array $filters = [])
    {
        $query = Transaksi::with(['kategori', 'sumberTransaksi', 'user', 'tags'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by jenis
        if (!empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }

        // Filter by kategori
        if (!empty($filters['kategori_id'])) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        // Filter by sumber
        if (!empty($filters['sumber_transaksi_id'])) {
            $query->where('sumber_transaksi_id', $filters['sumber_transaksi_id']);
        }

        // Filter by date range
        if (!empty($filters['tanggal_dari'])) {
            $query->whereDate('tanggal', '>=', $filters['tanggal_dari']);
        }
        if (!empty($filters['tanggal_sampai'])) {
            $query->whereDate('tanggal', '<=', $filters['tanggal_sampai']);
        }

        // Filter by tags
        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', $filters['tags']);
            });
        }

        // Search
        if (!empty($filters['search'])) {
            $query->where('keterangan', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get summary transaksi
     */
    public function getSummary(array $filters = []): array
    {
        $query = Transaksi::query();

        // Apply date filters
        if (!empty($filters['tanggal_dari'])) {
            $query->whereDate('tanggal', '>=', $filters['tanggal_dari']);
        }
        if (!empty($filters['tanggal_sampai'])) {
            $query->whereDate('tanggal', '<=', $filters['tanggal_sampai']);
        }

        $pemasukan = (clone $query)->where('jenis', 'pemasukan')->sum('jumlah');
        $pengeluaran = (clone $query)->where('jenis', 'pengeluaran')->sum('jumlah');
        $saldo = $pemasukan - $pengeluaran;

        return [
            'total_pemasukan' => $pemasukan,
            'total_pengeluaran' => $pengeluaran,
            'saldo' => $saldo,
            'total_transaksi' => $query->count(),
        ];
    }
}
