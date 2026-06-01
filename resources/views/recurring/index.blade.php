@extends('layouts.app')

@section('title', __('recurring.title'))
@section('page-title', __('recurring.title'))

@section('content')
<div class="row g-4">

    <div class="col-12 d-flex justify-content-end">
        <a href="{{ route('recurring.create') }}" class="btn btn-primary btn-sm" data-tour="recurring-add">
            <i class="bi bi-plus-lg me-1"></i>{{ __('recurring.add') }}
        </a>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-0">
                @forelse($recurring ?? [] as $item)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                             style="width:40px;height:40px;
                             background:{{ $item->jenis === 'pemasukan' ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)' }};">
                            <i class="bi bi-arrow-repeat {{ $item->jenis === 'pemasukan' ? 'text-success' : 'text-danger' }} fs-5"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="small fw-medium text-dark text-truncate">{{ $item->keterangan }}</div>
                            <div class="text-muted d-flex align-items-center gap-1 flex-wrap" style="font-size:.72rem;">
                                <span class="text-capitalize">{{ $item->frekuensi }}</span>
                                <span>&bull;</span>
                                <span>{{ __('recurring.start_date') }} {{ $item->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                @if($item->sumberTransaksi)
                                    <span>&bull;</span>
                                    <span>{{ $item->sumberTransaksi->nama }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="small fw-bold {{ $item->jenis === 'pemasukan' ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1 justify-content-end" style="font-size:.72rem;">
                                <span class="badge rounded-pill {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}"
                                      style="font-size:.6rem;">{{ $item->is_active ? __('recurring.active') : __('recurring.inactive') }}</span>
                                <form method="POST" action="{{ route('recurring.toggle', $item) }}" class="d-inline" data-tour="recurring-toggle">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-primary" style="font-size:.72rem;">
                                        {{ $item->is_active ? __('recurring.inactive') : __('recurring.active') }}
                                    </button>
                                </form>
                                <a href="{{ route('recurring.edit', $item) }}" class="text-muted text-decoration-none">{{ __('messages.edit') }}</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-5 text-center">
                        <i class="bi bi-arrow-repeat fs-1 d-block mb-2 text-muted opacity-25"></i>
                        <p class="text-muted small mb-2">{{ __('recurring.no_recurring') }}</p>
                        <a href="{{ route('recurring.create') }}" class="small text-primary fw-medium text-decoration-none">
                            + {{ __('messages.add') }}
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
