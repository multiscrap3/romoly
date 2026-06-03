@php
    /** @var \App\Models\UserChallenge $uc */
    $c   = $uc->challenge;
    $p   = $uc->progress_view;
    $pct = $p['percent'];

    $diff = $c->difficulty;
    $diffMap = [
        'easy'   => ['label' => 'Mudah',  'class' => 'success'],
        'medium' => ['label' => 'Sedang', 'class' => 'warning'],
        'hard'   => ['label' => 'Sulit',  'class' => 'danger'],
    ];
    $d = $diffMap[$diff] ?? ['label' => ucfirst($diff), 'class' => 'secondary'];

    // Warna bar: mode limit (budget) -> hijau aman, merah kalau >90%
    if ($p['mode'] === 'limit') {
        $barClass = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
    } else {
        $barClass = $pct >= 100 ? 'bg-success' : 'bg-primary';
    }

    // Countdown
    $daysLeft = (int) ceil(now()->floatDiffInDays($uc->expires_at, false));
    $urgent   = $daysLeft <= 1;

    $iconMap = [
        'daily_transaction_logged'    => 'bi-calendar-check',
        'no_food_delivery_days'       => 'bi-bag-x',
        'saving_ratio'                => 'bi-wallet-fill',
        'emergency_fund_contribution' => 'bi-shield-shaded',
        'no_budget_exceeded'          => 'bi-wallet2',
        'category_budget_limit'       => 'bi-graph-down',
    ];
    $icon = $iconMap[$c->condition_type] ?? 'bi-flag';
    $almostDone = $pct >= 70 && $p['mode'] !== 'limit';
@endphp

<div class="challenge-card card h-100 {{ $almostDone ? 'challenge-hot' : '' }}">
    <div class="card-body d-flex flex-column">

        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="challenge-icon flex-shrink-0">
                <i class="bi {{ $icon }}"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <h6 class="fw-bold mb-1 lh-sm">{{ $c->title }}</h6>
                    <span class="badge bg-light-{{ $d['class'] }} text-{{ $d['class'] }} flex-shrink-0">{{ $d['label'] }}</span>
                </div>
                <p class="text-muted small mb-0 lh-sm">{{ $c->description }}</p>
            </div>
        </div>

        {{-- Progress --}}
        <div class="mt-auto">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-semibold challenge-amt">{{ $p['display'] }}</span>
                <span class="small fw-bold text-{{ $barClass === 'bg-danger' ? 'danger' : 'muted' }}">{{ rtrim(rtrim(number_format($pct, 1), '0'), '.') }}%</span>
            </div>
            <div class="progress challenge-progress mb-3" style="height:8px;">
                <div class="progress-bar {{ $barClass }}" role="progressbar"
                     style="width:0" data-target="{{ $pct }}"
                     aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            {{-- Footer: reward + countdown + CTA --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge reward-badge"><i class="bi bi-lightning-charge-fill me-1"></i>+{{ $c->xp_reward }} XP</span>
                    @if($c->momentum_bonus)
                    <span class="badge bg-light-info text-info">+{{ $c->momentum_bonus }} momentum</span>
                    @endif
                </div>
                <span class="small {{ $urgent ? 'text-danger fw-semibold' : 'text-muted' }}">
                    <i class="bi bi-clock{{ $urgent ? '-history' : '' }} me-1"></i>
                    @if($daysLeft <= 0) Hari terakhir
                    @elseif($daysLeft === 1) Sisa 1 hari
                    @else Sisa {{ $daysLeft }} hari @endif
                </span>
            </div>

            <a href="{{ route($p['cta_route']) }}" class="btn btn-sm btn-outline-primary w-100 mt-3 challenge-cta">
                {{ $p['cta_label'] }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
