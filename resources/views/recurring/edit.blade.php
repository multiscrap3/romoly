@extends('layouts.app')

@section('title', __('recurring.edit'))
@section('page-title', __('recurring.edit'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('recurring.update', $recurring) }}">
                @csrf @method('PUT')

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label small fw-medium text-muted">{{ __('recurring.type') }}</label>
                    <div class="form-control bg-light text-capitalize" style="pointer-events:none;">{{ $recurring->jenis }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.name') }}</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan', $recurring->keterangan) }}"
                           class="form-control @error('keterangan') is-invalid @enderror">
                    @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.amount') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="jumlah" value="{{ old('jumlah', $recurring->jumlah) }}"
                               class="form-control currency-input @error('jumlah') is-invalid @enderror">
                        @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.frequency') }}</label>
                    <select name="frekuensi" class="form-select">
                        <option value="harian"   {{ old('frekuensi', $recurring->frekuensi) === 'harian' ? 'selected' : '' }}>{{ __('recurring.daily') }}</option>
                        <option value="mingguan" {{ old('frekuensi', $recurring->frekuensi) === 'mingguan' ? 'selected' : '' }}>{{ __('recurring.weekly') }}</option>
                        <option value="bulanan"  {{ old('frekuensi', $recurring->frekuensi) === 'bulanan' ? 'selected' : '' }}>{{ __('recurring.monthly') }}</option>
                        <option value="tahunan"  {{ old('frekuensi', $recurring->frekuensi) === 'tahunan' ? 'selected' : '' }}>{{ __('recurring.yearly') }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.category') }}</label>
                    <select name="kategori_id" class="form-select">
                        <option value="">{{ __('recurring.category') }}</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id', $recurring->kategori_id) == $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama }} ({{ ucfirst($kat->jenis) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.source') }}</label>
                    <select name="sumber_transaksi_id" class="form-select">
                        <option value="">{{ __('recurring.source') }}</option>
                        @foreach($sumberTransaksi as $s)
                            <option value="{{ $s->id }}" {{ old('sumber_transaksi_id', $recurring->sumber_transaksi_id) == $s->id ? 'selected' : '' }}>
                                {{ $s->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('recurring.end_date') }}</label>
                    <input type="date" name="tanggal_selesai"
                           value="{{ old('tanggal_selesai', optional($recurring->tanggal_selesai)->format('Y-m-d')) }}"
                           class="form-control">
                    <div class="form-text">Kosongkan jika tidak ada tanggal selesai.</div>
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('recurring.save') }}</button>
                    <a href="{{ route('recurring.index') }}" class="btn btn-outline-secondary flex-fill">{{ __('recurring.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

