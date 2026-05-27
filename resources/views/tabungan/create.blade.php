@extends('layouts.app')

@section('title', __('tabungan.add'))
@section('page-title', __('tabungan.add'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('tabungan.store') }}">
                @csrf

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('tabungan.name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required
                           placeholder="{{ __('tabungan.name_ph') }}"
                           class="form-control @error('nama') is-invalid @enderror">
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('tabungan.target') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="target" value="{{ old('target') }}" required
                               placeholder="0"
                               class="form-control currency-input @error('target') is-invalid @enderror">
                        @error('target')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('tabungan.deadline') }}</label>
                    <input type="date" name="tanggal_target" value="{{ old('tanggal_target') }}"
                           class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('tabungan.notes') }}</label>
                    <textarea name="keterangan" rows="3" placeholder="{{ __('tabungan.notes') }}"
                              class="form-control">{{ old('keterangan') }}</textarea>
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('tabungan.save') }}</button>
                    <a href="{{ route('tabungan.index') }}" class="btn btn-outline-secondary flex-fill">{{ __('tabungan.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

