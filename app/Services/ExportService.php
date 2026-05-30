<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\Anggaran;
use App\Models\Tabungan;
use App\Models\HutangPiutang;
use Carbon\Carbon;

class ExportService
{
    /**
     * Export transaksi to CSV
     */
    public function exportTransaksiCSV(array $filters = []): string
    {
        $query = Transaksi::with(['kategori', 'sumberTransaksi', 'tags']);

        // Apply filters
        if (!empty($filters['tanggal_dari'])) {
            $query->where('tanggal', '>=', $filters['tanggal_dari']);
        }
        if (!empty($filters['tanggal_sampai'])) {
            $query->where('tanggal', '<=', $filters['tanggal_sampai']);
        }
        if (!empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }
        if (!empty($filters['kategori_id'])) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->get();

        // Generate CSV
        $csv = "Tanggal,Jenis,Kategori,Sumber,Jumlah,Keterangan,Tags\n";

        foreach ($transaksi as $t) {
            $tags = $t->tags->pluck('nama')->implode(', ');
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $t->tanggal,
                ucfirst($t->jenis),
                $t->kategori->nama,
                $t->sumberTransaksi->nama,
                $t->jumlah,
                str_replace(',', ';', $t->keterangan ?? ''),
                $tags
            );
        }

        return $csv;
    }

    /**
     * Export transaksi to Excel format (array for library)
     */
    public function exportTransaksiExcel(array $filters = []): array
    {
        $query = Transaksi::with(['kategori', 'sumberTransaksi', 'tags']);

        // Apply filters
        if (!empty($filters['tanggal_dari'])) {
            $query->where('tanggal', '>=', $filters['tanggal_dari']);
        }
        if (!empty($filters['tanggal_sampai'])) {
            $query->where('tanggal', '<=', $filters['tanggal_sampai']);
        }
        if (!empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }
        if (!empty($filters['kategori_id'])) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->get();

        $data = [
            ['Tanggal', 'Jenis', 'Kategori', 'Sumber', 'Jumlah', 'Keterangan', 'Tags']
        ];

        foreach ($transaksi as $t) {
            $tags = $t->tags->pluck('nama')->implode(', ');
            $data[] = [
                $t->tanggal,
                ucfirst($t->jenis),
                $t->kategori->nama,
                $t->sumberTransaksi->nama,
                $t->jumlah,
                $t->keterangan ?? '',
                $tags
            ];
        }

        return $data;
    }

    /**
     * Export laporan bulanan to PDF format (array for library)
     */
    public function exportLaporanPDF(string $bulan): array
    {
        $startDate = Carbon::parse($bulan . '-01');
        $endDate = $startDate->copy()->endOfMonth();

        $transaksi = Transaksi::with(['kategori', 'sumberTransaksi'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->get();

        $pemasukan = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
        $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');
        $selisih = $pemasukan - $pengeluaran;

        // Group by kategori
        $byKategori = $transaksi->groupBy('kategori_id')->map(function ($items) {
            return [
                'kategori' => $items->first()->kategori->nama,
                'jenis' => $items->first()->jenis,
                'total' => $items->sum('jumlah'),
                'count' => $items->count(),
            ];
        })->values();

        return [
            'periode' => $startDate->format('F Y'),
            'summary' => [
                'pemasukan' => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'selisih' => $selisih,
            ],
            'by_kategori' => $byKategori,
            'transaksi' => $transaksi,
        ];
    }

    /**
     * Export anggaran to CSV
     */
    public function exportAnggaranCSV(string $bulan): string
    {
        $anggaran = Anggaran::with('kategori')
            ->where('bulan', $bulan)
            ->get();

        $csv = "Kategori,Target,Terpakai,Sisa,Persentase,Status\n";

        foreach ($anggaran as $a) {
            $persentase = $a->target > 0 ? ($a->terpakai / $a->target * 100) : 0;
            $status = $persentase >= 100 ? 'Over Budget' : ($persentase >= 80 ? 'Mendekati Limit' : 'Aman');

            $csv .= sprintf(
                "%s,%s,%s,%s,%.2f%%,%s\n",
                $a->kategori->nama,
                $a->target,
                $a->terpakai,
                $a->target - $a->terpakai,
                $persentase,
                $status
            );
        }

        return $csv;
    }

    /**
     * Export tabungan to CSV
     */
    public function exportTabunganCSV(): string
    {
        $tabungan = Tabungan::all();

        $csv = "Nama,Target,Terkumpul,Sisa,Progress,Deadline,Status\n";

        foreach ($tabungan as $t) {
            $progress = $t->target > 0 ? ($t->terkumpul / $t->target * 100) : 0;

            $csv .= sprintf(
                "%s,%s,%s,%s,%.2f%%,%s,%s\n",
                $t->nama,
                $t->target,
                $t->terkumpul,
                $t->target - $t->terkumpul,
                $progress,
                $t->deadline ?? '-',
                ucfirst($t->status)
            );
        }

        return $csv;
    }

    /**
     * Export hutang/piutang to CSV
     */
    public function exportHutangPiutangCSV(string $jenis = null): string
    {
        $query = HutangPiutang::query();

        if ($jenis) {
            $query->where('jenis', $jenis);
        }

        $items = $query->orderBy('tanggal', 'desc')->get();

        $csv = "Jenis,Nama Pihak,Jumlah,Sisa,Tanggal,Jatuh Tempo,Status\n";

        foreach ($items as $item) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                ucfirst($item->jenis),
                $item->nama_pihak,
                $item->jumlah,
                $item->sisa,
                $item->tanggal,
                $item->jatuh_tempo ?? '-',
                ucfirst($item->status)
            );
        }

        return $csv;
    }

    /**
     * Generate filename for export
     */
    public function generateFilename(string $type, string $extension, array $params = []): string
    {
        $timestamp = Carbon::now()->format('YmdHis');
        $household = auth()->user()->household->nama ?? 'Romoly';
        $household = preg_replace('/[^A-Za-z0-9\-]/', '_', $household);

        $filename = "{$household}_{$type}_{$timestamp}";

        if (!empty($params['bulan'])) {
            $filename .= '_' . str_replace('-', '', $params['bulan']);
        }

        return "{$filename}.{$extension}";
    }

    /**
     * Get export headers for download
     */
    public function getDownloadHeaders(string $filename, string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
