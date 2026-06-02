@extends('layouts.app')

@section('title', __('laporan.title'))
@section('page-title', __('laporan.title'))

@section('content')
<div class="row g-4">

    {{-- Quick links --}}
    <div class="col-12" data-tour="laporan-jenis">
        <div class="row g-3">
            @foreach([
                ['route' => 'laporan.harian',   'label' => __('laporan.daily'),   'icon' => 'bi-calendar-day',   'color' => '#3b82f6'],
                ['route' => 'laporan.mingguan',  'label' => __('laporan.weekly'), 'icon' => 'bi-calendar-week',  'color' => '#6366f1'],
                ['route' => 'laporan.bulanan',   'label' => __('laporan.monthly'), 'icon' => 'bi-bar-chart-line', 'color' => '#8b5cf6'],
                ['route' => 'laporan.tahunan',   'label' => __('laporan.yearly'),  'icon' => 'bi-pie-chart',      'color' => '#ec4899'],
            ] as $item)
                <div class="col-6 col-md-3">
                    <a href="{{ route($item['route']) }}"
                       class="card border-0 shadow-sm text-decoration-none h-100"
                       style="border-radius:.75rem;transition:.15s;">
                        <div class="card-body p-4 d-flex flex-column align-items-center gap-3 text-center">
                            <div class="d-flex align-items-center justify-content-center rounded-circle"
                                 style="width:52px;height:52px;background:{{ $item['color'] }}20;">
                                <i class="bi {{ $item['icon'] }} fs-4" style="color:{{ $item['color'] }};"></i>
                            </div>
                            <span class="fw-semibold small text-dark">{{ $item['label'] }}</span>
                        </div>
                    </a>
                </div>
            @endforeach
            {{-- Card laporan per tag --}}
            <div class="col-6 col-md-3">
                <a href="{{ route('tags.index') }}"
                   class="card border-0 shadow-sm text-decoration-none h-100"
                   style="border-radius:.75rem;transition:.15s;">
                    <div class="card-body p-4 d-flex flex-column align-items-center gap-3 text-center">
                        <div class="d-flex align-items-center justify-content-center rounded-circle"
                             style="width:52px;height:52px;background:#f59e0b20;">
                            <i class="bi bi-tags fs-4" style="color:#f59e0b;"></i>
                        </div>
                        <span class="fw-semibold small text-dark">Per Tag</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Info + Export --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">{{ __('laporan.summary') }}</h6>
                    <a href="{{ route('laporan.bulanan') }}" class="small text-primary text-decoration-none">{{ __('laporan.detail') }}</a>
                </div>
                <p class="text-muted small mb-3">{{ __('laporan.no_data') }}</p>
                <button type="button" class="btn btn-outline-secondary btn-sm disabled" tabindex="-1" title="{{ __('laporan.export') }}" data-tour="laporan-export">
                    <i class="bi bi-download me-1"></i>{{ __('laporan.export_excel') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
