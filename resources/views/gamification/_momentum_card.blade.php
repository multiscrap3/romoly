@php
    $score = (float) $gamification->momentum_score;

    $barColor = match(true) {
        $score >= 90 => 'bg-success',
        $score >= 70 => 'bg-primary',
        $score >= 40 => 'bg-warning',
        default      => 'bg-danger',
    };
    $accent = match(true) {
        $score >= 90 => '#10b981',
        $score >= 70 => '#435ebe',
        $score >= 40 => '#f59e0b',
        default      => '#ef4444',
    };
    $glowClass = match($momentumStatus) {
        'Strong Momentum' => 'momentum-strong',
        'Stable'          => 'momentum-stable',
        'Weakening'       => 'momentum-weakening',
        default           => 'momentum-lost',
    };
@endphp

<div class="card momentum-card {{ $glowClass }}">
    <div class="card-body">
        <div class="row align-items-center g-4">
            {{-- Score + status --}}
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="momentum-flame" style="background:{{ $accent }}1a;color:{{ $accent }};">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Momentum</div>
                        <div class="fw-bold fs-5" style="color:{{ $accent }};">{{ $momentumStatus }}</div>
                    </div>
                    <div class="ms-auto text-end">
                        <span class="fw-bold" style="font-size:2rem;line-height:1;">{{ number_format($score, 0) }}</span>
                        <span class="text-muted">/100</span>
                    </div>
                </div>
                <div class="progress mb-2" style="height:8px;border-radius:6px;">
                    <div class="progress-bar {{ $barColor }}" style="width:0" data-target="{{ $score }}"></div>
                </div>
                <p class="small text-muted mb-0">
                    @if($score >= 70)
                        Momentum kamu bagus. Pertahankan dengan mencatat transaksi setiap hari.
                    @elseif($score >= 40)
                        Momentum mulai melemah. Yuk catat satu transaksi hari ini agar tetap di jalur.
                    @else
                        Saatnya kembali ke jalur — satu transaksi hari ini sudah cukup untuk memulai.
                    @endif
                </p>
            </div>

            {{-- Cara naik momentum --}}
            <div class="col-md-5">
                <div class="momentum-hints">
                    <div class="text-muted small fw-semibold mb-2">Cara menaikkan momentum</div>
                    <div class="d-flex flex-column gap-2">
                        <div class="momentum-hint"><i class="bi bi-pencil-square"></i> Catat transaksi harian <span class="ms-auto fw-semibold text-success">+2</span></div>
                        <div class="momentum-hint"><i class="bi bi-cash-stack"></i> Aktivitas menabung <span class="ms-auto fw-semibold text-success">+5</span></div>
                        <div class="momentum-hint"><i class="bi bi-bar-chart-line"></i> Buka weekly review <span class="ms-auto fw-semibold text-success">+5</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.momentum-card { border:1px solid #eef0f4; border-radius:.85rem; }
.momentum-flame { width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
.momentum-hints { background:#f8f9fb;border-radius:.7rem;padding:.85rem 1rem; }
.momentum-hint { display:flex;align-items:center;gap:.55rem;font-size:.82rem;color:#495057; }
.momentum-hint i { color:#435ebe; }
.momentum-card.momentum-strong { box-shadow:0 0 0 2px rgba(16,185,129,.25),0 6px 18px rgba(16,185,129,.08)!important; }
.momentum-card.momentum-weakening { box-shadow:0 0 0 2px rgba(245,158,11,.22),0 6px 14px rgba(245,158,11,.08)!important; }
.momentum-card.momentum-lost { box-shadow:0 0 0 2px rgba(239,68,68,.2),0 6px 14px rgba(239,68,68,.08)!important; }
</style>
