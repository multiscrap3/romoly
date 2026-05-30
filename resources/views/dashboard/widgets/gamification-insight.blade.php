@php
    $s = $gamificationSummary  ?? [];
    $i = $gamificationInsights ?? [];

    $level            = $s['level']             ?? 1;
    $levelTitle       = $s['level_title']        ?? '-';
    $progressPercent  = $s['progress_percent']   ?? 0;
    $xpRemaining      = $s['xp_remaining']       ?? 0;
    $totalXp          = $s['total_xp']           ?? 0;
    $momentumScore    = $s['momentum_score']      ?? 50;
    $momentumLabel    = $s['momentum_label']      ?? 'Stable';
    $momentumColor    = $s['momentum_color']      ?? 'primary';
    $earnedCount      = $s['earned_count']        ?? 0;
    $activeChallenges = $s['active_challenges']   ?? 0;

    $mBarColor = match($momentumColor) {
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'danger'  => '#ef4444',
        default   => '#435ebe',
    };
@endphp

<div class="card h-100 border-0 shadow-sm" style="border-radius:.75rem;">
    <div class="card-body p-3 d-flex flex-column">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                     style="width:28px;height:28px;background:rgba(67,94,190,.12);">
                    <i class="bi bi-trophy text-primary" style="font-size:.8rem;"></i>
                </div>
                <span class="fw-semibold text-dark" style="font-size:.76rem;">Progres Finansial</span>
            </div>
            <a href="{{ route('gamifikasi.index') }}" class="text-muted" style="font-size:.75rem;">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        {{-- 2×2 Grid --}}
        <div class="row g-2 flex-grow-1">

            {{-- Kiri atas: Level & XP --}}
            <div class="col-6">
                <div class="rounded-2 p-2 h-100 d-flex flex-column justify-content-center"
                     style="background:rgba(67,94,190,.06);">
                    <div class="fw-semibold text-dark lh-1 mb-1" style="font-size:.7rem;">
                        Lv {{ $level }}
                        <span class="text-muted fw-normal" style="font-size:.65rem;">· {{ $levelTitle }}</span>
                    </div>
                    <div class="progress mb-1" style="height:4px;border-radius:2px;background:#e9ecef;">
                        <div class="progress-bar bg-primary"
                             style="width:{{ $progressPercent }}%;border-radius:2px;transition:width 600ms ease;"></div>
                    </div>
                    @if($level < 10)
                    <div class="text-muted lh-1" style="font-size:.6rem;">
                        {{ number_format($totalXp) }} XP ·
                        <span class="text-primary fw-medium">{{ number_format($xpRemaining) }} lagi</span>
                    </div>
                    @else
                    <div class="text-success fw-semibold lh-1" style="font-size:.6rem;">Max level!</div>
                    @endif
                </div>
            </div>

            {{-- Kanan atas: Momentum --}}
            <div class="col-6">
                <div class="rounded-2 p-2 h-100 d-flex flex-column justify-content-center"
                     style="background:rgba(245,158,11,.06);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold text-dark lh-1" style="font-size:.7rem;">Momentum</span>
                    </div>
                    <div class="progress mb-1" style="height:4px;border-radius:2px;background:#e9ecef;">
                        <div class="progress-bar"
                             style="width:{{ $momentumScore }}%;background:{{ $mBarColor }};border-radius:2px;transition:width 600ms ease;"></div>
                    </div>
                    <span class="badge rounded-pill bg-{{ $momentumColor }} bg-opacity-10 text-{{ $momentumColor }}"
                          style="font-size:.58rem;font-weight:500;width:fit-content;">
                        {{ $momentumLabel }} · {{ $momentumScore }}
                    </span>
                </div>
            </div>

            {{-- Kiri bawah: Stats --}}
            <div class="col-6">
                <div class="rounded-2 p-2 h-100 d-flex align-items-center justify-content-between"
                     style="background:rgba(0,0,0,.025);">
                    <div class="text-center">
                        <div class="fw-bold text-dark lh-1 mb-1" style="font-size:.85rem;">{{ $earnedCount }}</div>
                        <div class="text-muted" style="font-size:.58rem;">Achievement</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-dark lh-1 mb-1" style="font-size:.85rem;">{{ $activeChallenges }}</div>
                        <div class="text-muted" style="font-size:.58rem;">Tantangan</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-dark lh-1 mb-1" style="font-size:.85rem;">{{ number_format($totalXp) }}</div>
                        <div class="text-muted" style="font-size:.58rem;">Total XP</div>
                    </div>
                </div>
            </div>

            {{-- Kanan bawah: Insight / status --}}
            <div class="col-6">
                <div class="rounded-2 p-2 h-100 d-flex flex-column justify-content-center"
                     style="background:rgba(0,0,0,.025);">
                    @if(!empty($i))
                        @foreach(array_slice($i, 0, 2) as $insight)
                        <div class="d-flex align-items-start gap-1 {{ !$loop->last ? 'mb-1' : '' }}">
                            <i class="bi {{ $insight['icon'] }} text-{{ $insight['color'] }} flex-shrink-0 mt-1"
                               style="font-size:.62rem;"></i>
                            <span class="text-dark lh-sm" style="font-size:.62rem;">
                                {{ Str::limit($insight['text'], 38) }}
                            </span>
                        </div>
                        @endforeach
                    @else
                    <div class="d-flex align-items-center gap-1">
                        <i class="bi bi-check-circle-fill text-success flex-shrink-0" style="font-size:.72rem;"></i>
                        <span class="text-dark" style="font-size:.65rem;">Semua dalam jalur!</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
