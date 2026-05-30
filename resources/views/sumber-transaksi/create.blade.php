@extends('layouts.app')

@section('title', __('sumber.add'))
@section('page-title', __('sumber.add'))

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
    $iconAutoMap = [
        'bank'         => 'bi-bank',
        'e-wallet'     => 'bi-phone',
        'cash'         => 'bi-cash-stack',
        'kartu_kredit' => 'bi-credit-card',
        'investasi'    => 'bi-graph-up-arrow',
        'lainnya'      => 'bi-wallet2',
    ];
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

            <form method="POST" action="{{ route('sumber-transaksi.store') }}" novalidate>
                @csrf

                <div class="row g-3">
                    {{-- Nama --}}
                    <div class="col-12">
                        <label class="form-label fw-medium">
                            {{ __('sumber.name') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama" id="createNama"
                               required maxlength="100" autocomplete="off"
                               placeholder="{{ __('sumber.name_ph') }}"
                               value="{{ old('nama') }}"
                               class="form-control @error('nama') is-invalid @enderror">
                        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Jenis + Saldo --}}
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">
                            {{ __('sumber.type') }} <span class="text-danger">*</span>
                        </label>
                        <select name="jenis" id="createJenis" required
                                class="form-select @error('jenis') is-invalid @enderror">
                            <option value="">— {{ __('sumber.type') }} —</option>
                            <option value="cash"         {{ old('jenis') === 'cash'         ? 'selected' : '' }}>{{ __('sumber.cash') }}</option>
                            <option value="bank"         {{ old('jenis') === 'bank'         ? 'selected' : '' }}>{{ __('sumber.bank') }}</option>
                            <option value="e-wallet"     {{ old('jenis') === 'e-wallet'     ? 'selected' : '' }}>{{ __('sumber.ewallet') }}</option>
                            <option value="kartu_kredit" {{ old('jenis') === 'kartu_kredit' ? 'selected' : '' }}>{{ __('sumber.card_credit') }}</option>
                            <option value="investasi"    {{ old('jenis') === 'investasi'    ? 'selected' : '' }}>{{ __('sumber.investment') }}</option>
                            <option value="lainnya"      {{ old('jenis') === 'lainnya'      ? 'selected' : '' }}>{{ __('sumber.other') }}</option>
                        </select>
                        @error('jenis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('sumber.initial_balance') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" name="saldo"
                                   value="{{ old('saldo', 0) }}"
                                   class="form-control currency-input @error('saldo') is-invalid @enderror">
                            @error('saldo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Nomor Rekening + Nama Bank --}}
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('sumber.account_number') }}</label>
                        <input type="text" name="nomor_rekening" maxlength="50"
                               placeholder="Opsional"
                               value="{{ old('nomor_rekening') }}"
                               class="form-control">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('sumber.bank_name') }}</label>
                        <input type="text" name="nama_bank" maxlength="100"
                               placeholder="Opsional"
                               value="{{ old('nama_bank') }}"
                               class="form-control">
                    </div>

                    {{-- Warna + Icon --}}
                    <div class="col-12">
                        <div class="d-flex gap-4 flex-wrap align-items-start">
                            <div class="flex-shrink-0">
                                <label class="form-label fw-medium d-block">{{ __('sumber.color') }}</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="warna" id="createWarna"
                                           value="{{ old('warna', '#3b82f6') }}"
                                           class="form-control form-control-color"
                                           style="width:46px;height:38px;">
                                    <code class="small text-muted" id="createWarnaText">
                                        {{ old('warna', '#3b82f6') }}
                                    </code>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label fw-medium">{{ __('sumber.icon_pick') }}</label>
                                <input type="hidden" name="icon" id="createIcon"
                                       value="{{ old('icon', 'bi-wallet2') }}">
                                <div class="d-flex flex-wrap gap-1" id="createIconGrid">
                                    @foreach($iconList as $ico)
                                    <button type="button"
                                            class="btn btn-sm p-0 d-flex align-items-center justify-content-center icon-pick-btn
                                                   {{ (old('icon', 'bi-wallet2') === $ico) ? 'btn-primary' : 'btn-light' }}"
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
                            <div id="createPreviewBox"
                                 class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:52px;height:52px;background:#3b82f622;color:#3b82f6;">
                                <i class="bi bi-wallet2 fs-4" id="createPreviewIcon"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" id="createPreviewNama">—</div>
                                <div class="text-muted small" id="createPreviewJenis">—</div>
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
    const iconAutoMap = @json($iconAutoMap);

    const grid    = document.getElementById('createIconGrid');
    const hidden  = document.getElementById('createIcon');
    const preBox  = document.getElementById('createPreviewBox');
    const preIco  = document.getElementById('createPreviewIcon');
    const colorEl = document.getElementById('createWarna');
    const colorTx = document.getElementById('createWarnaText');

    function setIcon(icon, manual) {
        hidden.value = icon;
        if (manual) hidden._manual = true;
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
        if (btn) setIcon(btn.dataset.icon, true);
    });

    colorEl.addEventListener('input', () => applyColor(colorEl.value));

    document.getElementById('createNama')?.addEventListener('input', function () {
        document.getElementById('createPreviewNama').textContent = this.value || '—';
    });

    document.getElementById('createJenis')?.addEventListener('change', function () {
        const sel = this.options[this.selectedIndex];
        document.getElementById('createPreviewJenis').textContent = sel?.text || '—';
        if (!hidden._manual) setIcon(iconAutoMap[this.value] || 'bi-wallet2', false);
    });

    setIcon(hidden.value || 'bi-wallet2', false);
    applyColor(colorEl.value);
})();
</script>
@endpush
