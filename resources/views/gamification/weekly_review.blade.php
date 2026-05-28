@extends('layouts.app')

@section('title', 'Weekly Review')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Weekly Review</h3>
                <p class="text-subtitle text-muted">
                    {{ $review->week_start->format('d M') }} – {{ $review->week_end->format('d M Y') }}
                </p>
            </div>
        </div>
    </div>

    <section class="section">
        @php $data = $review->data; @endphp

        <div class="row g-3">
            {{-- Spending Comparison --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h4 class="card-title">Perbandingan Pengeluaran</h4></div>
                    <div class="card-body">
                        <div class="d-flex gap-4 mb-3">
                            <div>
                                <div class="small text-muted">Minggu ini</div>
                                <div class="fw-semibold fs-5">Rp {{ number_format($data['spending_comparison']['this_week']) }}</div>
                            </div>
                            <div>
                                <div class="small text-muted">Minggu lalu</div>
                                <div class="fw-semibold fs-5">Rp {{ number_format($data['spending_comparison']['last_week']) }}</div>
                            </div>
                        </div>
                        @if($data['spending_comparison']['improved'])
                        <span class="badge bg-light-success text-success">
                            <i class="bi bi-arrow-down-short"></i> {{ abs($data['spending_comparison']['diff_percent']) }}% lebih hemat
                        </span>
                        @else
                        <span class="badge bg-light-danger text-danger">
                            <i class="bi bi-arrow-up-short"></i> +{{ $data['spending_comparison']['diff_percent'] }}% dari minggu lalu
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Momentum + XP --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h4 class="card-title">Progress Minggu Ini</h4></div>
                    <div class="card-body">
                        <div class="d-flex gap-4 mb-3">
                            <div>
                                <div class="small text-muted">XP Didapat</div>
                                <div class="fw-semibold fs-5 text-primary">+{{ $data['xp_gained_this_week'] }} XP</div>
                            </div>
                            <div>
                                <div class="small text-muted">Momentum</div>
                                <div class="fw-semibold fs-5">{{ $data['momentum_trend']['score'] }}</div>
                                <div class="small text-muted">{{ $data['momentum_trend']['status'] }}</div>
                            </div>
                        </div>
                        @if(!empty($data['achievements_this_week']))
                        <div class="small text-muted mb-1">Achievement minggu ini:</div>
                        @foreach($data['achievements_this_week'] as $achName)
                        <span class="badge bg-primary me-1">{{ $achName }}</span>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Top Spending Categories --}}
            @if(!empty($data['top_spending_category']))
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h4 class="card-title">Kategori Pengeluaran Terbesar</h4></div>
                    <div class="card-body">
                        @foreach($data['top_spending_category'] as $cat)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $cat['kategori'] }}</span>
                            <span class="fw-semibold">Rp {{ number_format($cat['total']) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Insights --}}
            @if(!empty($data['unusual_spending']))
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h4 class="card-title">Insight Keuangan</h4></div>
                    <div class="card-body">
                        @foreach($data['unusual_spending'] as $insight)
                        <div class="alert alert-light border mb-2 py-2">
                            <small>{{ $insight['message'] }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Saving Progress --}}
            @if(!empty($data['saving_progress']))
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Progress Tabungan</h4></div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($data['saving_progress'] as $saving)
                            <div class="col-md-4">
                                <div class="small fw-semibold mb-1">{{ $saving['nama'] }}</div>
                                <div class="progress mb-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ min(100, $saving['percent']) }}%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Rp {{ number_format($saving['saved']) }}</span>
                                    <span>{{ $saving['percent'] }}%</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="mt-4">
            <a href="{{ route('gamifikasi.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </section>
</div>
@endsection
