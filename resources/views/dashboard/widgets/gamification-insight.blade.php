@php
    $s = $gamificationSummary  ?? [];
    $i = $gamificationInsights ?? [];
    $m = $gamificationMissions ?? ['missions' => [], 'done_count' => 0, 'total' => 4, 'all_done' => false, 'percent' => 0];

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

    // Tampilkan mode "Misi Pertama" selama user baru belum menuntaskan semua langkah awal.
    $onboarding = !($m['all_done'] ?? false);

    // Geometri ring SVG (r=22 → keliling ≈ 138.23)
    $ringCirc   = 2 * M_PI * 22;
    $ringOffset = $ringCirc * (1 - ($m['percent'] ?? 0) / 100);
@endphp

<div class="card h-100 border-0 shadow-sm pf-widget" style="border-radius:.85rem;overflow:hidden;">
    {{-- Aksen gradient tipis di header --}}
    <div class="pf-accent"></div>

    <div class="card-body p-3 d-flex flex-column">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="pf-badge d-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="lh-1">
                    <div class="fw-semibold pf-title" style="font-size:.8rem;">Progres Finansial</div>
                    <div class="pf-subtitle" style="font-size:.62rem;">Lv {{ $level }} · {{ $levelTitle }}</div>
                </div>
            </div>
            <a href="{{ route('gamifikasi.index') }}" class="pf-more d-flex align-items-center gap-1" style="font-size:.66rem;">
                <span>Detail</span><i class="bi bi-chevron-right"></i>
            </a>
        </div>

        @if($onboarding)
        {{-- ════════════ MODE: MISI PERTAMA (user baru) ════════════ --}}

        <div class="d-flex align-items-center gap-3 mb-3 pf-quest-head">
            <div class="pf-ring flex-shrink-0">
                <svg width="58" height="58" viewBox="0 0 58 58">
                    <circle class="pf-ring-track" cx="29" cy="29" r="22" fill="none" stroke-width="5"/>
                    <circle class="pf-ring-fill" cx="29" cy="29" r="22" fill="none" stroke-width="5"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $ringCirc }}"
                            stroke-dashoffset="{{ $ringOffset }}"
                            transform="rotate(-90 29 29)"/>
                </svg>
                <div class="pf-ring-label">
                    <span class="pf-ring-num">{{ $m['done_count'] }}</span><span class="pf-ring-den">/{{ $m['total'] }}</span>
                </div>
            </div>
            <div class="flex-grow-1 lh-sm">
                <div class="fw-semibold pf-title" style="font-size:.82rem;">Mulai perjalananmu 🚀</div>
                <div class="pf-subtitle" style="font-size:.66rem;">
                    @if($m['done_count'] === 0)
                        Selesaikan misi ini untuk membangun progres &amp; naik level.
                    @elseif($m['done_count'] < $m['total'])
                        Mantap! Tinggal {{ $m['total'] - $m['done_count'] }} langkah lagi.
                    @endif
                </div>
            </div>
        </div>

        {{-- Daftar misi --}}
        <div class="d-flex flex-column gap-2 flex-grow-1">
            @foreach($m['missions'] as $mission)
                @if($mission['done'])
                    <div class="pf-mission pf-mission-done d-flex align-items-center gap-2">
                        <span class="pf-check pf-check-done flex-shrink-0">
                            <i class="bi bi-check2"></i>
                        </span>
                        <span class="flex-grow-1 pf-mission-label" style="font-size:.7rem;">{{ $mission['label'] }}</span>
                        <span class="pf-reward pf-reward-done flex-shrink-0">{{ $mission['reward'] }}</span>
                    </div>
                @else
                    <a href="{{ $mission['link'] }}" class="pf-mission pf-mission-todo d-flex align-items-center gap-2 text-decoration-none">
                        <span class="pf-check flex-shrink-0">
                            <i class="bi {{ $mission['icon'] }}"></i>
                        </span>
                        <span class="flex-grow-1 lh-1">
                            <span class="d-block pf-mission-label fw-medium" style="font-size:.72rem;">{{ $mission['label'] }}</span>
                            <span class="d-block pf-subtitle" style="font-size:.6rem;">{{ $mission['hint'] }}</span>
                        </span>
                        <span class="pf-reward flex-shrink-0">{{ $mission['reward'] }}</span>
                        <i class="bi bi-arrow-right pf-mission-arrow flex-shrink-0"></i>
                    </a>
                @endif
            @endforeach
        </div>

        @else
        {{-- ════════════ MODE: PROGRES (user aktif) ════════════ --}}

        <div class="row g-2 flex-grow-1">

            {{-- Level & XP --}}
            <div class="col-6">
                <div class="pf-tile pf-tile-accent h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-semibold pf-title" style="font-size:.7rem;">Level {{ $level }}</span>
                        <i class="bi bi-stars pf-tile-ico" style="color:#435ebe;"></i>
                    </div>
                    <div class="pf-bar mb-1">
                        <div class="pf-bar-fill" style="width:{{ $progressPercent }}%;background:linear-gradient(90deg,#5b7bd6,#435ebe);"></div>
                    </div>
                    @if($level < 10)
                    <div class="pf-subtitle" style="font-size:.6rem;">
                        {{ number_format($totalXp) }} XP ·
                        <span style="color:#5b7bd6;" class="fw-medium">{{ number_format($xpRemaining) }} lagi</span>
                    </div>
                    @else
                    <div class="fw-semibold lh-1" style="font-size:.6rem;color:#10b981;">⭐ Max level!</div>
                    @endif
                </div>
            </div>

            {{-- Momentum --}}
            <div class="col-6">
                <div class="pf-tile pf-tile-warn h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-semibold pf-title" style="font-size:.7rem;">Momentum</span>
                        <i class="bi bi-lightning-charge-fill pf-tile-ico" style="color:{{ $mBarColor }};"></i>
                    </div>
                    <div class="pf-bar mb-1">
                        <div class="pf-bar-fill" style="width:{{ $momentumScore }}%;background:{{ $mBarColor }};"></div>
                    </div>
                    <span class="pf-pill" style="color:{{ $mBarColor }};background:{{ $mBarColor }}1a;">
                        {{ $momentumLabel }} · {{ $momentumScore }}
                    </span>
                </div>
            </div>

            {{-- Stats --}}
            <div class="col-6">
                <div class="pf-tile h-100 d-flex align-items-center justify-content-around">
                    <div class="text-center">
                        <div class="fw-bold pf-title lh-1 mb-1" style="font-size:.9rem;">{{ $earnedCount }}</div>
                        <div class="pf-subtitle" style="font-size:.56rem;">Achievement</div>
                    </div>
                    <div class="pf-divider"></div>
                    <div class="text-center">
                        <div class="fw-bold pf-title lh-1 mb-1" style="font-size:.9rem;">{{ $activeChallenges }}</div>
                        <div class="pf-subtitle" style="font-size:.56rem;">Tantangan</div>
                    </div>
                    <div class="pf-divider"></div>
                    <div class="text-center">
                        <div class="fw-bold pf-title lh-1 mb-1" style="font-size:.9rem;">{{ number_format($totalXp) }}</div>
                        <div class="pf-subtitle" style="font-size:.56rem;">Total XP</div>
                    </div>
                </div>
            </div>

            {{-- Insight / status --}}
            <div class="col-6">
                <div class="pf-tile h-100 d-flex flex-column justify-content-center">
                    @if(!empty($i))
                        @foreach(array_slice($i, 0, 2) as $insight)
                        <a href="{{ $insight['link'] }}"
                           class="pf-insight d-flex align-items-start gap-2 text-decoration-none {{ !$loop->last ? 'mb-1' : '' }}">
                            <i class="bi {{ $insight['icon'] }} text-{{ $insight['color'] }} flex-shrink-0 mt-1" style="font-size:.62rem;"></i>
                            <span class="pf-title lh-sm" style="font-size:.62rem;">{{ Str::limit($insight['text'], 38) }}</span>
                        </a>
                        @endforeach
                    @else
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill flex-shrink-0" style="font-size:.78rem;color:#10b981;"></i>
                        <span class="pf-title" style="font-size:.66rem;">Semua dalam jalur!</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>
        @endif

    </div>
