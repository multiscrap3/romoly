<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Transaksi;
use App\Models\Kategori;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanService
{
    /**
     * Generate laporan harian
     */
    public function laporanHarian(string $tanggal): array
    {
        $date = Carbon::parse($tanggal);

        $transaksi = Transaksi::with(['kategori', 'sumberTransaksi', 'user'])
            ->whereDate('tanggal', $date)
            ->orderBy('tanggal', 'desc')
            ->get();

        $pemasukan = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
        $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');

        return [
            'tanggal' => $date->format('d F Y'),
            'transaksi' => $transaksi,
            'total_pemasukan' => $pemasukan,
            'total_pengeluaran' => $pengeluaran,
            'cashflow' => $pemasukan - $pengeluaran,
            'summary' => [
                'pemasukan' => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'selisih' => $pemasukan - $pengeluaran,
                'total_transaksi' => $transaksi->count(),
            ],
        ];
    }

    /**
     * Generate laporan mingguan
     */
    public function laporanMingguan(string $tanggalMulai): array
    {
        $start = Carbon::parse($tanggalMulai)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $transaksi = Transaksi::with(['kategori', 'sumberTransaksi'])
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal', 'desc')
            ->get();

        $pemasukan = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
        $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');

        // Group by day
        $perHari = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayTransaksi = $transaksi->filter(function ($t) use ($date) {
                return $t->tanggal->isSameDay($date);
            });

            $perHari[] = [
                'tanggal' => $date->format('d M'),
                'pemasukan' => $dayTransaksi->where('jenis', 'pemasukan')->sum('jumlah'),
                'pengeluaran' => $dayTransaksi->where('jenis', 'pengeluaran')->sum('jumlah'),
            ];
        }

        return [
            'periode' => $start->format('d M') . ' - ' . $end->format('d M Y'),
            'transaksi' => $transaksi,
            'per_hari' => $perHari,
            'total_pemasukan' => $pemasukan,
            'total_pengeluaran' => $pengeluaran,
            'cashflow' => $pemasukan - $pengeluaran,
            'summary' => [
                'pemasukan' => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'selisih' => $pemasukan - $pengeluaran,
                'total_transaksi' => $transaksi->count(),
            ],
        ];
    }

    /**
     * Generate laporan bulanan
     */
    public function laporanBulanan(int $bulan, int $tahun): array
    {
        $date = Carbon::create($tahun, $bulan, 1);
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $transaksi = Transaksi::with(['kategori', 'sumberTransaksi'])
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal', 'desc')
            ->get();

        $pemasukan = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
        $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');

        // Per kategori
        $perKategori = $this->groupByKategori($transaksi->where('jenis', 'pengeluaran'));

        // Per minggu
        $perMinggu = $this->groupByWeek($transaksi, $start, $end);

        return [
            'periode' => $date->format('F Y'),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'transaksi' => $transaksi,
            'pengeluaran_per_kategori' => $perKategori,
            'per_minggu' => $perMinggu,
            'total_pemasukan' => $pemasukan,
            'total_pengeluaran' => $pengeluaran,
            'cashflow' => $pemasukan - $pengeluaran,
            'saving_rate' => $pemasukan > 0 ? round((($pemasukan - $pengeluaran) / $pemasukan) * 100, 1) : 0,
            'summary' => [
                'pemasukan' => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'selisih' => $pemasukan - $pengeluaran,
                'total_transaksi' => $transaksi->count(),
                'rata_rata_harian' => $pengeluaran / $date->daysInMonth,
            ],
        ];
    }

    /**
     * Generate laporan tahunan
     */
    public function laporanTahunan(int $tahun): array
    {
        $start = Carbon::create($tahun, 1, 1)->startOfYear();
        $end = Carbon::create($tahun, 12, 31)->endOfYear();

        $transaksi = Transaksi::with(['kategori'])
            ->whereBetween('tanggal', [$start, $end])
            ->get();

        $pemasukan = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
        $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');

        // Per bulan (keyed by month number 1-12)
        $perBulan = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTransaksi = $transaksi->filter(function ($t) use ($m) {
                return $t->tanggal->month == $m;
            });

            $pemasukanBulan = $monthTransaksi->where('jenis', 'pemasukan')->sum('jumlah');
            $pengeluaranBulan = $monthTransaksi->where('jenis', 'pengeluaran')->sum('jumlah');

            $perBulan[$m] = [
                'pemasukan' => $pemasukanBulan,
                'pengeluaran' => $pengeluaranBulan,
                'cashflow' => $pemasukanBulan - $pengeluaranBulan,
            ];
        }

        // Per kategori
        $perKategori = $this->groupByKategori($transaksi->where('jenis', 'pengeluaran'));

        return [
            'tahun' => $tahun,
            'per_bulan' => $perBulan,
            'per_kategori' => $perKategori,
            'total_pemasukan' => $pemasukan,
            'total_pengeluaran' => $pengeluaran,
            'cashflow' => $pemasukan - $pengeluaran,
            'saving_rate' => $pemasukan > 0 ? round((($pemasukan - $pengeluaran) / $pemasukan) * 100, 1) : 0,
            'summary' => [
                'pemasukan' => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'selisih' => $pemasukan - $pengeluaran,
                'total_transaksi' => $transaksi->count(),
                'rata_rata_bulanan' => $pengeluaran / 12,
            ],
        ];
    }

    /**
     * Group transaksi by kategori
     */
    protected function groupByKategori($transaksi): array
    {
        $grouped = $transaksi->groupBy('kategori_id');
        $result = [];

        foreach ($grouped as $kategoriId => $items) {
            $kategori = $items->first()->kategori;
            $result[] = [
                'nama' => $kategori->nama,
                'total' => $items->sum('jumlah'),
                'count' => $items->count(),
                'persentase' => 0,
            ];
        }

        // Calculate percentage
        $total = collect($result)->sum('total');
        foreach ($result as &$item) {
            $item['persentase'] = $total > 0 ? round(($item['total'] / $total) * 100, 2) : 0;
        }

        // Sort by jumlah desc
        usort($result, function ($a, $b) {
            return $b['jumlah'] <=> $a['jumlah'];
        });

        return $result;
    }

    /**
     * Group transaksi by week
     */
    protected function groupByWeek($transaksi, Carbon $start, Carbon $end): array
    {
        $weeks = [];
        $current = $start->copy()->startOfWeek();

        while ($current->lte($end)) {
            $weekEnd = $current->copy()->endOfWeek();
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $weekTransaksi = $transaksi->filter(function ($t) use ($current, $weekEnd) {
                return $t->tanggal->between($current, $weekEnd);
            });

            $weeks[] = [
                'minggu' => 'Minggu ' . $current->weekOfMonth,
                'periode' => $current->format('d M') . ' - ' . $weekEnd->format('d M'),
                'pemasukan' => $weekTransaksi->where('jenis', 'pemasukan')->sum('jumlah'),
                'pengeluaran' => $weekTransaksi->where('jenis', 'pengeluaran')->sum('jumlah'),
            ];

            $current->addWeek();
        }

        return $weeks;
    }

    /**
     * Laporan perbandingan bulan
     */
    public function perbandinganBulan(int $bulan1, int $tahun1, int $bulan2, int $tahun2): array
    {
        $data1 = $this->laporanBulanan($bulan1, $tahun1);
        $data2 = $this->laporanBulanan($bulan2, $tahun2);

        $selisihPemasukan = $data2['total_pemasukan'] - $data1['total_pemasukan'];
        $selisihPengeluaran = $data2['total_pengeluaran'] - $data1['total_pengeluaran'];

        $persentasePemasukan = $data1['total_pemasukan'] > 0
            ? ($selisihPemasukan / $data1['total_pemasukan']) * 100
            : 0;

        $persentasePengeluaran = $data1['total_pengeluaran'] > 0
            ? ($selisihPengeluaran / $data1['total_pengeluaran']) * 100
            : 0;

        return [
            'periode1' => $data1['periode'],
            'periode2' => $data2['periode'],
            'bulan1' => [
                'total_pemasukan' => $data1['total_pemasukan'],
                'total_pengeluaran' => $data1['total_pengeluaran'],
                'cashflow' => $data1['cashflow'],
            ],
            'bulan2' => [
                'total_pemasukan' => $data2['total_pemasukan'],
                'total_pengeluaran' => $data2['total_pengeluaran'],
                'cashflow' => $data2['cashflow'],
            ],
            'perbandingan' => [
                'selisih_pemasukan' => $selisihPemasukan,
                'selisih_pengeluaran' => $selisihPengeluaran,
                'persentase_pemasukan' => round($persentasePemasukan, 2),
                'persentase_pengeluaran' => round($persentasePengeluaran, 2),
            ],
        ];
    }

    /**
     * Laporan semua transaksi bertag tertentu dalam rentang tanggal.
     */
    public function getByTag(Tag $tag, string $dari, string $sampai): array
    {
        $start = Carbon::parse($dari)->startOfDay();
        $end   = Carbon::parse($sampai)->endOfDay();

        $transaksi = Transaksi::with(['kategori', 'sumberTransaksi', 'user'])
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal', 'desc')
            ->get();

        $pemasukan   = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
        $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');

        // Tren 6 bulan terakhir untuk chart
        $perBulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date   = Carbon::now()->subMonths($i);
            $bulanT = $transaksi->filter(
                fn ($t) => $t->tanggal->year == $date->year && $t->tanggal->month == $date->month
            );
            $perBulan[] = [
                'bulan'       => $date->translatedFormat('M Y'),
                'pemasukan'   => $bulanT->where('jenis', 'pemasukan')->sum('jumlah'),
                'pengeluaran' => $bulanT->where('jenis', 'pengeluaran')->sum('jumlah'),
            ];
        }

        $perKategori = $this->groupByKategori($transaksi->where('jenis', 'pengeluaran'));

        return [
            'tag'               => $tag,
            'periode'           => $start->translatedFormat('d M Y') . ' s/d ' . $end->translatedFormat('d M Y'),
            'dari'              => $dari,
            'sampai'            => $sampai,
            'transaksi'         => $transaksi,
            'total_pemasukan'   => $pemasukan,
            'total_pengeluaran' => $pengeluaran,
            'cashflow'          => $pemasukan - $pengeluaran,
            'per_bulan'         => $perBulan,
            'per_kategori'      => $perKategori,
            'summary' => [
                'pemasukan'       => $pemasukan,
                'pengeluaran'     => $pengeluaran,
                'selisih'         => $pemasukan - $pengeluaran,
                'total_transaksi' => $transaksi->count(),
            ],
        ];
    }
}
