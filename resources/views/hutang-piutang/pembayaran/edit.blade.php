@extends('layouts.app')

@section('title', __('hutang.edit_payment'))
@section('page-title', __('hutang.edit_payment'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">

            @if(session('error'))
                <div class="alert alert-danger py-2 mb-4 small">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger py-2 mb-4">
                    <ul class="mb-0 ps-3 small">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-4 p-3 bg-light rounded">
                <div class="small text-muted mb-1">
                    <span class="badge {{ $pembayaran->hutangPiutang->jenis === 'hutang' ? 'bg-danger' : 'bg-success' }} rounded-pill me-1">
                        {{ ucfirst($pembayaran->hutangPiutang->jenis) }}
                    </span>
                    {{ $pembayaran->hutangPiutang->nama_pihak }}
                </div>
                <div class="fw-semibold">Total: Rp {{ number_format($pembayaran->hutangPiutang->jumlah_total, 0, ',', '.') }}</div>
                <div class="small text-muted">Sisa: Rp {{ number_format($pembayaran->hutangPiutang->sisa + $pembayaran->jumlah, 0, ',', '.') }}</div>
            </div>

            <form method="POST" action="{{ route('pembayaran.update', $pembayaran) }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.source') }} <span class="text-danger">*</span></label>
                    <select name="sumber_transaksi_id" required
                            class="form-select @error('sumber_transaksi_id') is-invalid @enderror">
                        <option value="">— {{ __('hutang.source') }} —</option>
                        @foreach($sumberTransaksi as $s)
                            <option value="{{ $s->id }}"
                                {{ old('sumber_transaksi_id', $pembayaran->sumber_transaksi_id) == $s->id ? 'selected' : '' }}>
                                {{ $s->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('sumber_transaksi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.amount') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="jumlah" required
                               value="{{ old('jumlah', $pembayaran->jumlah) }}"
                               class="form-control currency-input @error('jumlah') is-invalid @enderror">
                        @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('messages.date') }}</label>
                    <input type="date" name="tanggal" required
                           value="{{ old('tanggal', optional($pembayaran->tanggal)->format('Y-m-d')) }}"
                           class="form-control @error('tanggal') is-invalid @enderror">
                    @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('hutang.notes') }}</label>
                    <input type="text" name="keterangan" placeholder="{{ __('hutang.notes') }}"
                           value="{{ old('keterangan', $pembayaran->keterangan) }}"
                           class="form-control">
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('hutang.save') }}</button>
                    <a href="{{ route('hutang-piutang.show', $pembayaran->hutang_piutang_id) }}"
                       class="btn btn-outline-secondary flex-fill">{{ __('hutang.cancel') }}</a>
                </div>
            </form>

        </div>
    </div>
</div>
</div>
@endsection
