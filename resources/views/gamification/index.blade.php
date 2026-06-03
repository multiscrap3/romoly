@extends('layouts.app')

@section('title', 'Progres Finansial')

@section('content')
<div class="page-heading gamify-page">

    {{-- ══════════════ HERO ══════════════ --}}
    @php
        $circ = 282.74; // 2πr, r=45
        $fill = round($circ * min(100, (float) $progressPercent) / 100, 2);
        $momoColor = match(true) {
            $stats['momentum'] >= 90 => '#10b981',
            $stats['momentum'] >= 70 => '#435ebe',
            $stats['momentum'] >= 40 => '#f59e0b',
            default                  => '#ef4444',
        };
    @endphp
    <div class="gamify-hero card border-0 mb-4" data-tour="game-level">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                {{-- Level ring --}}
                <div class="col-auto">
                    <div class="hero-ring position-relative">
                        <svg width="118" height="118" viewBox="0 0 100 100" role="img" aria-label="Progres XP {{ $progressPercent }}%">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,.18)" stroke-width="6"/>
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#fff" stroke-width="6"
                                stroke-linecap="round" stroke-dasharray="0 282.74"
                                data-fill="{{ $fill }}" transform="rotate(-90 50 50)" class="xp-ring-fill"/>
                        </svg>
                        <div class="position-absolute top-50 start-50 translate-middle text-center text-white" style="pointer-events:none;">
                            <div class="lh-1" style="font-size:.6rem;opacity:.8;letter-spacing:.08em;">LEVEL</div>
                            <div class="fw-bold lh-1" style="font-size:1.9rem;">{{ $stats['level'] }}</div>
                        </div>
                    </div>
                </div>

                {{-- Identity --}}
                <div class="col">
                    <div class="text-white-50 small text-uppercase mb-1" style="letter-spacing:.1em;">Identitas Finansialmu</div>
                    <h3 class="text-white fw-bold mb-1">{{ $stats['title'] }}</h3>
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                        <span class="text-white fw-semibold"><i class="bi bi-lightning-charge-fill me-1"></i>{{ number_format($stats['total_xp']) }} XP</span>
                        @if($stats['next_title'])
                        <span class="text-white-50 small">
                            <i class="bi bi-arrow-up-right me-1"></i>{{ number_format($xpToNext) }} XP lagi → <strong class="text-white">{{ $stats['next_title'] }}</strong>
                        </span>
                        @else
                        <span class="badge bg-white text-primary"><i class="bi bi-trophy-fill me-1"></i>Level Maksimum</span>
                        @endif
                    </div>
                    <div class="progress hero-progress" style="height:7px;max-width:420px;">
                        <div class="progress-bar bg-white" style="width:0" data-target="{{ $progressPercent }}"></div>
                    </div>
                </div>

                {{-- CTA --}}
                <div class="col-12 col-lg-auto">
                    <a href="{{ route('transaksi.create') }}" class="btn btn-light fw-semibold px-4 w-100">
                        <i class="bi bi-plus-circle-fill me-1 text-primary"></i> Catat Transaksi
                    </a>
                    <div class="text-center text-white-50 small mt-2">+2 XP setiap transaksi</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════ STAT STRIP ══════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-tile h-100"><div class="card-body">
                <div class="stat-ic" style="background:#eef1fb;color:#435ebe;"><i class="bi bi-graph-up"></i></div>
                <div class="stat-num">{{ number_format($stats['xp_week']) }}</div>
                <div class="stat-lbl">XP minggu ini</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3" data-tour="game-momentum">
            <div class="card stat-tile h-100"><div class="card-body">
                <div class="stat-ic" style="background:{{ $momoColor }}1a;color:{{ $momoColor }};"><i class="bi bi-speedometer2"></i></div>
                <div class="stat-num">{{ $stats['momentum'] }}<small class="text-muted fw-normal" style="font-size:.9rem;">/100</small></div>
                <div class="stat-lbl">{{ $momentumStatus }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-tile h-100"><div class="card-body">
                <div class="stat-ic" style="background:#fff4e5;color:#f59e0b;"><i class="bi bi-flag-fill"></i></div>
                <div class="stat-num">{{ $stats['challenges_done'] }}</div>
                <div class="stat-lbl">Tantangan selesai</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-tile h-100"><div class="card-body">
                <div class="stat-ic" style="background:#f3eafd;color:#8b5cf6;"><i class="bi bi-trophy-fill"></i></div>
                <div class="stat-num">{{ $stats['completion'] }}%</div>
                <div class="stat-lbl">Koleksi achievement</div>
            </div></div>
        </div>
    </div>

    {{-- ══════════════ MOMENTUM ══════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            @include('gamification._momentum_card')
        </div>
    </div>

    {{-- ══════════════ ACTIVE CHALLENGES ══════════════ --}}
    <div class="d-flex justify-content-between align-items-center mb-3" data-tour="game-challenge">
        <div>
            <h5 class="fw-bold mb-0"><i class="bi bi-flag-fill text-warning me-2"></i>Tantangan Aktif</h5>
            <p class="text-muted small mb-0">Selesaikan untuk XP & momentum ekstra. Tanpa penalti jika lewat.</p>
        </div>
        @if($activeChallenges->isNotEmpty())
        <span class="badge bg-primary rounded-pill">{{ $activeChallenges->count() }} aktif</span>
        @endif
    </div>

    @if($activeChallenges->isNotEmpty())
    <div class="row g-3 mb-4">
        @foreach($activeChallenges as $uc)
        <div class="col-12 col-md-6 col-xl-4">
            @include('gamification._challenge_card', ['uc' => $uc])
        </div>
        @endforeach
    </div>
    @else
    <div class="card mb-4"><div class="card-body text-center py-5">
        <i class="bi bi-flag text-muted" style="font-size:2.5rem;opacity:.5;"></i>
        <p class="text-muted mb-1 mt-2 fw-semibold">Belum ada tantangan aktif</p>
        <p class="text-muted small mb-0">Tantangan baru muncul setiap awal minggu. Sampai jumpa lagi!</p>
    </div></div>
    @endif

    {{-- ══════════════ WEEKLY REVIEW ══════════════ --}}
    @if($latestReview)
    <div class="card mb-4 weekly-card">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="weekly-ic"><i class="bi bi-bar-chart-line-fill"></i></div>
                <div>
                    <div class="fw-bold">Weekly Review
                        @if(!$latestReview->viewed_at)<span class="badge bg-danger ms-1">Baru</span>@endif
                    </div>
                    <div class="text-muted small">Periode {{ $latestReview->week_start->format('d M') }} – {{ $latestReview->week_end->format('d M Y') }}</div>
                </div>
            </div>
            <a href="{{ route('gamifikasi.review.show', $latestReview->id) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-eye me-1"></i> Lihat Review {{ !$latestReview->viewed_at ? '(+10 XP)' : '' }}
            </a>
        </div>
    </div>
    @endif

    {{-- ══════════════ ACHIEVEMENT COLLECTION ══════════════ --}}
    <div class="card">
        <div class="card-header border-0 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="fw-bold mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Koleksi Achievement</h5>
                    <p class="text-muted small mb-0">{{ $stats['achievements'] }} dari {{ $stats['achievements_all'] }} pencapaian terkumpul</p>
                </div>
                {{-- Rarity legend --}}
                <div class="d-flex flex-wrap gap-2">
                    @php $rarMeta = ['platinum'=>['#8b5cf6','Platinum'],'gold'=>['#f59e0b','Gold'],'silver'=>['#9e9e9e','Silver'],'bronze'=>['#cd7f32','Bronze']]; @endphp
                    @foreach($rarityCounts as $r => $rc)
                    <span class="rarity-chip" style="--rc:{{ $rarMeta[$r][0] }}">
                        <span class="dot"></span>{{ $rarMeta[$r][1] }} {{ $rc['earned'] }}/{{ $rc['total'] }}
                    </span>
                    @endforeach
                </div>
            </div>

            {{-- Overall collection progress --}}
            <div class="progress mt-3 mb-3" style="height:8px;">
                <div class="progress-bar bg-warning" style="width:0" data-target="{{ $stats['completion'] }}"></div>
            </div>

            {{-- Filter tabs --}}
            <div class="ach-filters d-flex flex-wrap gap-2 mb-3">
                <button class="btn btn-sm ach-filter active" data-filter="all">Semua <span class="badge bg-secondary ms-1">{{ $stats['achievements_all'] }}</span></button>
                <button class="btn btn-sm ach-filter" data-filter="earned">Diraih <span class="badge bg-success ms-1">{{ $stats['achievements'] }}</span></button>
                <button class="btn btn-sm ach-filter" data-filter="locked">Terkunci <span class="badge bg-secondary ms-1">{{ $stats['achievements_all'] - $stats['achievements'] }}</span></button>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row g-2" id="achievementGrid">
                @foreach($allAchievements as $ach)
                @php $ua = $earnedMap[$ach->id] ?? null; @endphp
                <div class="col-6 col-md-4 col-xl-3 ach-item" data-state="{{ $ua ? 'earned' : 'locked' }}">
                    @include('gamification._achievement_card', [
                        'achievement' => $ach,
                        'isEarned'    => !is_null($ua),
                        'earnedAt'    => $ua?->earned_at,
                    ])
                </div>
                @endforeach
            </div>
            <p class="text-muted text-center small mt-3 mb-0 d-none" id="achEmpty">Tidak ada achievement di kategori ini.</p>
        </div>
    </div>

</div>

{{-- Major Achievement Modal --}}
@include('gamification._achievement_modal')

@push('styles')
<style>
.gamify-page .gamify-hero {
    background: linear-gradient(135deg, #435ebe 0%, #5b76d8 55%, #7c4fd8 100%);
    border-radius: 1rem;
    box-shadow: 0 10px 30px rgba(67,94,190,.25);
}
.gamify-hero .hero-progress { background: rgba(255,255,255,.22); border-radius:10px; }
.xp-ring-fill { transition: stroke-dasharray 1100ms cubic-bezier(.2,.8,.2,1); }

/* Stat tiles */
.stat-tile { border:1px solid #eef0f4; border-radius:.85rem; transition:transform .2s ease, box-shadow .2s ease; }
.stat-tile:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.06); }
.stat-ic { width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;margin-bottom:.6rem; }
.stat-num { font-size:1.7rem;font-weight:700;line-height:1;color:#1f2937; }
.stat-lbl { font-size:.78rem;color:#6c757d;margin-top:.25rem; }

/* Challenge cards */
.challenge-card { border:1px solid #eef0f4;border-radius:.85rem;transition:transform .2s ease,box-shadow .2s ease; }
.challenge-card:hover { transform:translateY(-3px);box-shadow:0 10px 24px rgba(0,0,0,.07); }
.challenge-card.challenge-hot { border-color:#cdb6f8; box-shadow:0 0 0 1px rgba(67,94,190,.25),0 8px 20px rgba(67,94,190,.1); }
.challenge-icon { width:44px;height:44px;border-radius:12px;background:#eef1fb;color:#435ebe;display:flex;align-items:center;justify-content:center;font-size:1.3rem; }
.challenge-progress { background:#eef0f4;border-radius:10px;overflow:hidden; }
.challenge-progress .progress-bar { transition:width 1000ms cubic-bezier(.2,.8,.2,1);border-radius:10px; }
.reward-badge { background:linear-gradient(135deg,#435ebe,#7c4fd8);color:#fff; }
.challenge-cta { border-radius:.6rem; }

/* Weekly */
.weekly-card { border:1px solid #eef0f4;border-radius:.85rem; }
.weekly-ic { width:46px;height:46px;border-radius:12px;background:#eef1fb;color:#435ebe;display:flex;align-items:center;justify-content:center;font-size:1.35rem; }

/* badge soft colors fallback */
.bg-light-success{background:#e6f7ef!important;} .bg-light-warning{background:#fff4e5!important;}
.bg-light-danger{background:#fdeaea!important;} .bg-light-info{background:#e7f5fb!important;}
.bg-light-secondary{background:#f1f2f4!important;}

/* Rarity legend chips */
.rarity-chip { font-size:.72rem;color:#6c757d;display:inline-flex;align-items:center;gap:5px;background:#f6f7f9;padding:3px 9px;border-radius:20px; }
.rarity-chip .dot { width:8px;height:8px;border-radius:50%;background:var(--rc); }

/* Achievement filters */
.ach-filter { background:#f6f7f9;color:#495057;border:1px solid transparent;border-radius:20px;font-size:.82rem;transition:all .15s ease; }
.ach-filter:hover { background:#eceef1; }
.ach-filter.active { background:#435ebe;color:#fff; }
.ach-filter.active .badge { background:rgba(255,255,255,.25)!important;color:#fff; }
.ach-item.hide { display:none !important; }
.challenge-amt { color:#1f2937; }

/* ══════════ DARK MODE OVERRIDES ══════════ */
[data-theme-version="dark"] .stat-num,
[data-theme-version="dark"] .challenge-amt { color:#e9ebf2; }
[data-theme-version="dark"] .stat-lbl { color:#9aa0b0; }

[data-theme-version="dark"] .stat-tile,
[data-theme-version="dark"] .challenge-card,
[data-theme-version="dark"] .weekly-card,
[data-theme-version="dark"] .momentum-card { border-color:rgba(255,255,255,.08); }

[data-theme-version="dark"] .stat-ic,
[data-theme-version="dark"] .challenge-icon,
[data-theme-version="dark"] .weekly-ic { filter:brightness(1.15); }

[data-theme-version="dark"] .challenge-progress,
[data-theme-version="dark"] .gamify-page .progress { background:rgba(255,255,255,.1); }

[data-theme-version="dark"] .challenge-card h6,
[data-theme-version="dark"] .momentum-hint { color:#d6d9e3; }
[data-theme-version="dark"] .momentum-hints { background:rgba(255,255,255,.05); }

[data-theme-version="dark"] .rarity-chip { background:rgba(255,255,255,.06);color:#9aa0b0; }
[data-theme-version="dark"] .ach-filter { background:rgba(255,255,255,.07);color:#c5c8d3; }
[data-theme-version="dark"] .ach-filter:hover { background:rgba(255,255,255,.12); }
[data-theme-version="dark"] .ach-filter.active { background:#435ebe;color:#fff; }

[data-theme-version="dark"] .bg-light-success{background:rgba(16,185,129,.18)!important;}
[data-theme-version="dark"] .bg-light-warning{background:rgba(245,158,11,.18)!important;}
[data-theme-version="dark"] .bg-light-danger{background:rgba(239,68,68,.18)!important;}
[data-theme-version="dark"] .bg-light-info{background:rgba(59,160,210,.18)!important;}

[data-theme-version="dark"] .achievement-card .badge.bg-light { background:rgba(255,255,255,.12)!important; }
[data-theme-version="dark"] .achievement-card .badge.bg-light.text-dark { color:#e9ebf2!important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Animate XP ring
    document.querySelectorAll('.xp-ring-fill').forEach(function (el) {
        var fill = parseFloat(el.getAttribute('data-fill')) || 0;
        setTimeout(function () { el.setAttribute('stroke-dasharray', fill + ' 282.74'); }, 180);
    });
    // Animate all progress bars with data-target
    setTimeout(function () {
        document.querySelectorAll('.progress-bar[data-target]').forEach(function (bar) {
            bar.style.width = (parseFloat(bar.getAttribute('data-target')) || 0) + '%';
        });
    }, 200);

    // Achievement filter
    var grid    = document.getElementById('achievementGrid');
    var empty   = document.getElementById('achEmpty');
    var buttons = document.querySelectorAll('.ach-filter');
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var f = btn.getAttribute('data-filter');
            var visible = 0;
            grid.querySelectorAll('.ach-item').forEach(function (item) {
                var show = (f === 'all') || (item.getAttribute('data-state') === f);
                item.classList.toggle('hide', !show);
                if (show) visible++;
            });
            empty.classList.toggle('d-none', visible !== 0);
        });
    });
});
</script>
@endpush
@endsection
