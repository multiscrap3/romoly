@extends('layouts.app')

@section('title', __('hutang.title'))
@section('page-title', __('hutang.title'))

@section('content')
<div class="row g-4">

    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
        {{-- Bootstrap Nav Tabs --}}
        <ul class="nav nav-pills" id="hpTab" role="tablist" data-tour="hp-ringkasan">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ request('tab', 'hutang') === 'hutang' ? 'active' : '' }}"
                        id="hutang-tab" data-bs-toggle="pill" data-bs-target="#hutangPanel" type="button" role="tab">
                    {{ __('hutang.debt') }}
                    @php $totalHutang = $summary['total_hutang'] ?? 0; @endphp
                    @if($totalHutang > 0)
                        <span class="badge bg-danger ms-1" style="font-size:.65rem;">Rp {{ number_format($totalHutang, 0, ',', '.') }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ request('tab') === 'piutang' ? 'active' : '' }}"
                        id="piutang-tab" data-bs-toggle="pill" data-bs-target="#piutangPanel" type="button" role="tab">
                    {{ __('hutang.credit') }}
                    @php $totalPiutang = $summary['total_piutang'] ?? 0; @endphp
                    @if($totalPiutang > 0)
                        <span class="badge bg-success ms-1" style="font-size:.65rem;">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</span>
                    @endif
                </button>
            </li>
        </ul>
        <a href="{{ route('hutang-piutang.create') }}" class="btn btn-primary btn-sm" data-tour="hp-add">
            <i class="bi bi-plus-lg me-1"></i>{{ __('hutang.add') }}
        </a>
    </div>

    <div class="col-12">
        <div class="tab-content" id="hpTabContent">

            {{-- Hutang panel --}}
            <div class="tab-pane fade {{ request('tab', 'hutang') === 'hutang' ? 'show active' : '' }}" id="hutangPanel" role="tabpanel">
                <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                    <div class="card-body p-0">
                        @forelse($hutangPiutang->where('jenis', 'hutang') as $item)
                            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:40px;height:40px;background:rgba(239,68,68,.12);">
                                    <i class="bi bi-arrow-down-circle text-danger fs-5"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="small fw-medium text-dark">{{ $item->nama_pihak }}</div>
                                    <div class="text-muted d-flex align-items-center gap-1 flex-wrap" style="font-size:.72rem;">
                                        @if($item->tanggal_jatuh_tempo)
                                            <span>Jatuh tempo: {{ $item->tanggal_jatuh_tempo->translatedFormat('d M Y') }}</span>
                                            @if($item->tanggal_jatuh_tempo->isPast() && $item->status !== 'lunas')
                                                <span class="badge bg-danger" style="font-size:.6rem;">{{ __('hutang.overdue') }}</span>
                                            @endif
                                        @else
                                            <span>{{ __('hutang.due_date') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="small fw-bold text-danger">Rp {{ number_format($item->sisa, 0, ',', '.') }}</div>
                                    @if($item->jumlah_terbayar > 0)
                                        <div class="text-muted" style="font-size:.7rem;">dari Rp {{ number_format($item->jumlah_total, 0, ',', '.') }}</div>
                                    @endif
                                    <div class="d-flex gap-2 mt-1 justify-content-end" style="font-size:.72rem;">
                                        <a href="{{ route('hutang-piutang.show', $item) }}" class="text-primary text-decoration-none">{{ __('hutang.detail') }}</a>
                                        @if($item->status !== 'lunas')
                                            <span class="text-muted">|</span>
                                            <a href="{{ route('hutang-piutang.show', $item) }}" class="text-success text-decoration-none">{{ __('hutang.mark_paid') }}</a>
                                        @else
                                            <span class="text-success fw-medium">{{ __('hutang.paid') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-muted small">{{ __('hutang.no_data') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Piutang panel --}}
            <div class="tab-pane fade {{ request('tab') === 'piutang' ? 'show active' : '' }}" id="piutangPanel" role="tabpanel">
                <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                    <div class="card-body p-0">
                        @forelse($hutangPiutang->where('jenis', 'piutang') as $item)
                            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:40px;height:40px;background:rgba(16,185,129,.12);">
                                    <i class="bi bi-arrow-up-circle text-success fs-5"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="small fw-medium text-dark">{{ $item->nama_pihak }}</div>
                                    <div class="text-muted d-flex align-items-center gap-1 flex-wrap" style="font-size:.72rem;">
                                        @if($item->tanggal_jatuh_tempo)
                                            <span>Jatuh tempo: {{ $item->tanggal_jatuh_tempo->translatedFormat('d M Y') }}</span>
                                            @if($item->tanggal_jatuh_tempo->isPast() && $item->status !== 'lunas')
                                                <span class="badge bg-warning text-dark" style="font-size:.6rem;">{{ __('hutang.overdue') }}</span>
                                            @endif
                                        @else
                                            <span>{{ __('hutang.due_date') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="small fw-bold text-success">Rp {{ number_format($item->sisa, 0, ',', '.') }}</div>
                                    @if($item->jumlah_terbayar > 0)
                                        <div class="text-muted" style="font-size:.7rem;">dari Rp {{ number_format($item->jumlah_total, 0, ',', '.') }}</div>
                                    @endif
                                    <div class="d-flex gap-2 mt-1 justify-content-end" style="font-size:.72rem;">
                                        <a href="{{ route('hutang-piutang.show', $item) }}" class="text-primary text-decoration-none">{{ __('hutang.detail') }}</a>
                                        @if($item->status !== 'lunas')
                                            <span class="text-muted">|</span>
                                            <a href="{{ route('hutang-piutang.show', $item) }}" class="text-primary text-decoration-none">{{ __('hutang.outstanding') }}</a>
                                        @else
                                            <span class="text-success fw-medium">{{ __('hutang.paid') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-muted small">{{ __('hutang.no_data') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
