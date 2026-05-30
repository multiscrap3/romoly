<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\Household;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperadminAiMonitorController extends Controller
{
    public function index(Request $request): View
    {
        $period = (int) $request->get('days', 30);
        $period = in_array($period, [7, 14, 30, 90]) ? $period : 30;
        $since  = now()->subDays($period - 1)->startOfDay();

        // ── AI Usage summary stats ────────────────────────────────────────
        $todayTotal   = AiUsageLog::whereDate('created_at', today())->count();
        $todaySuccess = AiUsageLog::whereDate('created_at', today())->where('success', true)->count();
        $todayError   = $todayTotal - $todaySuccess;
        $monthTotal   = AiUsageLog::where('created_at', '>=', now()->startOfMonth())->count();
        $allTime      = AiUsageLog::count();
        $dailyLimit   = (int) (Setting::query()->where('key', 'ocr_daily_limit')->value('value') ?: 500);
        $successRate  = $todayTotal > 0 ? round($todaySuccess / $todayTotal * 100, 1) : 0;

        $stats = compact(
            'todayTotal', 'todaySuccess', 'todayError',
            'monthTotal', 'allTime', 'dailyLimit', 'successRate'
        );

        // ── Chart: calls per day ──────────────────────────────────────────
        $chartRows = AiUsageLog::selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(success) as ok')
            ->where('created_at', '>=', $since)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels  = [];
        $chartTotal   = [];
        $chartSuccess = [];
        $chartError   = [];

        for ($d = $since->copy(); $d->lte(now()); $d->addDay()) {
            $key = $d->toDateString();
            $row = $chartRows->get($key);
            $chartLabels[]  = $d->format('d/m');
            $total          = (int) ($row?->total ?? 0);
            $ok             = (int) ($row?->ok ?? 0);
            $chartTotal[]   = $total;
            $chartSuccess[] = $ok;
            $chartError[]   = $total - $ok;
        }

        // ── Action breakdown ──────────────────────────────────────────────
        $actionBreakdown = AiUsageLog::selectRaw('action, COUNT(*) as count')
            ->where('created_at', '>=', $since)
            ->groupBy('action')
            ->orderByDesc('count')
            ->pluck('count', 'action');

        // ── Token stats ───────────────────────────────────────────────────
        $tokenStats = AiUsageLog::where('created_at', '>=', $since)
            ->selectRaw('
                SUM(total_tokens)          as total_tokens,
                SUM(prompt_tokens)         as prompt_tokens,
                SUM(completion_tokens)     as completion_tokens,
                ROUND(AVG(total_tokens),0) as avg_tokens,
                COUNT(*)                   as call_count
            ')
            ->first();

        // ── Top users ─────────────────────────────────────────────────────
        $topUsers = AiUsageLog::selectRaw('user_id, COUNT(*) as count, SUM(success) as success_count')
            ->with('user:id,name,email')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // ── Top households ────────────────────────────────────────────────
        $topHouseholds = AiUsageLog::selectRaw('household_id, COUNT(*) as count')
            ->with('household:id,nama')
            ->where('created_at', '>=', $since)
            ->whereNotNull('household_id')
            ->groupBy('household_id')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        // ── Gemini config ─────────────────────────────────────────────────
        $geminiConfig = Setting::query()
            ->whereNull('household_id')
            ->whereIn('key', [
                'gemini_model', 'gemini_api_key', 'gemini_base_url',
                'ocr_daily_limit', 'gemini_reset_date', 'gemini_ocr_used_today',
            ])
            ->pluck('value', 'key');

        // ── AI API Logs (paginated) ───────────────────────────────────────
        $logs = AiUsageLog::with('user:id,name', 'household:id,nama')
            ->when($request->filled('action'),    fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('success'),   fn ($q) => $q->where('success', $request->success === '1'))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'),   fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(30, ['*'], 'page')
            ->withQueryString();

        // ── OCR History stats ─────────────────────────────────────────────
        $ocrStats = [
            'today_total'   => DB::table('ocr_history')->whereDate('created_at', today())->count(),
            'today_success' => DB::table('ocr_history')->whereDate('created_at', today())->where('status', 'success')->count(),
            'today_failed'  => DB::table('ocr_history')->whereDate('created_at', today())->where('status', 'failed')->count(),
            'month_total'   => DB::table('ocr_history')->where('created_at', '>=', now()->startOfMonth())->count(),
            'all_time'      => DB::table('ocr_history')->count(),
            'converted'     => DB::table('ocr_history')->whereNotNull('transaksi_id')->count(),
            'period_total'  => DB::table('ocr_history')->where('created_at', '>=', $since)->count(),
            'period_success'=> DB::table('ocr_history')->where('created_at', '>=', $since)->where('status', 'success')->count(),
        ];
        $ocrStats['conversion_rate'] = $ocrStats['all_time'] > 0
            ? round($ocrStats['converted'] / $ocrStats['all_time'] * 100, 1)
            : 0;
        $ocrStats['success_rate'] = $ocrStats['period_total'] > 0
            ? round($ocrStats['period_success'] / $ocrStats['period_total'] * 100, 1)
            : 0;

        // ── OCR per day chart ─────────────────────────────────────────────
        $ocrChartRows = DB::table('ocr_history')
            ->selectRaw("DATE(created_at) as date, COUNT(*) as total, SUM(status='success') as ok, SUM(status='failed') as fail")
            ->where('created_at', '>=', $since)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $ocrChartLabels  = [];
        $ocrChartTotal   = [];
        $ocrChartSuccess = [];
        $ocrChartFailed  = [];

        for ($d = $since->copy(); $d->lte(now()); $d->addDay()) {
            $key = $d->toDateString();
            $row = $ocrChartRows->get($key);
            $ocrChartLabels[]  = $d->format('d/m');
            $ocrChartTotal[]   = (int) ($row?->total ?? 0);
            $ocrChartSuccess[] = (int) ($row?->ok ?? 0);
            $ocrChartFailed[]  = (int) ($row?->fail ?? 0);
        }

        // ── OCR top merchants ─────────────────────────────────────────────
        $ocrTopMerchants = DB::table('ocr_history')
            ->selectRaw('detected_merchant, COUNT(*) as count, SUM(detected_amount) as total_amount')
            ->where('created_at', '>=', $since)
            ->whereNotNull('detected_merchant')
            ->where('status', 'success')
            ->groupBy('detected_merchant')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        // ── OCR History table (paginated + filtered) ──────────────────────
        $allHouseholds = Household::orderBy('nama')->get(['id', 'nama']);

        $ocrQuery = DB::table('ocr_history as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('households as h', 'h.id', '=', 'o.household_id')
            ->leftJoin('transaksi as t', 't.id', '=', 'o.transaksi_id')
            ->select(
                'o.id', 'o.image_path', 'o.status', 'o.error_message',
                'o.detected_amount', 'o.detected_date', 'o.detected_merchant',
                'o.ocr_result', 'o.transaksi_id', 'o.created_at',
                'u.name as user_name', 'u.email as user_email',
                'h.nama as household_nama',
                't.jumlah as transaksi_jumlah'
            )
            ->when($request->filled('ocr_status'), fn ($q) => $q->where('o.status', $request->ocr_status))
            ->when($request->filled('ocr_household'), fn ($q) => $q->where('o.household_id', $request->ocr_household))
            ->when($request->filled('ocr_merchant'), fn ($q) => $q->where('o.detected_merchant', 'like', '%' . $request->ocr_merchant . '%'))
            ->when($request->filled('ocr_date_from'), fn ($q) => $q->whereDate('o.created_at', '>=', $request->ocr_date_from))
            ->when($request->filled('ocr_date_to'), fn ($q) => $q->whereDate('o.created_at', '<=', $request->ocr_date_to))
            ->orderByDesc('o.created_at');

        $ocrHistory = $ocrQuery->paginate(25, ['*'], 'ocr_page')->withQueryString();

        $actionLabels = AiUsageLog::ACTIONS;

        return view('superadmin.ai-monitor', compact(
            'stats', 'period',
            'chartLabels', 'chartTotal', 'chartSuccess', 'chartError',
            'actionBreakdown', 'tokenStats',
            'topUsers', 'topHouseholds',
            'geminiConfig', 'logs', 'actionLabels',
            'ocrStats', 'ocrChartLabels', 'ocrChartTotal', 'ocrChartSuccess', 'ocrChartFailed',
            'ocrTopMerchants', 'ocrHistory', 'allHouseholds'
        ));
    }
}
