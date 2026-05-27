@extends('layouts.app')

@section('title', __('hutang.add'))
@section('page-title', __('hutang.add'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">
            <form method="POST" action="{{ route('hutang-piutang.store') }}">
                @csrf

                @if($errors->any())
                    <div class="alert alert-danger py-2 mb-4">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.type') }} <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check border rounded p-3 {{ old('jenis') !== 'piutang' ? 'border-danger bg-danger bg-opacity-10' : 'border-2' }}" style="cursor:pointer;">
                                <input class="form-check-input" type="radio" name="jenis" value="hutang" id="jenisHutang"
                                       {{ old('jenis', 'hutang') === 'hutang' ? 'checked' : '' }}>
                                <label class="form-check-label w-100" for="jenisHutang" style="cursor:pointer;">
                                    <div class="fw-medium small">{{ __('hutang.debt') }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">{{ __('hutang.add_debt') }}</div>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check border rounded p-3 {{ old('jenis') === 'piutang' ? 'border-success bg-success bg-opacity-10' : 'border-2' }}" style="cursor:pointer;">
                                <input class="form-check-input" type="radio" name="jenis" value="piutang" id="jenisPiutang"
                                       {{ old('jenis') === 'piutang' ? 'checked' : '' }}>
                                <label class="form-check-label w-100" for="jenisPiutang" style="cursor:pointer;">
                                    <div class="fw-medium small">{{ __('hutang.credit') }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">{{ __('hutang.add_credit') }}</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.counterparty') }} <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pihak" value="{{ old('nama_pihak') }}" required
                           placeholder="{{ __('hutang.counterparty_ph') }}"
                           class="form-control @error('nama_pihak') is-invalid @enderror">
                    @error('nama_pihak')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.amount') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" name="jumlah" value="{{ old('jumlah') }}" required
                               placeholder="0"
                               class="form-control currency-input @error('jumlah') is-invalid @enderror">
                        @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('messages.date') }}</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', now()->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">{{ __('hutang.due_date') }}</label>
                        <input type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo') }}" class="form-control">
                    </div>
                </div>

                {{-- Mode Pembayaran --}}
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.payment_mode') }} <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check border rounded p-3" style="cursor:pointer;" id="card-sekali">
                                <input class="form-check-input" type="radio" name="tipe_pembayaran" value="sekali"
                                       id="tipeSekali" {{ old('tipe_pembayaran', 'sekali') === 'sekali' ? 'checked' : '' }}>
                                <label class="form-check-label w-100" for="tipeSekali" style="cursor:pointer;">
                                    <div class="fw-medium small">{{ __('hutang.payment_once') }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">{{ __('hutang.payment_once_desc') }}</div>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check border rounded p-3" style="cursor:pointer;" id="card-cicilan">
                                <input class="form-check-input" type="radio" name="tipe_pembayaran" value="cicilan"
                                       id="tipeCicilan" {{ old('tipe_pembayaran') === 'cicilan' ? 'checked' : '' }}>
                                <label class="form-check-label w-100" for="tipeCicilan" style="cursor:pointer;">
                                    <div class="fw-medium small">{{ __('hutang.payment_installment') }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">{{ __('hutang.payment_installment_desc') }}</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Detail cicilan (tampil jika cicilan dipilih) --}}
                <div id="cicilan-fields" class="mb-3 {{ old('tipe_pembayaran') === 'cicilan' ? '' : 'd-none' }}">
                    <div class="row g-3 p-3 border rounded bg-light">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">{{ __('hutang.installment_amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" name="jumlah_cicilan"
                                       value="{{ old('jumlah_cicilan') }}" placeholder="0"
                                       class="form-control currency-input @error('jumlah_cicilan') is-invalid @enderror">
                                @error('jumlah_cicilan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">{{ __('hutang.installment_freq') }} <span class="text-danger">*</span></label>
                            <select name="frekuensi_cicilan"
                                    class="form-select form-select-sm @error('frekuensi_cicilan') is-invalid @enderror">
                                <option value="">—</option>
                                <option value="mingguan" {{ old('frekuensi_cicilan') === 'mingguan' ? 'selected' : '' }}>{{ __('hutang.freq_weekly') }}</option>
                                <option value="bulanan"  {{ old('frekuensi_cicilan') === 'bulanan'  ? 'selected' : '' }}>{{ __('hutang.freq_monthly') }}</option>
                                <option value="tahunan"  {{ old('frekuensi_cicilan') === 'tahunan'  ? 'selected' : '' }}>{{ __('hutang.freq_yearly') }}</option>
                            </select>
                            @error('frekuensi_cicilan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('hutang.notes') }}</label>
                    <textarea name="keterangan" rows="3" placeholder="{{ __('hutang.notes') }}"
                              class="form-control">{{ old('keterangan') }}</textarea>
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('hutang.save') }}</button>
                    <a href="{{ route('hutang-piutang.index') }}" class="btn btn-outline-secondary flex-fill">{{ __('hutang.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="tipe_pembayaran"]');
    const fields = document.getElementById('cicilan-fields');

    function toggle() {
        const isCicilan = document.querySelector('input[name="tipe_pembayaran"]:checked')?.value === 'cicilan';
        fields.classList.toggle('d-none', !isCicilan);
    }

    radios.forEach(r => r.addEventListener('change', toggle));
});
</script>
@endpush
