@extends('layouts.app')

@section('title', __('sumber.add'))
@section('page-title', __('sumber.add'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('sumber-transaksi.store') }}">
                @csrf

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('sumber.name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required maxlength="255"
                           placeholder="{{ __('sumber.name_ph') }}"
                           class="form-control @error('nama') is-invalid @enderror">
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('sumber.type') }} <span class="text-danger">*</span></label>
                    <select name="jenis" required class="form-select @error('jenis') is-invalid @enderror">
                        <option value="">{{ __('sumber.type') }}</option>
                        <option value="cash"         {{ old('jenis') === 'cash'         ? 'selected' : '' }}>{{ __('sumber.cash') }}</option>
                        <option value="bank"         {{ old('jenis') === 'bank'         ? 'selected' : '' }}>{{ __('sumber.bank') }}</option>
                        <option value="e-wallet"     {{ old('jenis') === 'e-wallet'     ? 'selected' : '' }}>{{ __('sumber.ewallet') }}</option>
                        <option value="kartu_kredit" {{ old('jenis') === 'kartu_kredit' ? 'selected' : '' }}>{{ __('sumber.other') }}</option>
                        <option value="investasi"    {{ old('jenis') === 'investasi'    ? 'selected' : '' }}>{{ __('sumber.investment') }}</option>
                        <option value="lainnya"      {{ old('jenis') === 'lainnya'      ? 'selected' : '' }}>{{ __('sumber.other') }}</option>
                    </select>
                    @error('jenis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('sumber.initial_balance') }}</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="saldo" value="{{ old('saldo', 0) }}"
                               class="form-control currency-input @error('saldo') is-invalid @enderror">
                        @error('saldo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('sumber.save') }}</button>
                    <a href="{{ route('sumber-transaksi.index') }}" class="btn btn-outline-secondary flex-fill">{{ __('sumber.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

