<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LaporanController extends Controller
{
    protected $laporanService;

    public function __construct(LaporanService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Display laporan index
     */
    public function index()
    {
        return view('laporan.index');
    }

    /**
     * Laporan harian
     */
    public function harian(Request $request)
    {
        $tanggal = $request->tanggal ?? Carbon::today()->format('Y-m-d');
        $data = $this->laporanService->laporanHarian($tanggal);

        return view('laporan.harian', compact('data', 'tanggal'));
    }

    /**
     * Laporan mingguan
     */
    public function mingguan(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai ?? Carbon::now()->startOfWeek()->format('Y-m-d');
        $data = $this->laporanService->laporanMingguan($tanggalMulai);

        return view('laporan.mingguan', compact('data', 'tanggalMulai'));
    }

    /**
     * Laporan bulanan
     */
    public function bulanan(Request $request)
    {
        $bulan = $request->bulan ?? Carbon::now()->month;
        $tahun = $request->tahun ?? Carbon::now()->year;
        
        $data = $this->laporanService->laporanBulanan($bulan, $tahun);

        return view('laporan.bulanan', compact('data', 'bulan', 'tahun'));
    }

    /**
     * Laporan tahunan
     */
    public function tahunan(Request $request)
    {
        $tahun = $request->tahun ?? Carbon::now()->year;
        $data = $this->laporanService->laporanTahunan($tahun);

        return view('laporan.tahunan', compact('data', 'tahun'));
    }

    /**
     * Perbandingan bulan
     */
    public function perbandingan(Request $request)
    {
        $bulan1 = $request->bulan1 ?? Carbon::now()->subMonth()->month;
        $tahun1 = $request->tahun1 ?? Carbon::now()->subMonth()->year;
        $bulan2 = $request->bulan2 ?? Carbon::now()->month;
        $tahun2 = $request->tahun2 ?? Carbon::now()->year;

        $data = $this->laporanService->perbandinganBulan($bulan1, $tahun1, $bulan2, $tahun2);

        return view('laporan.perbandingan', compact('data', 'bulan1', 'tahun1', 'bulan2', 'tahun2'));
    }

    /**
     * Laporan per tag
     */
    public function byTag(Request $request, Tag $tag)
    {
        $dari   = $request->dari   ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $sampai = $request->sampai ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $data = $this->laporanService->getByTag($tag, $dari, $sampai);

        return view('laporan.tag', compact('data', 'tag', 'dari', 'sampai'));
    }

    /**
     * Export laporan
     */
    public function export(Request $request)
    {
        // TODO: Implement export with ExportService
        $jenis = $request->jenis; // harian, mingguan, bulanan, tahunan
        $format = $request->format; // pdf, excel
        
        return back()->with('info', 'Fitur export akan segera tersedia');
    }
}