</div>

<style>
/* ── Progres Finansial widget — theming light/dark via CSS vars ── */
.pf-widget {
    --pf-surface:    rgba(15,23,42,.035);
    --pf-surface-2:  rgba(15,23,42,.06);
    --pf-text:       #1f2937;
    --pf-muted:      #6b7280;
    --pf-border:     rgba(15,23,42,.07);
    --pf-track:      rgba(15,23,42,.09);
    position: relative;
}
[data-theme-version="dark"] .pf-widget {
    --pf-surface:    rgba(255,255,255,.04);
    --pf-surface-2:  rgba(255,255,255,.07);
    --pf-text:       #e8eaf2;
    --pf-muted:      #9aa0b5;
    --pf-border:     rgba(255,255,255,.08);
    --pf-track:      rgba(255,255,255,.10);
}

.pf-title    { color: var(--pf-text); }
.pf-subtitle { color: var(--pf-muted); }

/* Aksen gradient di tepi atas card */
.pf-accent {
    height: 3px;
    background: linear-gradient(90deg,#435ebe,#7c5bd6,#f59e0b);
    background-size: 200% 100%;
    animation: pf-shimmer 6s linear infinite;
}
@keyframes pf-shimmer { 0% { background-position: 0% 0; } 100% { background-position: 200% 0; } }

/* Badge trophy */
.pf-badge {
    width: 30px; height: 30px; border-radius: 9px;
    background: linear-gradient(135deg,#435ebe,#7c5bd6);
    color: #fff; font-size: .82rem;
    box-shadow: 0 3px 10px rgba(67,94,190,.35);
}

.pf-more {
    color: var(--pf-muted); text-decoration: none;
    padding: 3px 7px; border-radius: 999px; transition: all .15s ease;
}
.pf-more:hover { color: #435ebe; background: var(--pf-surface); }
.pf-more:hover i { transform: translateX(2px); }
.pf-more i { transition: transform .15s ease; }

/* ── Ring progress ── */
.pf-ring { position: relative; width: 58px; height: 58px; }
.pf-ring-track { stroke: var(--pf-track); }
.pf-ring-fill  {
    stroke: #435ebe;
    transition: stroke-dashoffset 900ms cubic-bezier(.4,0,.2,1);
    animation: pf-ring-in 900ms cubic-bezier(.4,0,.2,1);
}
.pf-ring-label {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
}
.pf-ring-num { font-weight: 700; font-size: .95rem; color: var(--pf-text); }
.pf-ring-den { font-weight: 600; font-size: .62rem; color: var(--pf-muted); }

/* ── Misi ── */
.pf-mission {
    padding: 8px 10px; border-radius: 10px;
    border: 1px solid var(--pf-border);
    background: var(--pf-surface);
    transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
}
.pf-mission-todo:hover {
    background: var(--pf-surface-2);
    transform: translateX(3px);
    box-shadow: 0 4px 14px rgba(67,94,190,.12);
    border-color: rgba(67,94,190,.35);
}
.pf-mission-done { opacity: .72; }
.pf-mission-done .pf-mission-label { color: var(--pf-muted); text-decoration: line-through; }

.pf-check {
    width: 26px; height: 26px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(67,94,190,.12); color: #5b7bd6; font-size: .8rem;
}
.pf-check-done { background: rgba(16,185,129,.16); color: #10b981; }
.pf-mission-label { color: var(--pf-text); }

.pf-reward {
    font-size: .58rem; font-weight: 600;
    padding: 2px 7px; border-radius: 999px;
    background: rgba(67,94,190,.12); color: #5b7bd6; white-space: nowrap;
}
.pf-reward-done { background: rgba(16,185,129,.14); color: #10b981; }
.pf-mission-arrow { color: var(--pf-muted); font-size: .72rem; transition: transform .15s ease; }
.pf-mission-todo:hover .pf-mission-arrow { transform: translateX(3px); color: #435ebe; }

/* ── Tiles (mode progres) ── */
.pf-tile {
    border-radius: 10px; padding: 10px;
    background: var(--pf-surface); border: 1px solid var(--pf-border);
    transition: transform .14s ease, box-shadow .14s ease;
}
.pf-tile-accent { background: linear-gradient(135deg, rgba(67,94,190,.10), var(--pf-surface)); }
.pf-tile-warn   { background: linear-gradient(135deg, rgba(245,158,11,.10), var(--pf-surface)); }
.pf-tile-ico    { font-size: .72rem; }
.pf-divider     { width: 1px; height: 24px; background: var(--pf-border); }

.pf-bar { height: 5px; border-radius: 99px; background: var(--pf-track); overflow: hidden; }
.pf-bar-fill {
    height: 100%; border-radius: 99px;
    transition: width 800ms cubic-bezier(.4,0,.2,1);
    animation: pf-bar-in 900ms cubic-bezier(.4,0,.2,1);
}
.pf-pill {
    font-size: .58rem; font-weight: 600; width: fit-content;
    padding: 2px 8px; border-radius: 999px;
}

.pf-insight { transition: opacity .14s ease; border-radius: 6px; padding: 1px 2px; }
.pf-insight:hover { opacity: .7; }

@keyframes pf-bar-in  { from { width: 0; } }
@keyframes pf-ring-in { from { stroke-dashoffset: {{ $ringCirc ?? 138 }}; } }
</style>
