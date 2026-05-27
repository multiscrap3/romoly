@extends('layouts.app')

@section('title', __('anggaran.add'))
@section('page-title', __('anggaran.add'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('anggaran.store') }}">
                @csrf

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('anggaran.category') }} <span class="text-danger">*</span></label>
                    <select name="kategori_id" required
                            class="form-select @error('kategori_id') is-invalid @enderror">
                        <option value="">{{ __('anggaran.category') }}</option>
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                    @error('kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('laporan.month') }} <span class="text-danger">*</span></label>
                        <select name="bulan" required class="form-select @error('bulan') is-invalid @enderror">
                            @foreach(range(1, 12) as $b)
                                <option value="{{ $b }}" {{ old('bulan', now()->month) == $b ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $b, 1)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                        @error('bulan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('laporan.year') }} <span class="text-danger">*</span></label>
                        <select name="tahun" required class="form-select @error('tahun') is-invalid @enderror">
                            @foreach(range(now()->year, now()->year - 2) as $y)
                                <option value="{{ $y }}" {{ old('tahun', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                            <option value="{{ now()->year + 1 }}" {{ old('tahun') == now()->year + 1 ? 'selected' : '' }}>{{ now()->year + 1 }}</option>
                        </select>
                        @error('tahun')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('anggaran.amount') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="jumlah" value="{{ old('jumlah') }}" required
                               placeholder="0"
                               class="form-control currency-input @error('jumlah') is-invalid @enderror">
                        @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('anggaran.save') }}</button>
                    <a href="{{ route('anggaran.index') }}" class="btn btn-outline-secondary flex-fill">{{ __('anggaran.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

