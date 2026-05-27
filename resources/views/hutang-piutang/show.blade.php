@extends('layouts.app')

@section('title', __('hutang.detail'))
@section('page-title', ucfirst($hutangPiutang->jenis))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible py-2 small mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible py-2 small mb-3">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 align-items-start">

        {{-- ===== KOLOM KIRI: Info ===== --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">

                    {{-- Badges + actions --}}
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge rounded-pill {{ $hutangPiutang->jenis === 'hutang' ? 'bg-danger' : 'bg-success' }}">
                                {{ ucfirst($hutangPiutang->jenis) }}
                            </span>
                            <span class="badge rounded-pill {{ $hutangPiutang->status === 'lunas' ? 'bg-secondary' : 'bg-primary' }}">
                                {{ $hutangPiutang->status === 'lunas' ? __('hutang.paid') : __('hutang.outstanding') }}
                            </span>
                            @if($hutangPiutang->tipe_pembayaran === 'cicilan')
                                <span class="badge rounded-pill bg-info text-dark" style="font-size:.62rem;">
                                    {{ __('hutang.payment_installment') }}
                                </span>
                            @endif
                        </div>
                        <div class="d-flex gap-3 ms-2 flex-shrink-0">
                            <a href="{{ route('hutang-piutang.edit', $hutangPiutang) }}"
                               class="small text-primary text-decoration-none">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('hutang-piutang.destroy', $hutangPiutang) }}"
                                  onsubmit="return confirm('{{ __('hutang.delete_confirm') }}')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" style="font-size:.78rem;">
                                    {{ __('messages.delete') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Nama --}}
                    <h6 class="fw-bold mb-1">{{ $hutangPiutang->nama_pihak }}</h6>
                    @if($hutangPiutang->keterangan)
                        <p class="text-muted small mb-3">{{ $hutangPiutang->keterangan }}</p>
                    @endif

                    {{-- Stats 3 kolom --}}
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center">
                            <div class="small text-muted mb-1">{{ __('laporan.total') }}</div>
                            <div class="fw-bold small">Rp {{ number_format($hutangPiutang->jumlah_total, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-4 text-center border-start border-end">
                            <div class="small text-muted mb-1">{{ __('hutang.paid') }}</div>
                            <div class="fw-bold small text-success">Rp {{ number_format($hutangPiutang->jumlah_terbayar, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="small text-muted mb-1">{{ __('hutang.outstanding') }}</div>
                            <div class="fw-bold small {{ $hutangPiutang->jenis === 'hutang' ? 'text-danger' : 'text-primary' }}">
                                Rp {{ number_format($hutangPiutang->sisa, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    {{-- Progress --}}
                    @php $persen = $hutangPiutang->jumlah_total > 0
                        ? min(100, ($hutangPiutang->jumlah_terbayar / $hutangPiutang->jumlah_total) * 100)
                        : 0; @endphp
                    <div class="progress mb-1" style="height:5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width:{{ $persen }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>{{ number_format($persen, 0) }}% {{ __('hutang.paid') }}</span>
                        @if($hutangPiutang->tanggal_jatuh_tempo)
                            <span>{{ __('hutang.due_date') }}: {{ $hutangPiutang->tanggal_jatuh_tempo->translatedFormat('d M Y') }}</span>
                        @endif
                    </div>

                    {{-- Info cicilan --}}
                    @if($hutangPiutang->tipe_pembayaran === 'cicilan' && $hutangPiutang->status !== 'lunas')
                        <div class="mt-3 pt-3 border-top d-flex align-items-start gap-2 small text-info">
                            <i class="bi bi-info-circle flex-shrink-0 mt-1"></i>
                            <span>
                                {{ __('hutang.next_installment') }}:
                                <strong>Rp {{ number_format($hutangPiutang->jumlah_cicilan, 0, ',', '.') }}</strong>
                                — {{ optional($hutangPiutang->jadwal_cicilan_berikutnya)->translatedFormat('d M Y') ?? '-' }}
                                ({{ __('hutang.freq_' . $hutangPiutang->frekuensi_cicilan) }})
                            </span>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ===== KOLOM KANAN: Form + Riwayat ===== --}}
        <div class="col-12 col-lg-7">

            @if($hutangPiutang->status !== 'lunas')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-semibold mb-3">
                            {{ $hutangPiutang->jenis === 'hutang'
                                ? __('hutang.add_payment_debt')
                                : __('hutang.add_payment_credit') }}
                        </h6>
                        <form method="POST" action="{{ route('hutang-piutang.bayar', $hutangPiutang) }}">
                            @csrf
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium mb-1">
                                        {{ __('hutang.source') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="sumber_transaksi_id" required class="form-select form-select-sm">
                                        <option value="">— {{ __('hutang.source') }} —</option>
                                        @foreach($sumberTransaksi as $s)
                                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium mb-1">
                                        {{ __('hutang.amount') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" inputmode="numeric" name="jumlah" required
                                               value="{{ $hutangPiutang->tipe_pembayaran === 'cicilan'
                                                   ? $hutangPiutang->jumlah_cicilan
                                                   : $hutangPiutang->sisa }}"
                                               placeholder="0" class="form-control currency-input">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium mb-1">{{ __('messages.date') }}</label>
                                    <input type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}"
                                           class="form-control form-control-sm">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-medium mb-1">{{ __('hutang.notes') }}</label>
                                    <input type="text" name="keterangan" placeholder="{{ __('hutang.notes') }}"
                                           class="form-control form-control-sm">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm fw-medium px-4">
                                {{ __('hutang.mark_paid') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Riwayat --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom py-2 px-3">
                    <h6 class="fw-semibold mb-0 small">{{ __('laporan.transactions') }}</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($riwayat ?? [] as $r)
                        <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="small fw-medium text-truncate">
                                    {{ $r->keterangan ?: __('hutang.payment') }}
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    {{ optional($r->tanggal)->translatedFormat('d M Y')
                                        ?? $r->created_at->translatedFormat('d M Y') }}
                                    @if($r->sumberTransaksi)
                                        · {{ $r->sumberTransaksi->nama }}
                                    @endif
                                </div>
                            </div>
                            <div class="fw-semibold text-success small flex-shrink-0">
                                Rp {{ number_format($r->jumlah, 0, ',', '.') }}
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <a href="{{ route('pembayaran.edit', $r) }}"
                                   class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:.7rem;">
                                    {{ __('messages.edit') }}
                                </a>
                                <form method="POST" action="{{ route('pembayaran.destroy', $r) }}"
                                      onsubmit="return confirm('{{ __('hutang.delete_payment_confirm') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-outline-danger btn-sm py-0 px-2"
                                            style="font-size:.7rem;">
                                        {{ __('messages.delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="py-3 text-center text-muted small">{{ __('messages.no_data') }}</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</div>
</div>
@endsection
