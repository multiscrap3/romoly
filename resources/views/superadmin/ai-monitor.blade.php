@extends('layouts.superadmin')

@section('title', 'AI & OCR Monitor')
@section('page-title', 'AI & OCR Monitor')

@push('styles')
<style>
.stat-card { border-radius: .75rem; border: none; box-shadow: 0 1px 4px rgba(0,0,0,.08); transition: box-shadow .15s; }
.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.12); }
.stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.progress-thin { height: 6px; border-radius: 3px; }
.action-badge { font-size: .68rem; padding: 3px 8px; border-radius: 999px; font-weight: 600; }
.action-ocr              { background: #dbeafe; color: #1d4ed8; }
.action-suggest_detail   { background: #dcfce7; color: #15803d; }
.action-generate_insight { background: #fef9c3; color: #a16207; }
.action-detect_anomaly   { background: #fce7f3; color: #9d174d; }
.action-unknown          { background: #f3f4f6; color: #374151; }
.token-chip { background: #f3f0ff; color: #6d28d9; border-radius: 999px; padding: 2px 10px; font-size: .72rem; font-weight: 600; }
.filter-bar { background: #f8f9fa; border-radius: .75rem; padding: .75rem 1rem; }
.table-ai thead th { font-size: .72rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
.table-ai tbody td { vertical-align: middle; font-size: .82rem; }
.success-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.config-key { font-size: .7rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; }
.config-val { font-size: .88rem; font-weight: 600; color: #111827; word-break: break-all; }

/* OCR status badges */
.ocr-badge { font-size: .68rem; padding: 3px 9px; border-radius: 999px; font-weight: 600; }
.ocr-success    { background: #dcfce7; color: #15803d; }
.ocr-failed     { background: #fee2e2; color: #b91c1c; }
.ocr-processing { background: #fef9c3; color: #92400e; }
.ocr-pending    { background: #f3f4f6; color: #374151; }

/* Section divider */
.section-divider {
    display: flex; align-items: center; gap: 1rem; margin: 2rem 0 1.25rem;
}
.section-divider hr { flex: 1; margin: 0; border-color: #e5e7eb; }
.section-divider span {
    font-size: .72rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #9ca3af; white-space: nowrap;
}

/* Nav tabs override */
.monitor-tabs .nav-link { font-size: .82rem; font-weight: 600; color: #6b7280; border: none; border-bottom: 2px solid transparent; border-radius: 0; padding: .65rem 1.1rem; }
.monitor-tabs .nav-link.active { color: #7c3aed; border-bottom-color: #7c3aed; background: none; }
.monitor-tabs .nav-link:hover:not(.active) { color: #374151; border-bottom-color: #d1d5db; }
</style>
@endpush

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4 mt-2 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <span style="width:34px;height:34px;background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;">
                <i class="bi bi-robot text-white" style="font-size:.9rem;"></i>
            </span>
            AI & OCR Monitor
        </h5>
        <p class="text-muted small mb-0 ms-1">Monitoring penggunaan AI & riwayat OCR struk</p>
    </div>
    <div class="d-flex gap-1">
        @foreach([7 => '7H', 14 => '14H', 30 => '30H', 90 => '90H'] as $d => $label)
            <a href="{{ request()->fullUrlWithQuery(['days' => $d]) }}"
               class="btn btn-sm {{ $period == $d ? 'btn-primary' : 'btn-outline-secondary' }}"
               style="min-width:44px;">{{ $label }}</a>
        @endforeach
    </div>
</div>

{{-- ── TABS ─────────────────────────────────────────────────────────── --}}
<ul class="nav monitor-tabs border-bottom mb-4" id="monitorTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAi" type="button">
            <i class="bi bi-lightning-charge me-1"></i>AI API Monitor
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabOcr" type="button">
            <i class="bi bi-receipt me-1"></i>OCR History
            <span class="ms-1 badge rounded-pill" style="background:#7c3aed;font-size:.62rem;">{{ number_format($ocrStats['period_total']) }}</span>
        </button>
    </li>
</ul>

<div class="tab-content">

{{-- ════════════════════════════════════════════════════════════════════
     TAB 1 — AI API MONITOR
═════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade show active" id="tabAi">

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <div class="stat-icon" style="background:#ede9fe;">
                        <i class="bi bi-calendar-day" style="color:#7c3aed;font-size:1.1rem;"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-bold fs-5 lh-1">{{ number_format($stats['todayTotal']) }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Call hari ini</div>
                    </div>
                </div>
                @php $pct = $stats['dailyLimit'] > 0 ? min(100, round($stats['todayTotal']/$stats['dailyLimit']*100)) : 0; @endphp
                <div class="progress progress-thin">
                    <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-primary') }}" style="width:{{ $pct }}%"></div>
                </div>
                <div class="text-muted mt-1" style="font-size:.65rem;">{{ $stats['todayTotal'] }} / {{ number_format($stats['dailyLimit']) }} limit</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <div class="stat-icon" style="background:#dcfce7;">
                        <i class="bi bi-check-circle" style="color:#16a34a;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ $stats['successRate'] }}%</div>
                        <div class="text-muted" style="font-size:.68rem;">Success rate hari ini</div>
                    </div>
                </div>
                <div class="d-flex gap-2" style="font-size:.7rem;">
                    <span class="text-success fw-semibold"><i class="bi bi-check"></i> {{ $stats['todaySuccess'] }}</span>
                    <span class="text-danger fw-semibold"><i class="bi bi-x"></i> {{ $stats['todayError'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#fef3c7;">
                        <i class="bi bi-calendar-month" style="color:#d97706;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ number_format($stats['monthTotal']) }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Call bulan ini</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#e0f2fe;">
                        <i class="bi bi-infinity" style="color:#0284c7;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ number_format($stats['allTime']) }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Total all-time</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#fce7f3;">
                        <i class="bi bi-lightning-charge" style="color:#db2777;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ $tokenStats?->total_tokens ? number_format($tokenStats->total_tokens) : '—' }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Token ({{ $period }}h)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#f3f0ff;">
                        <i class="bi bi-graph-up" style="color:#7c3aed;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ $tokenStats?->avg_tokens ? number_format($tokenStats->avg_tokens) : '—' }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Avg token/call</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card stat-card h-100">
                <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">Calls per Hari <span class="text-muted fw-normal" style="font-size:.8rem;">({{ $period }} hari terakhir)</span></h6>
                        <div class="d-flex gap-3" style="font-size:.72rem;">
                            <span><span class="success-dot" style="background:#7c3aed;"></span> Total</span>
                            <span><span class="success-dot" style="background:#10b981;"></span> Sukses</span>
                            <span><span class="success-dot" style="background:#ef4444;"></span> Error</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3"><canvas id="chartCalls" height="80"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
                    <h6 class="fw-semibold mb-0">Breakdown per Fitur</h6>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center p-3 gap-3">
                    @if($actionBreakdown->isEmpty())
                        <p class="text-muted small mb-0">Belum ada data.</p>
                    @else
                        <canvas id="chartAction" style="max-height:180px;"></canvas>
                        <div class="w-100 d-flex flex-column gap-1 mt-1">
                            @php $actionColors = ['#7c3aed','#10b981','#f59e0b','#ef4444','#3b82f6']; $ci = 0; @endphp
                            @foreach($actionBreakdown as $act => $cnt)
                                @php $total = $actionBreakdown->sum(); @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <span class="success-dot flex-shrink-0" style="background:{{ $actionColors[$ci % count($actionColors)] }};"></span>
                                    <span class="flex-grow-1 text-muted" style="font-size:.75rem;">{{ $actionLabels[$act] ?? $act }}</span>
                                    <span class="fw-semibold" style="font-size:.78rem;">{{ number_format($cnt) }}</span>
                                    <span class="text-muted" style="font-size:.7rem;">({{ $total > 0 ? round($cnt/$total*100) : 0 }}%)</span>
                                </div>
                                @php $ci++ @endphp
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top Users + Config --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card stat-card h-100">
                <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
                    <h6 class="fw-semibold mb-0">Top Pengguna AI <span class="text-muted fw-normal" style="font-size:.8rem;">({{ $period }}h)</span></h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 table-ai">
                        <thead class="table-light">
                            <tr><th>#</th><th>User</th><th>Total Call</th><th>Sukses</th><th>Rate</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topUsers as $i => $row)
                                @php $rate = $row->count > 0 ? round($row->success_count / $row->count * 100) : 0; @endphp
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $row->user?->name ?? 'Unknown' }}</div>
                                        <div class="text-muted" style="font-size:.7rem;">{{ $row->user?->email }}</div>
                                    </td>
                                    <td><span class="fw-semibold">{{ number_format($row->count) }}</span></td>
                                    <td class="text-success">{{ number_format($row->success_count) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress progress-thin flex-grow-1" style="min-width:50px;">
                                                <div class="progress-bar bg-success" style="width:{{ $rate }}%"></div>
                                            </div>
                                            <span style="font-size:.72rem;width:34px;text-align:right;">{{ $rate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4 small">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5 d-flex flex-column gap-3">
            <div class="card stat-card">
                <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-gear me-2 text-muted"></i>Konfigurasi Gemini</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="config-key">Model</div>
                            <div class="config-val">{{ $geminiConfig['gemini_model'] ?? env('GEMINI_MODEL', 'gemini-2.0-flash') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="config-key">Daily Limit</div>
                            <div class="config-val">{{ number_format($geminiConfig['ocr_daily_limit'] ?? 500) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="config-key">Used Today</div>
                            @php $usedToday = (int)($geminiConfig['gemini_ocr_used_today'] ?? 0); $lim = (int)($geminiConfig['ocr_daily_limit'] ?? 500); @endphp
                            <div class="config-val {{ $lim > 0 && $usedToday >= $lim * 0.9 ? 'text-danger' : '' }}">{{ number_format($usedToday) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="config-key">Reset Date</div>
                            <div class="config-val" style="font-size:.8rem;">{{ $geminiConfig['gemini_reset_date'] ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="config-key">API Key</div>
                            @php
                                $rawKey = $geminiConfig['gemini_api_key'] ?? env('GEMINI_API_KEY', '');
                                $maskedKey = $rawKey ? substr($rawKey, 0, 6) . str_repeat('•', max(0, strlen($rawKey) - 10)) . substr($rawKey, -4) : '(tidak dikonfigurasi)';
                            @endphp
                            <div class="config-val" style="font-size:.78rem;font-family:monospace;">{{ $maskedKey }}</div>
                        </div>
                        <div class="col-12">
                            <div class="config-key">Base URL</div>
                            <div class="config-val" style="font-size:.72rem;">{{ $geminiConfig['gemini_base_url'] ?? env('GEMINI_BASE_URL', '—') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @if($topHouseholds->isNotEmpty())
            <div class="card stat-card flex-grow-1">
                <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
                    <h6 class="fw-semibold mb-0">Top Household AI</h6>
                </div>
                <div class="card-body p-3">
                    @foreach($topHouseholds as $row)
                        <div class="d-flex align-items-center gap-2 py-1">
                            <i class="bi bi-house text-muted" style="font-size:.8rem;"></i>
                            <span class="flex-grow-1 text-truncate small">{{ $row->household?->nama ?? 'Unknown' }}</span>
                            <span class="token-chip">{{ number_format($row->count) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- AI API Log Table --}}
    <div class="card stat-card">
        <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="fw-semibold mb-0">Log API Terbaru</h6>
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center filter-bar">
                    <input type="hidden" name="days" value="{{ $period }}">
                    <input type="hidden" name="tab" value="api">
                    <select name="action" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Semua Fitur</option>
                        @foreach($actionLabels as $key => $label)
                            <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="success" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('success') === '1' ? 'selected' : '' }}>Sukses</option>
                        <option value="0" {{ request('success') === '0' ? 'selected' : '' }}>Error</option>
                    </select>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" style="width:130px;">
                    <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="form-control form-control-sm" style="width:130px;">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                    @if(request()->hasAny(['action','success','date_from','date_to']))
                        <a href="{{ route('superadmin.ai-monitor') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 table-ai">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th><th>User</th><th>Household</th>
                        <th>Fitur</th><th>Model</th><th>Status</th><th>Tokens</th><th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-muted" style="white-space:nowrap;">{{ $log->created_at->format('d/m/y H:i:s') }}</td>
                            <td>
                                @if($log->user)
                                    <div class="fw-medium" style="white-space:nowrap;">{{ $log->user->name }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $log->household?->nama ?? '—' }}</td>
                            <td><span class="action-badge action-{{ $log->action }}">{{ $actionLabels[$log->action] ?? $log->action }}</span></td>
                            <td class="text-muted" style="font-size:.72rem;white-space:nowrap;">{{ $log->model ?? '—' }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="success-dot" style="background:{{ $log->success ? '#10b981' : '#ef4444' }};"></span>
                                    <span class="{{ $log->success ? 'text-success' : 'text-danger' }}" style="font-size:.75rem;">{{ $log->status_code ?? '—' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($log->total_tokens)
                                    <span class="token-chip">{{ number_format($log->total_tokens) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:.72rem;">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                Belum ada log AI tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white border-top px-4 py-3" style="border-radius:0 0 .75rem .75rem;">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>{{-- end #tabAi --}}


{{-- ════════════════════════════════════════════════════════════════════
     TAB 2 — OCR HISTORY
═════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tabOcr">

    {{-- OCR Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#dbeafe;">
                        <i class="bi bi-receipt" style="color:#1d4ed8;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ number_format($ocrStats['today_total']) }}</div>
                        <div class="text-muted" style="font-size:.68rem;">OCR hari ini</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <div class="stat-icon" style="background:#dcfce7;">
                        <i class="bi bi-check2-circle" style="color:#16a34a;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ $ocrStats['success_rate'] }}%</div>
                        <div class="text-muted" style="font-size:.68rem;">Sukses ({{ $period }}h)</div>
                    </div>
                </div>
                <div class="d-flex gap-2" style="font-size:.7rem;">
                    <span class="text-success fw-semibold">✓ {{ $ocrStats['period_success'] }}</span>
                    <span class="text-danger fw-semibold">✗ {{ $ocrStats['period_total'] - $ocrStats['period_success'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#fef3c7;">
                        <i class="bi bi-calendar-month" style="color:#d97706;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ number_format($ocrStats['month_total']) }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Bulan ini</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#e0f2fe;">
                        <i class="bi bi-infinity" style="color:#0284c7;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ number_format($ocrStats['all_time']) }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Total all-time</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#f0fdf4;">
                        <i class="bi bi-arrow-right-circle" style="color:#16a34a;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ number_format($ocrStats['converted']) }}</div>
                        <div class="text-muted" style="font-size:.68rem;">Jadi transaksi</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-start gap-2">
                    <div class="stat-icon" style="background:#fef9c3;">
                        <i class="bi bi-percent" style="color:#ca8a04;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">{{ $ocrStats['conversion_rate'] }}%</div>
                        <div class="text-muted" style="font-size:.68rem;">Conversion rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- OCR Charts --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card stat-card h-100">
                <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">OCR per Hari <span class="text-muted fw-normal" style="font-size:.8rem;">({{ $period }} hari terakhir)</span></h6>
                        <div class="d-flex gap-3" style="font-size:.72rem;">
                            <span><span class="success-dot" style="background:#1d4ed8;"></span> Total</span>
                            <span><span class="success-dot" style="background:#10b981;"></span> Sukses</span>
                            <span><span class="success-dot" style="background:#ef4444;"></span> Gagal</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3"><canvas id="chartOcr" height="80"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
                    <h6 class="fw-semibold mb-0">Top Merchant ({{ $period }}h)</h6>
                </div>
                <div class="card-body p-3">
                    @forelse($ocrTopMerchants as $row)
                        <div class="d-flex align-items-center gap-2 py-1 border-bottom" style="border-color:#f3f4f6!important;">
                            <i class="bi bi-shop text-muted" style="font-size:.8rem;flex-shrink:0;"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="text-truncate fw-medium" style="font-size:.8rem;">{{ $row->detected_merchant }}</div>
                                @if($row->total_amount)
                                    <div class="text-muted" style="font-size:.68rem;">Rp {{ number_format($row->total_amount) }}</div>
                                @endif
                            </div>
                            <span class="token-chip flex-shrink-0">{{ $row->count }}×</span>
                        </div>
                    @empty
                        <p class="text-muted small text-center py-3 mb-0">Belum ada data merchant.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- OCR History Table --}}
    <div class="card stat-card">
        <div class="card-header bg-white border-bottom px-4 py-3" style="border-radius:.75rem .75rem 0 0;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="fw-semibold mb-0">Riwayat OCR Struk</h6>
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center filter-bar">
                    <input type="hidden" name="days" value="{{ $period }}">
                    <input type="hidden" name="tab" value="ocr">
                    <select name="ocr_status" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Semua Status</option>
                        <option value="success"    {{ request('ocr_status') === 'success'    ? 'selected' : '' }}>Sukses</option>
                        <option value="failed"     {{ request('ocr_status') === 'failed'     ? 'selected' : '' }}>Gagal</option>
                        <option value="processing" {{ request('ocr_status') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="pending"    {{ request('ocr_status') === 'pending'    ? 'selected' : '' }}>Pending</option>
                    </select>
                    <select name="ocr_household" class="form-select form-select-sm" style="width:auto;max-width:180px;">
                        <option value="">Semua Household</option>
                        @foreach($allHouseholds as $h)
                            <option value="{{ $h->id }}" {{ request('ocr_household') == $h->id ? 'selected' : '' }}>{{ $h->nama }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="ocr_merchant" value="{{ request('ocr_merchant') }}"
                           placeholder="Cari merchant..." class="form-control form-control-sm" style="width:140px;">
                    <input type="date" name="ocr_date_from" value="{{ request('ocr_date_from') }}" class="form-control form-control-sm" style="width:130px;">
                    <input type="date" name="ocr_date_to"   value="{{ request('ocr_date_to') }}"   class="form-control form-control-sm" style="width:130px;">
                    <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                    @if(request()->hasAny(['ocr_status','ocr_household','ocr_merchant','ocr_date_from','ocr_date_to']))
                        <a href="{{ route('superadmin.ai-monitor', ['days' => $period, 'tab' => 'ocr']) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 table-ai">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Household</th>
                        <th>Status</th>
                        <th>Merchant</th>
                        <th>Jumlah</th>
                        <th>Tgl Struk</th>
                        <th>Transaksi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ocrHistory as $ocr)
                        <tr>
                            <td class="text-muted" style="white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($ocr->created_at)->format('d/m/y H:i') }}
                            </td>
                            <td>
                                @if($ocr->user_name)
                                    <div class="fw-medium" style="white-space:nowrap;">{{ $ocr->user_name }}</div>
                                    <div class="text-muted" style="font-size:.68rem;">{{ $ocr->user_email }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $ocr->household_nama ?? '—' }}</td>
                            <td>
                                <span class="ocr-badge ocr-{{ $ocr->status }}">
                                    @switch($ocr->status)
                                        @case('success')    <i class="bi bi-check me-1"></i>Sukses @break
                                        @case('failed')     <i class="bi bi-x me-1"></i>Gagal @break
                                        @case('processing') <i class="bi bi-arrow-repeat me-1"></i>Processing @break
                                        @default            <i class="bi bi-clock me-1"></i>Pending
                                    @endswitch
                                </span>
                            </td>
                            <td>
                                @if($ocr->detected_merchant)
                                    <span class="fw-medium">{{ $ocr->detected_merchant }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($ocr->detected_amount)
                                    <span class="fw-semibold text-dark">Rp {{ number_format($ocr->detected_amount) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted" style="white-space:nowrap;">
                                {{ $ocr->detected_date ?? '—' }}
                            </td>
                            <td>
                                @if($ocr->transaksi_id)
                                    <span class="badge bg-success-subtle text-success" style="font-size:.68rem;">
                                        <i class="bi bi-check2"></i> #{{ $ocr->transaksi_id }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:.75rem;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($ocr->status === 'success' && $ocr->ocr_result)
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2"
                                            style="font-size:.7rem;"
                                            onclick="showOcrDetail({{ $ocr->id }}, {{ json_encode($ocr->ocr_result) }})">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                @elseif($ocr->status === 'failed' && $ocr->error_message)
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2"
                                            style="font-size:.7rem;"
                                            onclick="showOcrError({{ json_encode($ocr->error_message) }})">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25"></i>
                                Belum ada riwayat OCR.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ocrHistory->hasPages())
            <div class="card-footer bg-white border-top px-4 py-3" style="border-radius:0 0 .75rem .75rem;">
                {{ $ocrHistory->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>{{-- end #tabOcr --}}

</div>{{-- end .tab-content --}}

{{-- ── Modal: OCR Detail ───────────────────────────────────────────── --}}
<div class="modal fade" id="modalOcrDetail" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">
                    <i class="bi bi-receipt me-2 text-primary"></i>
                    Hasil OCR Struk <span id="ocrDetailId" class="text-muted fw-normal" style="font-size:.8rem;"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Info cards --}}
                <div class="d-flex flex-wrap gap-3 p-4 bg-light border-bottom" id="ocrDetailCards"></div>
                {{-- Items table --}}
                <div id="ocrItemsSection" class="px-4 pt-3 pb-2" style="display:none;">
                    <div class="fw-semibold small mb-2">Detail Item</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="ocrItemsTable" style="font-size:.8rem;">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Qty</th><th>Harga Satuan</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                {{-- Raw JSON --}}
                <details class="px-4 py-3">
                    <summary class="text-muted small" style="cursor:pointer;">Lihat JSON mentah</summary>
                    <pre id="ocrDetailJson" class="mt-2 p-3 rounded" style="background:#f8f9fa;font-size:.72rem;max-height:300px;overflow:auto;"></pre>
                </details>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: OCR Error ────────────────────────────────────────────── --}}
<div class="modal fade" id="modalOcrError" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Pesan Error OCR
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="ocrErrorMsg" class="p-3 rounded text-danger" style="background:#fff5f5;font-size:.8rem;white-space:pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    // ── AI Calls chart ────────────────────────────────────────────────
    new Chart(document.getElementById('chartCalls'), {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                { label: 'Total',  data: @json($chartTotal),   borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.08)', tension: 0.4, fill: true,  pointRadius: 3, borderWidth: 2 },
                { label: 'Sukses', data: @json($chartSuccess), borderColor: '#10b981', backgroundColor: 'transparent',            tension: 0.4, fill: false, pointRadius: 3, borderWidth: 2 },
                { label: 'Error',  data: @json($chartError),   borderColor: '#ef4444', backgroundColor: 'transparent',            tension: 0.4, fill: false, pointRadius: 3, borderWidth: 2 },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,.04)' } },
                x: { ticks: { font: { size: 10 }, maxRotation: 0 }, grid: { display: false } },
            },
        },
    });

    // ── Action doughnut ───────────────────────────────────────────────
    const actionCanvas = document.getElementById('chartAction');
    if (actionCanvas) {
        new Chart(actionCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($actionBreakdown->keys()->map(fn($k) => $actionLabels[$k] ?? $k)),
                datasets: [{ data: @json($actionBreakdown->values()), backgroundColor: ['#7c3aed','#10b981','#f59e0b','#ef4444','#3b82f6'], borderWidth: 2, borderColor: '#fff' }],
            },
            options: { responsive: true, cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed } } } },
        });
    }

    // ── OCR chart ─────────────────────────────────────────────────────
    new Chart(document.getElementById('chartOcr'), {
        type: 'bar',
        data: {
            labels: @json($ocrChartLabels),
            datasets: [
                { label: 'Sukses', data: @json($ocrChartSuccess), backgroundColor: 'rgba(16,185,129,.75)',  borderRadius: 3, stack: 'ocr' },
                { label: 'Gagal',  data: @json($ocrChartFailed),  backgroundColor: 'rgba(239,68,68,.75)',   borderRadius: 3, stack: 'ocr' },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                y: { beginAtZero: true, stacked: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,.04)' } },
                x: { stacked: true, ticks: { font: { size: 10 }, maxRotation: 0 }, grid: { display: false } },
            },
        },
    });

    // ── Tab persistence via URL param ─────────────────────────────────
    const tabParam = new URLSearchParams(window.location.search).get('tab');
    if (tabParam === 'ocr') {
        const ocrTab = document.querySelector('[data-bs-target="#tabOcr"]');
        if (ocrTab) bootstrap.Tab.getOrCreateInstance(ocrTab).show();
    }

    // Update URL when switching tabs (without reload)
    document.querySelectorAll('#monitorTabs .nav-link').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            const target = this.dataset.bsTarget === '#tabOcr' ? 'ocr' : 'api';
            const url = new URL(window.location);
            url.searchParams.set('tab', target);
            history.replaceState(null, '', url);
        });
    });
}());

// ── OCR Detail modal ──────────────────────────────────────────────────
function showOcrDetail(id, rawJson) {
    let data;
    try { data = typeof rawJson === 'string' ? JSON.parse(rawJson) : rawJson; } catch(e) { data = {}; }

    document.getElementById('ocrDetailId').textContent = '#' + id;
    document.getElementById('ocrDetailJson').textContent = JSON.stringify(data, null, 2);

    // Info cards
    const cards = document.getElementById('ocrDetailCards');
    const fields = [
        { key: 'nama_toko',      label: 'Merchant' },
        { key: 'tipe_toko',      label: 'Tipe Toko' },
        { key: 'tipe_transaksi', label: 'Tipe' },
        { key: 'tanggal',        label: 'Tanggal' },
        { key: 'total',          label: 'Total', format: v => 'Rp ' + Number(v).toLocaleString('id-ID') },
        { key: 'metode_bayar',   label: 'Metode Bayar' },
        { key: 'catatan',        label: 'Catatan' },
    ];
    cards.innerHTML = fields.map(f => {
        const val = data[f.key];
        if (!val && val !== 0) return '';
        const display = f.format ? f.format(val) : val;
        return `<div class="px-3 py-2 bg-white rounded border" style="min-width:120px;">
            <div style="font-size:.65rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;">${f.label}</div>
            <div style="font-size:.85rem;font-weight:600;color:#111;">${display}</div>
        </div>`;
    }).join('');

    // Items table
    const items = data.items;
    const itemsSection = document.getElementById('ocrItemsSection');
    const tbody = document.querySelector('#ocrItemsTable tbody');
    if (Array.isArray(items) && items.length > 0) {
        tbody.innerHTML = items.map(item => `
            <tr>
                <td>${item.nama_item ?? '—'}</td>
                <td>${item.qty ?? '—'}</td>
                <td>${item.harga_satuan ? 'Rp ' + Number(item.harga_satuan).toLocaleString('id-ID') : '—'}</td>
                <td>${item.subtotal ? 'Rp ' + Number(item.subtotal).toLocaleString('id-ID') : '—'}</td>
            </tr>`).join('');
        itemsSection.style.display = '';
    } else {
        itemsSection.style.display = 'none';
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOcrDetail')).show();
}

function showOcrError(msg) {
    document.getElementById('ocrErrorMsg').textContent = msg;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOcrError')).show();
}
</script>
@endpush
