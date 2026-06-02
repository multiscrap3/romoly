<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\GamificationDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Opsi lebar: key => Bootstrap col class
    public const WIDTH_OPTIONS = [
        'small'   => 'col-6 col-md-3',
        'quarter' => 'col-12 col-md-3',
        'medium'  => 'col-12 col-md-6',
        'large'   => 'col-12 col-md-9',
        'full'    => 'col-12',
    ];

    // Opsi tinggi: key => label display
    public const HEIGHT_OPTIONS = ['auto', 'compact', 'normal', 'tall'];

    public const WIDGETS = [
        'hero_saldo'        => ['label' => 'Total Saldo',         'icon' => 'bi-wallet2',          'desc' => 'Ringkasan saldo dan cashflow bulan ini',               'default_width' => 'medium', 'default_height' => 'auto'],
        'card_anggaran'     => ['label' => 'Anggaran',            'icon' => 'bi-calculator',        'desc' => 'Persentase anggaran terpakai bulan ini',               'default_width' => 'small',  'default_height' => 'auto'],
        'card_tabungan'     => ['label' => 'Tabungan',            'icon' => 'bi-wallet2',           'desc' => 'Total terkumpul dari semua tujuan tabungan',           'default_width' => 'small',  'default_height' => 'auto'],
        'card_hutang'       => ['label' => 'Hutang & Piutang',    'icon' => 'bi-arrow-down-circle', 'desc' => 'Sisa hutang yang belum lunas',                         'default_width' => 'small',  'default_height' => 'auto'],
        'card_transaksi'    => ['label' => 'Catat Transaksi',     'icon' => 'bi-receipt',           'desc' => 'Shortcut ke halaman pencatatan transaksi',             'default_width' => 'small',  'default_height' => 'auto'],
        'chart_trend'       => ['label' => 'Tren 6 Bulan',        'icon' => 'bi-bar-chart-line',    'desc' => 'Grafik pemasukan dan pengeluaran 6 bulan terakhir',    'default_width' => 'medium', 'default_height' => 'auto'],
        'chart_kategori'    => ['label' => 'Grafik Kategori',     'icon' => 'bi-pie-chart',         'desc' => 'Distribusi pengeluaran per kategori bulan ini',        'default_width' => 'medium', 'default_height' => 'auto'],
        'saldo_rekening'    => ['label' => 'Saldo Rekening',      'icon' => 'bi-credit-card',       'desc' => 'Daftar saldo tiap rekening dan dompet',                'default_width' => 'medium', 'default_height' => 'auto'],
        'transaksi_terbaru' => ['label' => 'Transaksi Terbaru',   'icon' => 'bi-clock-history',     'desc' => 'Daftar transaksi paling baru',                         'default_width' => 'medium', 'default_height' => 'auto'],
        'quick_actions'          => ['label' => 'Aksi Cepat',          'icon' => 'bi-lightning-charge',  'desc' => 'Pintasan ke fitur yang sering digunakan',              'default_width' => 'full',   'default_height' => 'auto'],
        'gamification_insight'   => ['label' => 'Progres Finansial',   'icon' => 'bi-trophy',            'desc' => 'Level, momentum, insight tantangan & achievement',      'default_width' => 'medium', 'default_height' => 'auto'],
        'top_tags'               => ['label' => 'Tag Bulan Ini',       'icon' => 'bi-tags',              'desc' => 'Tag dengan pengeluaran terbesar bulan ini',             'default_width' => 'small',  'default_height' => 'auto'],
    ];

    public const DEFAULT_LAYOUT = [
        ['id' => 'hero_saldo',           'visible' => true, 'width' => 'medium', 'height' => 'auto'],
        ['id' => 'gamification_insight', 'visible' => true, 'width' => 'medium', 'height' => 'auto'],
        ['id' => 'chart_trend',          'visible' => true, 'width' => 'medium', 'height' => 'auto'],
        ['id' => 'chart_kategori',       'visible' => true, 'width' => 'medium', 'height' => 'auto'],
        ['id' => 'saldo_rekening',       'visible' => true, 'width' => 'medium', 'height' => 'auto'],
        ['id' => 'transaksi_terbaru',    'visible' => true, 'width' => 'medium', 'height' => 'auto'],
        ['id' => 'quick_actions',        'visible' => true, 'width' => 'full',   'height' => 'auto'],
        ['id' => 'card_anggaran',        'visible' => true, 'width' => 'small',  'height' => 'auto'],
        ['id' => 'card_tabungan',        'visible' => true, 'width' => 'small',  'height' => 'auto'],
        ['id' => 'card_hutang',          'visible' => true, 'width' => 'small',  'height' => 'auto'],
        ['id' => 'card_transaksi',       'visible' => true, 'width' => 'small',  'height' => 'auto'],
        ['id' => 'top_tags',             'visible' => true, 'width' => 'small',  'height' => 'auto'],
    ];

    protected $dashboardService;
    protected $gamificationDashboardService;

    public function __construct(
        DashboardService $dashboardService,
        GamificationDashboardService $gamificationDashboardService,
    ) {
        $this->dashboardService             = $dashboardService;
        $this->gamificationDashboardService = $gamificationDashboardService;
    }

    public function index()
    {
        $user        = auth()->user();
        $savedLayout = $user->dashboard_cards;
        $validIds    = array_keys(self::WIDGETS);

        if ($savedLayout) {
            $widgetLayout = array_values(array_filter(
                $savedLayout,
                fn($w) => in_array($w['id'], $validIds)
            ));

            // Tambahkan widget baru yang belum ada di saved layout
            $savedIds = array_column($widgetLayout, 'id');
            foreach ($validIds as $id) {
                if (!in_array($id, $savedIds)) {
                    $def = self::WIDGETS[$id];
                    $widgetLayout[] = [
                        'id'      => $id,
                        'visible' => true,
                        'width'   => $def['default_width'],
                        'height'  => $def['default_height'],
                    ];
                }
            }

            // Pastikan setiap item punya width & height
            $needsResave = false;
            foreach ($widgetLayout as &$w) {
                $def        = self::WIDGETS[$w['id']];
                $w['width']  = $w['width']  ?? $def['default_width'];
                $w['height'] = $w['height'] ?? $def['default_height'];

                // Migrasi layout lama: sejajarkan hero_saldo & gamification_insight ke medium
                if ($w['id'] === 'hero_saldo' && $w['width'] === 'large') {
                    $w['width'] = 'medium'; $needsResave = true;
                }
                if ($w['id'] === 'gamification_insight' && $w['width'] === 'quarter') {
                    $w['width'] = 'medium'; $needsResave = true;
                }
            }
            unset($w);
            if ($needsResave) {
                $user->update(['dashboard_cards' => $widgetLayout]);
            }
        } else {
            $widgetLayout = self::DEFAULT_LAYOUT;
        }

        $summary                = $this->dashboardService->getSummary();
        $pengeluaranPerKategori = $this->dashboardService->getPengeluaranPerKategori();
        $saldoPerSumber         = $this->dashboardService->getSaldoPerSumber();
        $topTags                = $this->dashboardService->getTopTags(5);
        $gamificationSummary    = $this->gamificationDashboardService->getSummary($user);
        $gamificationInsights   = $this->gamificationDashboardService->getInsights($user);
        $widgetDefs             = self::WIDGETS;
        $widthOptions           = self::WIDTH_OPTIONS;
        $defaultLayout          = self::DEFAULT_LAYOUT;

        return view('dashboard', compact(
            'summary',
            'pengeluaranPerKategori',
            'saldoPerSumber',
            'topTags',
            'gamificationSummary',
            'gamificationInsights',
            'widgetLayout',
            'widgetDefs',
            'widthOptions',
            'defaultLayout'
        ));
    }

    public function saveLayout(Request $request)
    {
        try {
            $validIds      = array_keys(self::WIDGETS);
            $validWidths   = array_keys(self::WIDTH_OPTIONS);
            $validHeights  = self::HEIGHT_OPTIONS;
            $layout        = [];

            foreach ($request->input('layout', []) as $item) {
                $id = $item['id'] ?? null;
                if (!$id || !in_array($id, $validIds)) continue;

                $def = self::WIDGETS[$id];
                $layout[] = [
                    'id'      => $id,
                    'visible' => (bool) ($item['visible'] ?? true),
                    'width'   => in_array($item['width'] ?? '', $validWidths)  ? $item['width']  : $def['default_width'],
                    'height'  => in_array($item['height'] ?? '', $validHeights) ? $item['height'] : $def['default_height'],
                ];
            }

            // Pastikan semua widget ada
            $savedIds = array_column($layout, 'id');
            foreach ($validIds as $id) {
                if (!in_array($id, $savedIds)) {
                    $def = self::WIDGETS[$id];
                    $layout[] = ['id' => $id, 'visible' => true, 'width' => $def['default_width'], 'height' => $def['default_height']];
                }
            }

            auth()->user()->update(['dashboard_cards' => $layout]);

            return response()->json(['success' => true, 'message' => 'Layout dashboard berhasil disimpan.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan layout. Silakan coba lagi.'], 500);
        }
    }

    public function chartData(Request $request)
    {
        $data = match ($request->type ?? 'trend') {
            'trend'    => $this->dashboardService->getSummary()['chart_data'],
            'kategori' => $this->dashboardService->getPengeluaranPerKategori(),
            'sumber'   => $this->dashboardService->getSaldoPerSumber(),
            default    => [],
        };

        return response()->json($data);
    }
}
