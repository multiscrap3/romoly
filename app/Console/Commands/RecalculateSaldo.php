<?php

namespace App\Console\Commands;

use App\Models\SumberTransaksi;
use App\Models\Transaksi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateSaldo extends Command
{
    protected $signature   = 'sumber:recalculate-saldo {--household= : Batasi ke household_id tertentu}';
    protected $description = 'Hitung ulang saldo_saat_ini setiap sumber dari transaksi aktual (saldo_awal + pemasukan - pengeluaran ± transfer)';

    public function handle(): int
    {
        $query = SumberTransaksi::query();

        if ($householdId = $this->option('household')) {
            $query->where('household_id', $householdId);
        }

        $sources = $query->withTrashed(false)->get();

        $this->info("Recalculating {$sources->count()} sumber transaksi...");
        $this->newLine();

        $rows = [];

        DB::transaction(function () use ($sources, &$rows) {
            foreach ($sources as $sumber) {
                $pemasukan = Transaksi::where('sumber_transaksi_id', $sumber->id)
                    ->where('jenis', 'pemasukan')
                    ->sum('jumlah');

                $pengeluaran = Transaksi::where('sumber_transaksi_id', $sumber->id)
                    ->where('jenis', 'pengeluaran')
                    ->sum('jumlah');

                $transferKeluar = Transaksi::where('sumber_transaksi_id', $sumber->id)
                    ->where('jenis', 'transfer')
                    ->sum('jumlah');

                $transferMasuk = Transaksi::where('transfer_ke_id', $sumber->id)
                    ->where('jenis', 'transfer')
                    ->sum('jumlah');

                $saldoBaru = $sumber->saldo_awal
                    + $pemasukan
                    - $pengeluaran
                    - $transferKeluar
                    + $transferMasuk;

                $rows[] = [
                    $sumber->id,
                    $sumber->nama,
                    number_format($sumber->saldo_saat_ini, 0, ',', '.'),
                    number_format($saldoBaru, 0, ',', '.'),
                    $sumber->saldo_saat_ini != $saldoBaru ? '<fg=yellow>BERUBAH</>' : '<fg=green>SAMA</>',
                ];

                $sumber->update(['saldo_saat_ini' => $saldoBaru]);
            }
        });

        $this->table(
            ['ID', 'Nama', 'Saldo Lama', 'Saldo Baru', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info('Selesai. Semua saldo_saat_ini sudah diperbarui.');

        return self::SUCCESS;
    }
}
