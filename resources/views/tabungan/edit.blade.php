@extends('layouts.app')

@section('title', __('tabungan.edit'))
@section('page-title', __('tabungan.edit'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('tabungan.update', $tabungan) }}">
                @csrf @method('PUT')

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('tabungan.name') }}</label>
                    <input type="text" name="nama" value="{{ old('nama', $tabungan->nama) }}" required
                           class="form-control @error('nama') is-invalid @enderror">
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('tabungan.target') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="target" value="{{ old('target', $tabungan->target_jumlah) }}"
                               class="form-control currency-input @error('target') is-invalid @enderror">
                        @error('target')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('tabungan.deadline') }}</label>
                    <input type="date" name="tanggal_target"
                           value="{{ old('tanggal_target', optional($tabungan->target_tanggal)->format('Y-m-d')) }}"
                           class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('tabungan.notes') }}</label>
                    <textarea name="keterangan" rows="3"
                              class="form-control">{{ old('keterangan', $tabungan->deskripsi) }}</textarea>
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('tabungan.save') }}</button>
                    <a href="{{ route('tabungan.show', $tabungan) }}" class="btn btn-outline-secondary flex-fill">{{ __('tabungan.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

