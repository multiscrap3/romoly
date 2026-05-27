@extends('layouts.superadmin')

@section('title', $household->nama)
@section('page-title', 'Detail: ' . $household->nama)

@section('content')
<div class="row g-4">

    {{-- Stats --}}
    @foreach([
        ['label' => 'Total Transaksi', 'value' => number_format($stats['total_transaksi'])],
        ['label' => 'Total Anggaran',  'value' => number_format($stats['total_anggaran'])],
        ['label' => 'Total Tabungan',  'value' => number_format($stats['total_tabungan'])],
        ['label' => 'Anggota',         'value' => $household->users->count()],
    ] as $stat)
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
                <div class="card-body p-3 text-center">
                    <div class="fw-bold fs-5">{{ $stat['value'] }}</div>
                    <div class="small text-muted">{{ $stat['label'] }}</div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Info + Anggota --}}
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">{{ __('superadmin.households') }}</h6>
                <dl class="row g-2 small mb-0">
                    <dt class="col-5 text-muted">ID</dt>
                    <dd class="col-7">{{ $household->id }}</dd>
                    <dt class="col-5 text-muted">{{ __('superadmin.name') }}</dt>
                    <dd class="col-7 fw-medium">{{ $household->nama }}</dd>
                    <dt class="col-5 text-muted">{{ __('superadmin.status') }}</dt>
                    <dd class="col-7">
                        <span class="badge rounded-pill {{ $household->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                            {{ $household->status }}
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">Plan</dt>
                    <dd class="col-7">{{ $household->plan?->nama ?? 'Free' }}</dd>
                    <dt class="col-5 text-muted">{{ __('superadmin.created') }}</dt>
                    <dd class="col-7">{{ $household->created_at->translatedFormat('d M Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">Anggota ({{ $household->users->count() }})</h6>
                @foreach($household->users as $user)
                    <div class="d-flex align-items-center justify-content-between small mb-2">
                        <div>
                            <div class="fw-medium">{{ $user->name }}</div>
                            <div class="text-muted" style="font-size:.72rem;">{{ $user->email }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">{{ $user->getRoleNames()->implode(', ') ?: '-' }}</span>
                            <span class="rounded-circle d-inline-block {{ $user->is_active ? 'bg-success' : 'bg-danger' }}"
                                  style="width:8px;height:8px;"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Aktivitas terbaru --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">{{ __('superadmin.logs') }}</h6>
            </div>
            <div class="card-body p-0">
                @forelse($recentActivity as $log)
                    <div class="d-flex align-items-start justify-content-between gap-3 px-4 py-3 border-bottom small">
                        <div>
                            <span class="fw-medium">{{ $log->user?->name ?? 'System' }}</span>
                            <span class="text-muted ms-2">{{ $log->action }}</span>
                            @if($log->description)
                                <div class="text-muted mt-1" style="font-size:.72rem;">{{ $log->description }}</div>
                            @endif
                        </div>
                        <span class="text-muted flex-shrink-0" style="font-size:.72rem;">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="px-4 py-5 text-center text-muted small">Belum ada aktivitas.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
