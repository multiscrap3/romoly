@extends('layouts.app')

@section('title', __('notifikasi.title'))
@section('page-title', __('notifikasi.title'))

@section('content')
<div class="row g-3 justify-content-center">
<div class="col-12 col-lg-8">

    {{-- Header bar --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="small text-muted">{{ $notifikasi->total() }} notifikasi</span>
        @if($notifikasi->isNotEmpty())
            <form method="POST" action="{{ route('notifikasi.mark-all-read') }}" data-tour="notif-read-all">
                @csrf
                <button type="submit" class="btn btn-link btn-sm p-0 text-primary">{{ __('notifikasi.mark_all_read') }}</button>
            </form>
        @endif
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:.75rem;" data-tour="notif-list">
        <div class="card-body p-0">
            @forelse($notifikasi as $notif)
                @php
                    [$iconClass, $bgColor] = match($notif->jenis) {
                        'anggaran'       => ['bi-pie-chart-fill', '#fef9c3'],
                        'tabungan'       => ['bi-piggy-bank-fill', '#dcfce7'],
                        'hutang_piutang' => ['bi-credit-card-fill', '#fee2e2'],
                        'tagihan'        => ['bi-receipt', '#ffedd5'],
                        default          => ['bi-bell-fill', '#dbeafe'],
                    };
                    $iconColor = match($notif->jenis) {
                        'anggaran'       => '#ca8a04',
                        'tabungan'       => '#16a34a',
                        'hutang_piutang' => '#dc2626',
                        'tagihan'        => '#ea580c',
                        default          => '#2563eb',
                    };
                @endphp
                <div class="d-flex gap-3 px-4 py-3 border-bottom {{ $notif->is_read ? '' : '' }}"
                     style="{{ $notif->is_read ? '' : 'background:#eff6ff;' }}">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                         style="width:40px;height:40px;background:{{ $bgColor }};">
                        <i class="bi {{ $iconClass }}" style="color:{{ $iconColor }};font-size:1rem;"></i>
                    </div>
                    <div class="flex-grow-1 small">
                        <div class="fw-medium text-dark">{{ $notif->judul }}</div>
                        <div class="text-muted mt-1">{{ $notif->pesan }}</div>
                        <div class="d-flex align-items-center gap-3 mt-1">
                            <span class="text-muted" style="font-size:.72rem;">{{ $notif->created_at->diffForHumans() }}</span>
                            @if($notif->link)
                                <a href="{{ $notif->link }}" class="small text-primary text-decoration-none" style="font-size:.72rem;">Lihat detail</a>
                            @endif
                        </div>
                    </div>
                    @if(!$notif->is_read)
                        <form method="POST" action="{{ route('notifikasi.mark-read', $notif) }}" class="d-flex align-items-start flex-shrink-0">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm p-0 text-primary" style="font-size:.72rem;">{{ __('notifikasi.mark_read') }}</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="py-5 text-center">
                    <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-3"
                         style="width:56px;height:56px;background:#f3f4f6;">
                        <i class="bi bi-bell fs-4 text-muted"></i>
                    </div>
                    <p class="text-muted small">{{ __('notifikasi.no_notif') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($notifikasi->hasPages())
        <div class="mt-3">{{ $notifikasi->links('pagination::bootstrap-5') }}</div>
    @endif

</div>
</div>
@endsection
