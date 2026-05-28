<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="text-muted small mb-1">Momentum</div>
                <div class="fw-semibold fs-5">{{ $momentumStatus }}</div>
            </div>
            <span class="fw-bold fs-3">{{ number_format($gamification->momentum_score, 0) }}</span>
        </div>

        @php
            $score = (float) $gamification->momentum_score;
            $barColor = match(true) {
                $score >= 90 => 'bg-success',
                $score >= 70 => 'bg-primary',
                $score >= 40 => 'bg-warning',
                default      => 'bg-danger',
            };
        @endphp

        <div class="progress mb-3" style="height: 8px;">
            <div class="progress-bar {{ $barColor }}" style="width: {{ $score }}%"></div>
        </div>

        <p class="small text-muted mb-0">
            @if($score >= 70)
                Momentum kamu bagus! Pertahankan dengan mencatat transaksi setiap hari.
            @elseif($score >= 40)
                Momentum mulai melemah. Yuk catat transaksi hari ini!
            @else
                Saatnya kembali ke jalur. Satu transaksi hari ini sudah cukup.
            @endif
        </p>
    </div>
</div>
