<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="text-muted small mb-1">Level {{ $gamification->level }}</div>
                <div class="fw-semibold fs-5">{{ \App\Services\LevelService::title($gamification->level) }}</div>
            </div>
            <span class="badge bg-secondary fs-6">{{ number_format($gamification->total_xp) }} XP</span>
        </div>

        @if($gamification->level < 10)
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Menuju Level {{ $gamification->level + 1 }}</span>
            <span>{{ $progressPercent }}%</span>
        </div>
        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar bg-primary" style="width: {{ $progressPercent }}%"></div>
        </div>
        <div class="small text-muted">{{ number_format($xpToNext) }} XP lagi ke level berikutnya</div>
        @else
        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar bg-success" style="width: 100%"></div>
        </div>
        <div class="small text-success fw-semibold">Level maksimum tercapai!</div>
        @endif
    </div>
</div>
