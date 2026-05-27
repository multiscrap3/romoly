@extends('layouts.app')

@section('title', __('recurring.add'))
@section('page-title', __('recurring.add'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('recurring.store') }}">
                @csrf

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.type') }} <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="jenis" id="jenisRutin1" value="pengeluaran"
                               {{ old('jenis', 'pengeluaran') === 'pengeluaran' ? 'checked' : '' }}>
                        <label class="btn btn-outline-danger" for="jenisRutin1">
                            <i class="bi bi-arrow-down-circle me-1"></i>{{ __('recurring.expense') }}
                        </label>

                        <input type="radio" class="btn-check" name="jenis" id="jenisRutin2" value="pemasukan"
                               {{ old('jenis') === 'pemasukan' ? 'checked' : '' }}>
                        <label class="btn btn-outline-success" for="jenisRutin2">
                            <i class="bi bi-arrow-up-circle me-1"></i>{{ __('recurring.income') }}
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}" required
                           placeholder="{{ __('recurring.name_ph') }}"
                           class="form-control @error('keterangan') is-invalid @enderror">
                    @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.amount') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="jumlah" value="{{ old('jumlah') }}" required
                               placeholder="0"
                               class="form-control currency-input @error('jumlah') is-invalid @enderror">
                        @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.frequency') }} <span class="text-danger">*</span></label>
                    <select name="frekuensi" required class="form-select">
                        <option value="harian"   {{ old('frekuensi') === 'harian' ? 'selected' : '' }}>{{ __('recurring.daily') }}</option>
                        <option value="mingguan" {{ old('frekuensi') === 'mingguan' ? 'selected' : '' }}>{{ __('recurring.weekly') }}</option>
                        <option value="bulanan"  {{ old('frekuensi', 'bulanan') === 'bulanan' ? 'selected' : '' }}>{{ __('recurring.monthly') }}</option>
                        <option value="tahunan"  {{ old('frekuensi') === 'tahunan' ? 'selected' : '' }}>{{ __('recurring.yearly') }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.category') }} <span class="text-danger">*</span></label>
                    <select name="kategori_id" required class="form-select @error('kategori_id') is-invalid @enderror">
                        <option value="">{{ __('recurring.category') }}</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama }} ({{ ucfirst($kat->jenis) }})
                            </option>
                        @endforeach
                    </select>
                    @error('kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('recurring.source') }} <span class="text-danger">*</span></label>
                    <select name="sumber_transaksi_id" required class="form-select @error('sumber_transaksi_id') is-invalid @enderror">
                        <option value="">{{ __('recurring.source') }}</option>
                        @foreach($sumberTransaksi as $s)
                            <option value="{{ $s->id }}" {{ old('sumber_transaksi_id') == $s->id ? 'selected' : '' }}>{{ $s->nama }}</option>
                        @endforeach
                    </select>
                    @error('sumber_transaksi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('recurring.start_date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai"
                               value="{{ old('tanggal_mulai', now()->format('Y-m-d')) }}" required
                               class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('recurring.end_date') }} <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" class="form-control">
                    </div>
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

