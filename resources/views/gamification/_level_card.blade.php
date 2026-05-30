@php
    $circ = 251.33; // 2 * pi * 40
    $fill = round($circ * min(100, (float) $progressPercent) / 100, 2);
@endphp

<div class="card h-100">
    <div class="card-body d-flex align-items-center gap-3">

        {{-- SVG Progress Ring --}}
        <div class="position-relative flex-shrink-0" style="width:100px;height:100px;">
            <svg width="100" height="100" viewBox="0 0 100 100" aria-label="XP Progress {{ $progressPercent }}%" role="img">
                <circle cx="50" cy="50" r="40"
                    fill="none" stroke="#e9ecef" stroke-width="6"/>
                <circle cx="50" cy="50" r="40"
                    fill="none" stroke="#435ebe" stroke-width="6"
                    stroke-linecap="round"
                    stroke-dasharray="0 251.33"
                    data-fill="{{ $fill }}"
                    transform="rotate(-90 50 50)"
                    class="xp-ring-fill"
                    aria-hidden="true"/>
            </svg>
            <div class="position-absolute top-50 start-50 translate-middle text-center" style="pointer-events:none;">
                <div class="fw-bold lh-1" style="font-size:.95rem;">Lv {{ $gamification->level }}</div>
                <div class="text-muted lh-1 mt-1" style="font-size:.6rem;">{{ $progressPercent }}%</div>
            </div>
        </div>

        {{-- Info --}}
        <div class="flex-grow-1 min-w-0">
            <div class="text-muted small mb-1">{{ \App\Services\LevelService::title($gamification->level) }}</div>
            <span class="badge bg-secondary">{{ number_format($gamification->total_xp) }} XP</span>

            @if($gamification->level < 10)
            <div class="small text-muted mt-2">
                <span class="fw-semibold text-primary">{{ number_format($xpToNext) }} XP</span>
                lagi ke Level {{ $gamification->level + 1 }}
            </div>
            @else
            <div class="small text-success fw-semibold mt-2">Level maksimum tercapai!</div>
            @endif
        </div>
    </div>
</div>

<style>
.xp-ring-fill {
    transition: stroke-dasharray 800ms cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.xp-ring-fill').forEach(function (el) {
        var fill  = parseFloat(el.getAttribute('data-fill'));
        var total = 251.33;
        setTimeout(function () {
            el.setAttribute('stroke-dasharray', fill + ' ' + total);
        }, 120);
    });
});
</script>
