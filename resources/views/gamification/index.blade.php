@extends('layouts.app')

@section('title', 'Financial Progress')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>{{ __('Financial Progress') }}</h3>
                <p class="text-subtitle text-muted">Track XP, momentum, dan pencapaian finansialmu.</p>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row g-3 mb-4">
            {{-- Level Card --}}
            <div class="col-md-6">
                @include('gamification._level_card')
            </div>
            {{-- Momentum Card --}}
            <div class="col-md-6">
                @include('gamification._momentum_card')
            </div>
        </div>

        {{-- Active Challenges --}}
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Tantangan Aktif</h4>
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
                    <div class="text-end">
                        <div class="badge bg-primary">+{{ $uc->challenge->xp_reward }} XP</div>
                        <div class="small text-muted mt-1">+{{ $uc->challenge->momentum_bonus }} momentum</div>
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0">Tidak ada tantangan aktif. Cek kembali minggu depan.</p>
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
                <p class="text-muted mb-3">
                    Periode {{ $latestReview->week_start->format('d M') }} – {{ $latestReview->week_end->format('d M Y') }}
                </p>
                <a href="{{ route('gamifikasi.review.show', $latestReview->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-bar-chart-line me-1"></i> Lihat Review Mingguan
                </a>
            </div>
        </div>
        @endif

        {{-- Achievements --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Achievement</h4>
                <span class="badge bg-secondary">{{ $achievements->count() }}</span>
            </div>
            <div class="card-body">
                @if($achievements->isEmpty())
                <p class="text-muted">Belum ada achievement. Mulai catat transaksi untuk membuka achievement pertamamu!</p>
                @else
                <div class="row g-2">
                    @foreach($achievements as $ua)
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center h-100 {{ $ua->achievement->tier_type === 'financial' ? 'border-primary' : '' }}">
                            <div class="fw-semibold small mb-1">{{ $ua->achievement->name }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $ua->achievement->description }}</div>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark">+{{ $ua->achievement->xp_reward }} XP</span>
                            </div>
                            <div class="text-muted mt-1" style="font-size:0.7rem;">{{ $ua->earned_at->format('d M Y') }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
