@extends('layouts.app')

@section('title', __('sumber.edit'))
@section('page-title', __('sumber.edit'))

@section('content')
@php
    $iconList = [
        'bi-bank','bi-building','bi-building-fill',
        'bi-cash-stack','bi-cash-coin','bi-cash',
        'bi-credit-card','bi-credit-card-2-front','bi-credit-card-fill',
        'bi-phone','bi-phone-fill',
        'bi-wallet2','bi-wallet-fill',
        'bi-piggy-bank','bi-piggy-bank-fill',
        'bi-graph-up','bi-graph-up-arrow',
        'bi-currency-dollar','bi-currency-exchange',
        'bi-safe','bi-safe2',
        'bi-briefcase','bi-briefcase-fill',
        'bi-coin','bi-gem',
        'bi-house-fill','bi-shop',
        'bi-bag-fill','bi-star-fill',
    ];
    $currentIcon  = old('icon', $sumberTransaksi->icon ?? 'bi-wallet2');
    $currentWarna = old('warna', $sumberTransaksi->warna ?? '#6c757d');
@endphp

<div class="row justify-content-center">
<div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4 p-md-5">

            @if($errors->any())
            <div class="alert alert-danger py-2 mb-4">
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('sumber-transaksi.update', $sumberTransaksi) }}" novalidate>
                @csrf @method('PUT')

                <div class="row g-3">
                    {{-- Nama --}}
                    <div class="col-12">
                        <label class="form-label fw-medium">
                            {{ __('sumber.name') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama" id="editNama"
                               required maxlength="100" autocomplete="off"
                               value="{{ old('nama', $sumberTransaksi->nama) }}"
                               class="form-control @error('nama') is-invalid @enderror">
                        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Jenis + Saldo (read-only) --}}
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('sumber.type') }} <span class="text-danger">*</span></label>
                        <select name="jenis" id="editJenis" required
                                class="form-select @error('jenis') is-invalid @enderror">
                            <option value="cash"         {{ old('jenis', $sumberTransaksi->jenis) === 'cash'         ? 'selected' : '' }}>{{ __('sumber.cash') }}</option>
                            <option value="bank"         {{ old('jenis', $sumberTransaksi->jenis) === 'bank'         ? 'selected' : '' }}>{{ __('sumber.bank') }}</option>
                            <option value="e-wallet"     {{ old('jenis', $sumberTransaksi->jenis) === 'e-wallet'     ? 'selected' : '' }}>{{ __('sumber.ewallet') }}</option>
                            <option value="kartu_kredit" {{ old('jenis', $sumberTransaksi->jenis) === 'kartu_kredit' ? 'selected' : '' }}>{{ __('sumber.card_credit') }}</option>
                            <option value="investasi"    {{ old('jenis', $sumberTransaksi->jenis) === 'investasi'    ? 'selected' : '' }}>{{ __('sumber.investment') }}</option>
                            <option value="lainnya"      {{ old('jenis', $sumberTransaksi->jenis) === 'lainnya'      ? 'selected' : '' }}>{{ __('sumber.other') }}</option>
                        </select>
                        @error('jenis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('sumber.current_balance') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" readonly
                                   value="{{ number_format($sumberTransaksi->saldo_saat_ini, 0, ',', '.') }}"
                                   class="form-control bg-light text-muted">
                        </div>
                        <div class="form-text small text-muted">{{ __('sumber.notes') }}</div>
                    </div>

                    {{-- Nomor Rekening + Nama Bank --}}
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('sumber.account_number') }}</label>
                        <input type="text" name="nomor_rekening" maxlength="50"
                               placeholder="Opsional"
                               value="{{ old('nomor_rekening', $sumberTransaksi->nomor_rekening) }}"
                               class="form-control">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('sumber.bank_name') }}</label>
                        <input type="text" name="nama_bank" maxlength="100"
                               placeholder="Opsional"
                               value="{{ old('nama_bank', $sumberTransaksi->nama_bank) }}"
                               class="form-control">
                    </div>

                    {{-- Warna + Icon --}}
                    <div class="col-12">
                        <div class="d-flex gap-4 flex-wrap align-items-start">
                            <div class="flex-shrink-0">
                                <label class="form-label fw-medium d-block">{{ __('sumber.color') }}</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="warna" id="editWarna"
                                           value="{{ $currentWarna }}"
                                           class="form-control form-control-color"
                                           style="width:46px;height:38px;">
                                    <code class="small text-muted" id="editWarnaText">{{ $currentWarna }}</code>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label fw-medium">{{ __('sumber.icon_pick') }}</label>
                                <input type="hidden" name="icon" id="editIcon" value="{{ $currentIcon }}">
                                <div class="d-flex flex-wrap gap-1" id="editIconGrid">
                                    @foreach($iconList as $ico)
                                    <button type="button"
                                            class="btn btn-sm p-0 d-flex align-items-center justify-content-center icon-pick-btn
                                                   {{ $currentIcon === $ico ? 'btn-primary' : 'btn-light' }}"
                                            data-icon="{{ $ico }}"
                                            title="{{ $ico }}"
                                            style="width:38px;height:38px;">
                                        <i class="bi {{ $ico }}"></i>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Live Preview --}}
                    <div class="col-12">
                        <label class="form-label fw-medium">Preview</label>
                        <div class="d-flex align-items-center gap-3 rounded-2 p-3 border"
                             style="background:#f8fafc;">
                            <div id="editPreviewBox"
                                 class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:52px;height:52px;background:{{ $currentWarna }}22;color:{{ $currentWarna }};">
                                <i class="bi {{ $currentIcon }} fs-4" id="editPreviewIcon"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" id="editPreviewNama">
                                    {{ old('nama', $sumberTransaksi->nama) }}
                                </div>
                                <div class="text-muted small">
                                    @switch(old('jenis', $sumberTransaksi->jenis))
                                        @case('bank')         {{ __('sumber.bank') }}        @break
                                        @case('cash')         {{ __('sumber.cash') }}        @break
                                        @case('e-wallet')     {{ __('sumber.ewallet') }}     @break
                                        @case('kartu_kredit') {{ __('sumber.card_credit') }} @break
                                        @case('investasi')    {{ __('sumber.investment') }}  @break
                                        @default              {{ __('sumber.other') }}
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 pt-4">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">
                        <i class="bi bi-check-lg me-1"></i>{{ __('sumber.save') }}
                    </button>
                    <a href="{{ route('sumber-transaksi.index') }}"
                       class="btn btn-outline-secondary flex-fill">{{ __('sumber.cancel') }}</a>
                </div>
            </form>

        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const grid    = document.getElementById('editIconGrid');
    const hidden  = document.getElementById('editIcon');
    const preBox  = document.getElementById('editPreviewBox');
    const preIco  = document.getElementById('editPreviewIcon');
    const colorEl = document.getElementById('editWarna');
    const colorTx = document.getElementById('editWarnaText');

    function setIcon(icon) {
        hidden.value     = icon;
        preIco.className = 'bi ' + icon + ' fs-4';
        grid.querySelectorAll('.icon-pick-btn').forEach(b => {
            b.classList.toggle('btn-primary', b.dataset.icon === icon);
            b.classList.toggle('btn-light',   b.dataset.icon !== icon);
        });
    }

    function applyColor(hex) {
        preBox.style.background = hex + '22';
        preBox.style.color      = hex;
        colorTx.textContent     = hex;
    }

    grid.addEventListener('click', e => {
        const btn = e.target.closest('.icon-pick-btn');
        if (btn) setIcon(btn.dataset.icon);
    });

    colorEl.addEventListener('input', () => applyColor(colorEl.value));

    document.getElementById('editNama')?.addEventListener('input', function () {
        document.getElementById('editPreviewNama').textContent = this.value || '—';
    });

    // Init
    setIcon(hidden.value || 'bi-wallet2');
    applyColor(colorEl.value);
})();
</script>
@endpush
