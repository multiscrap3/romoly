@extends('layouts.app')

@section('title', __('anggaran.edit'))
@section('page-title', __('anggaran.edit'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('anggaran.update', $anggaran) }}">
                @csrf @method('PUT')

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('anggaran.category') }}</label>
                    <select name="kategori_id" class="form-select">
                        @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id', $anggaran->kategori_id) == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('laporan.month') }}</label>
                        <select name="bulan" class="form-select">
                            @foreach(range(1, 12) as $b)
                                <option value="{{ $b }}" {{ old('bulan', $anggaran->bulan) == $b ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $b, 1)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('laporan.year') }}</label>
                        <select name="tahun" class="form-select">
                            @foreach(range(now()->year + 1, now()->year - 2) as $y)
                                <option value="{{ $y }}" {{ old('tahun', $anggaran->tahun) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('anggaran.amount') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="jumlah" value="{{ old('jumlah', $anggaran->jumlah) }}"
                               class="form-control currency-input @error('jumlah') is-invalid @enderror">
                        @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('anggaran.save') }}</button>
                    <a href="{{ route('anggaran.index', ['bulan' => $anggaran->bulan, 'tahun' => $anggaran->tahun]) }}"
                       class="btn btn-outline-secondary flex-fill">{{ __('anggaran.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

