@php
    $score = (float) $gamification->momentum_score;

    $barColor = match(true) {
        $score >= 90 => 'bg-success',
        $score >= 70 => 'bg-primary',
        $score >= 40 => 'bg-warning',
        default      => 'bg-danger',
    };

    $glowClass = match($momentumStatus) {
        'Strong Momentum' => 'momentum-strong',
        'Stable'          => 'momentum-stable',
        'Weakening'       => 'momentum-weakening',
        default           => 'momentum-lost',
    };
@endphp

<div class="card h-100 {{ $glowClass }}">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="text-muted small mb-1">Momentum</div>
                <div class="fw-semibold fs-5">{{ $momentumStatus }}</div>
            </div>
            <span class="fw-bold fs-3 lh-1">{{ number_format($score, 0) }}</span>
        </div>

        <div class="progress mb-3" style="height: 6px; border-radius:4px;">
            <div class="progress-bar {{ $barColor }}" style="width: {{ $score }}%; transition: width 600ms cubic-bezier(0.4,0,0.2,1);"></div>
        </div>

        <p class="small text-muted mb-0">
            @if($score >= 70)
                Momentum kamu bagus. Pertahankan dengan mencatat transaksi setiap hari.
            @elseif($score >= 40)
                Momentum mulai melemah. Yuk catat satu transaksi hari ini.
            @else
                Saatnya kembali ke jalur — satu transaksi hari ini sudah cukup.
            @endif
        </p>
    </div>
</div>

<style>
.momentum-strong {
    box-shadow: 0 0 0 2px rgba(16,185,129,.3), 0 4px 16px rgba(16,185,129,.08) !important;
    animation: glow-pulse-green 3s ease-in-out infinite;
}
.momentum-stable {
    box-shadow: 0 0 0 2px rgba(67,94,190,.2), 0 4px 12px rgba(67,94,190,.06) !important;
}
.momentum-weakening {
    box-shadow: 0 0 0 2px rgba(245,158,11,.25), 0 4px 12px rgba(245,158,11,.08) !important;
}

@keyframes glow-pulse-green {
    0%, 100% { box-shadow: 0 0 0 2px rgba(16,185,129,.3),  0 4px 16px rgba(16,185,129,.08); }
    50%       { box-shadow: 0 0 0 3px rgba(16,185,129,.45), 0 4px 20px rgba(16,185,129,.15); }
}
</style>
