@extends('layouts.app')

@section('title', 'Financial Progress')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Financial Progress</h3>
                <p class="text-subtitle text-muted">Track XP, momentum, dan pencapaian finansialmu.</p>
            </div>
        </div>
    </div>

    <section class="section">

        {{-- Level + Momentum --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                @include('gamification._level_card')
            </div>
            <div class="col-md-6">
                @include('gamification._momentum_card')
            </div>
        </div>

        {{-- Active Challenges --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Tantangan Aktif</h4>
                @if($activeChallenges->isNotEmpty())
                <span class="badge bg-primary">{{ $activeChallenges->count() }}</span>
                @endif
            </div>
            <div class="card-body">
                @forelse($activeChallenges as $uc)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-semibold">{{ $uc->challenge->title }}</div>
                        <small class="text-muted">{{ $uc->challenge->description }}</small>
                        <div class="mt-1">
                            <span class="badge bg-light-{{ $uc->challenge->difficulty === 'easy' ? 'success' : ($uc->challenge->difficulty === 'hard' ? 'danger' : 'warning') }} text-capitalize">
                                {{ $uc->challenge->difficulty }}
                            </span>
                            <small class="text-muted ms-2">Berakhir: {{ $uc->expires_at->format('d M Y') }}</small>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0 ms-3">
                        <div class="badge bg-primary">+{{ $uc->challenge->xp_reward }} XP</div>
                        <div class="small text-muted mt-1">+{{ $uc->challenge->momentum_bonus }} momentum</div>
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0 small">Tidak ada tantangan aktif. Cek kembali minggu depan.</p>
                @endforelse
            </div>
        </div>

        {{-- Latest Weekly Review --}}
        @if($latestReview)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Weekly Review</h4>
                @if(!$latestReview->viewed_at)
                <span class="badge bg-primary">Baru</span>
                @endif
            </div>
            <div class="card-body">
                <p class="text-muted mb-3 small">
                    Periode {{ $latestReview->week_start->format('d M') }} – {{ $latestReview->week_end->format('d M Y') }}
                </p>
                <a href="{{ route('gamifikasi.review.show', $latestReview->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-bar-chart-line me-1"></i> Lihat Review Mingguan
                </a>
            </div>
        </div>
        @endif

        {{-- Achievement Collection --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Achievement Collection</h4>
                <span class="badge bg-secondary">
                    {{ $earnedMap->count() }} / {{ $allAchievements->count() }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($allAchievements as $ach)
                    @php $ua = $earnedMap[$ach->id] ?? null; @endphp
                    <div class="col-6 col-md-3">
                        @include('gamification._achievement_card', [
                            'achievement' => $ach,
                            'isEarned'    => !is_null($ua),
                            'earnedAt'    => $ua?->earned_at,
                        ])
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </section>
</div>

{{-- Major Achievement Modal --}}
@include('gamification._achievement_modal')

@endsection
