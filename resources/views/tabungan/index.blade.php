@extends('layouts.app')

@section('title', __('tabungan.title'))
@section('page-title', __('tabungan.title'))

@section('content')
<div class="row g-4">

    <div class="col-12 d-flex justify-content-end">
        <a href="{{ route('tabungan.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>{{ __('tabungan.add') }}
        </a>
    </div>

    @forelse($tabungan ?? [] as $item)
        @php
            $persen = $item->target_jumlah > 0 ? min(100, ($item->terkumpul / $item->target_jumlah) * 100) : 0;
        @endphp
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <h6 class="fw-semibold mb-0">{{ $item->nama }}</h6>
                            @if($item->target_tanggal)
                                <div class="text-muted" style="font-size:.72rem;">Target: {{ $item->target_tanggal->translatedFormat('d M Y') }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('tabungan.show', $item) }}" class="small text-primary text-decoration-none">{{ __('tabungan.detail') }}</a>
                            <a href="{{ route('tabungan.edit', $item) }}" class="small text-muted text-decoration-none">{{ __('tabungan.edit') }}</a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between small mb-2">
                        <span class="text-muted">{{ __('tabungan.saved') }}</span>
                        <span class="fw-bold text-primary">Rp {{ number_format($item->terkumpul ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="progress mb-2" style="height:8px;">
                        <div class="progress-bar bg-primary" role="progressbar"
                             style="width:{{ $persen }}%"></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between text-muted mb-3" style="font-size:.72rem;">
                        <span>{{ number_format($persen, 0) }}% dari Rp {{ number_format($item->target_jumlah, 0, ',', '.') }}</span>
                        <span>{{ __('tabungan.remaining') }} Rp {{ number_format(max(0, $item->target_jumlah - ($item->terkumpul ?? 0)), 0, ',', '.') }}</span>
                    </div>

                    @if($item->status !== 'selesai')
                        <button class="btn btn-sm btn-outline-primary w-100" type="button"
                                data-bs-toggle="collapse" data-bs-target="#setor{{ $item->id }}">
                            <i class="bi bi-plus-circle me-1"></i>{{ __('tabungan.top_up') }}
                        </button>
                        <div class="collapse mt-3" id="setor{{ $item->id }}">
                            <form method="POST" action="{{ route('tabungan.setor', $item) }}">
                                @csrf
                                <div class="mb-2">
                                    <select name="sumber_transaksi_id" required class="form-select form-select-sm">
                                        <option value="">{{ __('tabungan.source') }}</option>
                                        @foreach($sumberTransaksi as $s)
                                            <option value="{{ $s->id }}">{{ $s->nama }} (Rp {{ number_format($s->saldo ?? 0, 0, ',', '.') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <input type="text" inputmode="numeric" name="jumlah" placeholder="{{ __('tabungan.amount') }}" required
                                           class="form-control form-control-sm flex-grow-1 currency-input">
                                    <button type="submit" class="btn btn-sm btn-success">{{ __('tabungan.top_up') }}</button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="text-center py-2">
                            <span class="text-success small fw-medium">
                                <i class="bi bi-check-circle-fill me-1"></i>{{ __('tabungan.achieved') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body py-5 text-center">
                    <i class="bi bi-piggy-bank fs-1 d-block mb-2 text-muted opacity-25"></i>
                    <p class="text-muted small mb-2">{{ __('tabungan.no_savings') }}</p>
                    <a href="{{ route('tabungan.create') }}" class="small text-primary fw-medium text-decoration-none">
                        + {{ __('tabungan.add') }}
                    </a>
                </div>
            </div>
        </div>
    @endforelse

</div>
@endsection
