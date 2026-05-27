@extends('layouts.app')

@section('title', __('tabungan.detail'))
@section('page-title', __('tabungan.detail'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-9">

    {{-- Summary card --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div>
                    <h5 class="fw-bold mb-1">{{ $tabungan->nama }}</h5>
                    @if($tabungan->deskripsi)
                        <p class="text-muted small mb-0">{{ $tabungan->deskripsi }}</p>
                    @endif
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('tabungan.edit', $tabungan) }}" class="small text-primary text-decoration-none">{{ __('messages.edit') }}</a>
                    <form method="POST" action="{{ route('tabungan.destroy', $tabungan) }}"
                          onsubmit="return confirm('{{ __('tabungan.delete_confirm') }}')" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0" style="font-size:.78rem;">{{ __('messages.delete') }}</button>
                    </form>
                </div>
            </div>

            @php
                $persen = $tabungan->target_jumlah > 0 ? min(100, ($tabungan->terkumpul / $tabungan->target_jumlah) * 100) : 0;
            @endphp

            <div class="row g-3 text-center mb-4">
                <div class="col-4">
                    <div class="text-muted mb-1" style="font-size:.72rem;">{{ __('tabungan.saved') }}</div>
                    <div class="fw-bold text-primary fs-5">Rp {{ number_format($tabungan->terkumpul, 0, ',', '.') }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted mb-1" style="font-size:.72rem;">{{ __('tabungan.target') }}</div>
                    <div class="fw-bold fs-5">Rp {{ number_format($tabungan->target_jumlah, 0, ',', '.') }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted mb-1" style="font-size:.72rem;">{{ __('tabungan.remaining') }}</div>
                    <div class="fw-bold fs-5">Rp {{ number_format($tabungan->sisa_target, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="progress mb-2" style="height:12px;border-radius:6px;">
                <div class="progress-bar {{ $persen >= 100 ? 'bg-success' : 'bg-primary' }}" role="progressbar"
                     style="width:{{ $persen }}%"></div>
            </div>
            <div class="d-flex align-items-center justify-content-between small text-muted">
                <span>{{ number_format($persen, 1) }}% tercapai</span>
                @if($tabungan->target_tanggal)
                    <span>Target: {{ $tabungan->target_tanggal->translatedFormat('d M Y') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Setor / Tarik forms --}}
    @if($tabungan->status !== 'selesai')
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">{{ __('tabungan.top_up') }}</h6>
                        <form method="POST" action="{{ route('tabungan.setor', $tabungan) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-medium">{{ __('tabungan.source') }}</label>
                                <select name="sumber_transaksi_id" required class="form-select form-select-sm">
                                    <option value="">{{ __('tabungan.source') }}</option>
                                    @foreach($sumberTransaksi as $s)
                                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-medium">{{ __('tabungan.amount') }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" inputmode="numeric" name="jumlah" required placeholder="0" class="form-control currency-input">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium">{{ __('messages.date') }}</label>
                                <input type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm">
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">{{ __('tabungan.top_up') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3">{{ __('tabungan.withdraw') }}</h6>
                        <form method="POST" action="{{ route('tabungan.tarik', $tabungan) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-medium">{{ __('tabungan.source') }}</label>
                                <select name="sumber_transaksi_id" required class="form-select form-select-sm">
                                    <option value="">{{ __('tabungan.source') }}</option>
                                    @foreach($sumberTransaksi as $s)
                                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-medium">{{ __('tabungan.amount') }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" inputmode="numeric" name="jumlah" required placeholder="0" class="form-control currency-input">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-medium">{{ __('messages.date') }}</label>
                                <input type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-medium">{{ __('tabungan.notes') }}</label>
                                <input type="text" name="keterangan" placeholder="{{ __('tabungan.notes') }}" class="form-control form-control-sm">
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm w-100"
                                    onclick="return confirm('{{ __('messages.confirm_delete') }}')">{{ __('tabungan.withdraw') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Riwayat --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
            <h6 class="fw-semibold mb-0">{{ __('laporan.transactions') }}</h6>
        </div>
        <div class="card-body p-0">
            @forelse($riwayat ?? [] as $r)
                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                         style="width:36px;height:36px;
                         background:{{ $r->jenis === 'setor' ? 'rgba(16,185,129,.15)' : 'rgba(249,115,22,.15)' }};">
                        @if($r->jenis === 'setor')
                            <i class="bi bi-arrow-down-circle text-success"></i>
                        @else
                            <i class="bi bi-arrow-up-circle text-warning"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="small fw-medium text-capitalize">{{ $r->jenis }}</div>
                        <div class="text-muted" style="font-size:.72rem;">
                            {{ optional($r->tanggal)->translatedFormat('d M Y') ?? $r->created_at->translatedFormat('d M Y') }}
                        </div>
                    </div>
                    <div class="small fw-semibold {{ $r->jenis === 'setor' ? 'text-success' : 'text-warning' }}">
                        {{ $r->jenis === 'setor' ? '+' : '-' }}Rp {{ number_format($r->jumlah, 0, ',', '.') }}
                    </div>
                </div>
            @empty
                <div class="py-4 text-center text-muted small">{{ __('messages.no_data') }}</div>
            @endforelse
        </div>
    </div>

</div>
</div>
@endsection
