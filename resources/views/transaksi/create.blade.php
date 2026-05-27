@extends('layouts.app')

@section('title', __('transaksi.add'))
@section('page-title', __('transaksi.add'))

@push('styles')
<style>
.jenis-card {
    cursor: pointer;
    border: 2px solid #e5e7eb;
    background: #fff;
    border-radius: .75rem;
    transition: all .18s ease;
    user-select: none;
}
[data-theme-version="dark"] .jenis-card { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.15); }
.jenis-card:hover { border-color: #9ca3af; }
.btn-check:checked + .jenis-card.card-pengeluaran { border-color: #ef4444; background: #fef2f2; }
.btn-check:checked + .jenis-card.card-pemasukan  { border-color: #22c55e; background: #f0fdf4; }
.btn-check:checked + .jenis-card.card-transfer   { border-color: var(--primary); background: #eff6ff; }
[data-theme-version="dark"] .btn-check:checked + .jenis-card.card-pengeluaran { background: rgba(239,68,68,.15); }
[data-theme-version="dark"] .btn-check:checked + .jenis-card.card-pemasukan  { background: rgba(34,197,94,.15); }
[data-theme-version="dark"] .btn-check:checked + .jenis-card.card-transfer   { background: rgba(59,130,246,.15); }

.jumlah-input {
    font-size: 2rem;
    font-weight: 700;
    border: none;
    border-bottom: 2px solid #e5e7eb;
    border-radius: 0;
    text-align: center;
    padding: .25rem .5rem;
    background: transparent;
    width: 100%;
    transition: border-color .15s;
    color: inherit;
}
.jumlah-input:focus { outline: none; box-shadow: none; border-bottom-color: var(--primary); }

.tag-pill input[type="checkbox"] { display: none; }
.tag-pill label {
    display: inline-block;
    padding: .3rem .85rem;
    border: 1.5px solid #d1d5db;
    border-radius: 50px;
    font-size: .8rem;
    cursor: pointer;
    transition: all .15s;
    background: transparent;
    color: #6b7280;
    font-weight: 500;
}
.tag-pill input:checked + label { background: var(--primary); border-color: var(--primary); color: #fff; }

.section-label {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: .75rem;
}

#suggestDropdown button:hover { background: #f3f4f6 !important; }
</style>
@endpush

@section('content')
@php
$kategoriJson = $kategori->flatMap(fn($k) => collect([['id' => $k->id, 'nama' => $k->nama, 'jenis' => $k->jenis]])->merge(
    $k->children->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama, 'jenis' => $c->jenis])
))->values()->toJson();

$sumberJson = $sumberTransaksi->map(fn($s) => [
    'id'    => $s->id,
    'nama'  => $s->nama,
    'saldo' => (float) $s->saldo_saat_ini,
])->toJson();
@endphp

<form method="POST" action="{{ route('transaksi.store') }}" enctype="multipart/form-data" id="transaksiForm">
@csrf
<input type="hidden" name="ocr_image_path" id="hiddenOcrImagePath">
<input type="hidden" name="ocr_history_id" id="hiddenOcrHistoryId">
<input type="hidden" name="ocr_items" id="hiddenOcrItems">

<div class="row g-3 align-items-start">

    {{-- ═══════════════════════════════════════════════════════
         KOLOM KIRI — OCR + Jenis + Jumlah + Tanggal
    ═══════════════════════════════════════════════════════ --}}
    <div class="col-12 col-lg-5">

        {{-- OCR Banner --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:.75rem;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                         style="width:40px;height:40px;background:#eff6ff;">
                        <i class="bi bi-camera-fill text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold" style="font-size:.875rem;">{{ __('transaksi.scan_receipt') }}</div>
                        <div class="text-muted" style="font-size:.75rem;">{{ __('transaksi.scan_subtitle') }}</div>
                    </div>
                    <label class="btn btn-primary btn-sm flex-shrink-0 mb-0" id="ocrLabel" style="cursor:pointer;">
                        <i class="bi bi-upload me-1"></i>
                        <span id="ocrBtnText">{{ __('transaksi.choose_photo') }}</span>
                        <input type="file" id="ocrFileInput" accept="image/jpeg,image/png,image/webp" class="d-none">
                    </label>
                    <span id="ocrDoneIcon" class="d-none badge bg-success-subtle text-success fw-semibold flex-shrink-0" style="font-size:.75rem;">
                        <i class="bi bi-check-circle-fill me-1"></i>{{ __('transaksi.done') }}
                    </span>
                </div>

                {{-- OCR Preview --}}
                <div id="ocrPreviewArea" class="d-none mt-3 pt-3 border-top">
                    <div class="d-flex align-items-start gap-3">
                        <img id="ocrPreviewImg" src="" alt="Preview"
                             class="rounded-3 border flex-shrink-0"
                             style="width:72px;height:72px;object-fit:cover;">
                        <div class="flex-grow-1">
                            <div id="ocrAnalyzing" class="d-none text-primary small">
                                <div class="spinner-border spinner-border-sm me-1" role="status"></div>
                                {{ __('transaksi.analyzing') }}
                            </div>
                            <div id="ocrResultText" class="d-none small">
                                <div class="fw-semibold mb-1">{{ __('transaksi.detected_data') }}</div>
                                <div id="ocrDetectedJumlah" class="d-none text-success">
                                    <i class="bi bi-check-circle me-1"></i>{{ __('transaksi.detected_amount') }} <span id="ocrJumlahVal"></span>
                                </div>
                                <div id="ocrDetectedToko" class="d-none text-success">
                                    <i class="bi bi-check-circle me-1"></i>{{ __('transaksi.detected_store') }} <span id="ocrTokoVal"></span>
                                </div>
                                <div id="ocrDetectedItems" class="d-none text-success">
                                    <i class="bi bi-check-circle me-1"></i><span id="ocrItemCount"></span> item —
                                    <button type="button" id="toggleItemBtn" class="btn btn-link btn-sm p-0 text-primary fw-medium">{{ __('transaksi.see_edit_items') }}</button>
                                </div>
                                <div id="ocrNoItems" class="d-none text-warning">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ __('transaksi.no_items_detected') }}
                                </div>
                                <div class="text-muted mt-1" style="font-size:.72rem;">
                                    <i class="bi bi-image me-1"></i>{{ __('transaksi.photo_saved') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card: Jenis + Jumlah + Tanggal --}}
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">

                {{-- Jenis Transaksi --}}
                <div class="section-label">{{ __('transaksi.transaction_type') }}</div>
                <div class="row g-2 mb-4">
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="jenis" id="jenisPengeluaran" value="pengeluaran"
                               {{ old('jenis', request('jenis', 'pengeluaran')) === 'pengeluaran' ? 'checked' : '' }}>
                        <label class="jenis-card card-pengeluaran d-block text-center py-3 px-2" for="jenisPengeluaran">
                            <i class="bi bi-arrow-down-circle-fill text-danger d-block mb-1" style="font-size:1.6rem;"></i>
                            <span class="fw-semibold text-danger d-block" style="font-size:.8rem;">{{ __('transaksi.expense') }}</span>
                        </label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="jenis" id="jenisPemasukan" value="pemasukan"
                               {{ old('jenis', request('jenis', 'pengeluaran')) === 'pemasukan' ? 'checked' : '' }}>
                        <label class="jenis-card card-pemasukan d-block text-center py-3 px-2" for="jenisPemasukan">
                            <i class="bi bi-arrow-up-circle-fill text-success d-block mb-1" style="font-size:1.6rem;"></i>
                            <span class="fw-semibold text-success d-block" style="font-size:.8rem;">{{ __('transaksi.income') }}</span>
                        </label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="jenis" id="jenisTransfer" value="transfer"
                               {{ old('jenis', request('jenis', 'pengeluaran')) === 'transfer' ? 'checked' : '' }}>
                        <label class="jenis-card card-transfer d-block text-center py-3 px-2" for="jenisTransfer">
                            <i class="bi bi-arrow-left-right text-primary d-block mb-1" style="font-size:1.6rem;"></i>
                            <span class="fw-semibold text-primary d-block" style="font-size:.8rem;">{{ __('transaksi.transfer') }}</span>
                        </label>
                    </div>
                </div>
                @error('jenis')<div class="text-danger small mt-1 mb-3">{{ $message }}</div>@enderror

                {{-- Jumlah --}}
                <div class="section-label">{{ __('transaksi.amount') }}</div>
                <div class="text-center mb-4">
                    <div class="d-flex align-items-baseline justify-content-center gap-2">
                        <span class="text-muted fw-bold" style="font-size:1.4rem;">Rp</span>
                        <input type="text" inputmode="numeric" name="jumlah" id="jumlahInput"
                               value="{{ old('jumlah') }}" required
                               class="jumlah-input currency-input @error('jumlah') border-danger @enderror"
                               style="max-width:220px;"
                               placeholder="0">
                    </div>
                    <div id="saldoInfo" class="d-none mt-1 small"></div>
                    @error('jumlah')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Tanggal --}}
                <div class="section-label">{{ __('transaksi.date') }}</div>
                <input type="date" name="tanggal" id="tanggalInput"
                       value="{{ old('tanggal', now()->toDateString()) }}" required
                       class="form-control @error('tanggal') is-invalid @enderror">
                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror

            </div>
        </div>

    </div>{{-- /col kiri --}}

    {{-- ═══════════════════════════════════════════════════════
         KOLOM KANAN — Detail + Item + Actions
    ═══════════════════════════════════════════════════════ --}}
    <div class="col-12 col-lg-7 d-flex flex-column gap-3">

        {{-- Saldo Warning --}}
        <div id="saldoWarning" class="alert alert-danger alert-dismissible d-none mb-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>{{ __('transaksi.insufficient_balance') }}</strong>
            {{ __('transaksi.available') }} <strong id="warnSaldo"></strong>, {{ __('transaksi.needed') }} <strong id="warnJumlah"></strong>.
        </div>

        {{-- Card: Detail Transaksi --}}
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <div class="section-label">{{ __('transaksi.detail') }}</div>

                {{-- Keterangan --}}
                <div class="mb-3 position-relative">
                    <label class="form-label fw-medium">{{ __('transaksi.description') }}</label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0">
                            <i class="bi bi-chat-left-text text-muted"></i>
                        </span>
                        <input type="text" name="keterangan" id="keteranganInput"
                               value="{{ old('keterangan') }}"
                               class="form-control border-start-0 ps-0"
                               placeholder="{{ __('transaksi.description_ph') }}" autocomplete="off"
                               style="border-left:none;">
                    </div>
                    <div id="suggestDropdown"
                         class="d-none position-absolute w-100 mt-1 bg-white border rounded-3 shadow"
                         style="z-index:1050;max-height:220px;overflow-y:auto;">
                    </div>
                </div>

                {{-- Kategori + Sumber Dana --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">
                            {{ __('transaksi.category') }} <span class="text-danger">*</span>
                            <span id="ocrKategoriLabel" class="d-none badge bg-success-subtle text-success fw-normal ms-1" style="font-size:.7rem;">{{ __('transaksi.from_ocr') }}</span>
                        </label>
                        <select name="kategori_id" id="kategoriSelect" required
                                class="form-select @error('kategori_id') is-invalid @enderror">
                            <option value="">{{ __('transaksi.category_ph') }}</option>
                            @foreach($kategori as $kat)
                                <option value="{{ $kat->id }}"
                                        data-jenis="{{ $kat->jenis }}"
                                        {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>
                                    {{ $kat->nama }}
                                </option>
                                @foreach($kat->children as $child)
                                    <option value="{{ $child->id }}"
                                            data-jenis="{{ $child->jenis }}"
                                            {{ old('kategori_id') == $child->id ? 'selected' : '' }}>
                                        &nbsp;&nbsp;↳ {{ $child->nama }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">
                            {{ __('transaksi.source') }} <span class="text-danger">*</span>
                        </label>
                        <select name="sumber_transaksi_id" id="sumberSelect" required
                                class="form-select @error('sumber_transaksi_id') is-invalid @enderror">
                            <option value="">{{ __('transaksi.source_ph') }}</option>
                            @foreach($sumberTransaksi as $s)
                                <option value="{{ $s->id }}" {{ old('sumber_transaksi_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->nama }} — Rp {{ number_format($s->saldo_saat_ini, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('sumber_transaksi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Transfer ke --}}
                <div class="mb-3 d-none" id="transferKeRow">
                    <label class="form-label fw-medium">{{ __('transaksi.transfer_to') }} <span class="text-danger">*</span></label>
                    <select name="transfer_ke_id" class="form-select">
                        <option value="">{{ __('transaksi.transfer_to_ph') }}</option>
                        @foreach($sumberTransaksi as $s)
                            <option value="{{ $s->id }}" {{ old('transfer_ke_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tags --}}
                @if($tags->count())
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('transaksi.tags') }}</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <div class="tag-pill">
                                <input type="checkbox" name="tags[]"
                                       value="{{ $tag->id }}" id="tag{{ $tag->id }}"
                                       {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}>
                                <label for="tag{{ $tag->id }}">{{ $tag->nama }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Catatan --}}
                <div class="mb-0">
                    <label class="form-label fw-medium">{{ __('transaksi.notes') }}</label>
                    <textarea name="catatan" rows="2"
                              class="form-control"
                              placeholder="{{ __('transaksi.notes_ph') }}">{{ old('catatan') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Card: Detail Item (collapsible) --}}
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <button type="button" id="itemToggleBtn"
                    class="card-body d-flex align-items-center justify-content-between p-3 border-0 bg-transparent text-start w-100"
                    style="border-radius:.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-3"
                         style="width:34px;height:34px;background:#f3f4f6;">
                        <i class="bi bi-receipt text-muted"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:.875rem;">{{ __('transaksi.item_detail') }}</div>
                        <div class="text-muted" style="font-size:.72rem;">
                            <span id="itemOptLabel">{{ __('transaksi.item_optional') }}</span>
                            <span id="itemCountBadge" class="d-none badge bg-primary ms-1" style="font-size:.7rem;"></span>
                        </div>
                    </div>
                </div>
                <i class="bi bi-chevron-down text-muted" id="itemChevron" style="transition:transform .2s;"></i>
            </button>

            <div id="itemPanel" class="d-none border-top">
                <div class="p-3">
                    <div id="itemEmptyState" class="text-center py-3 text-muted small">
                        <i class="bi bi-inbox d-block mb-1 fs-4"></i>
                        {{ __('transaksi.no_items') }}
                    </div>
                    <div id="itemTableWrap" class="d-none mb-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('transaksi.item_name') }}</th>
                                        <th class="text-center" style="width:60px;">{{ __('transaksi.qty') }}</th>
                                        <th class="text-end" style="width:115px;">{{ __('transaksi.price') }}</th>
                                        <th class="text-end" style="width:115px;">{{ __('transaksi.subtotal') }}</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemTbody"></tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-semibold">Total</td>
                                        <td class="text-end fw-bold" id="totalItemsCell">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <button type="button" id="addItemBtn"
                            class="btn btn-light w-100 border border-2"
                            style="border-style:dashed!important;font-size:.85rem;color:#6b7280;">
                        <i class="bi bi-plus-circle me-1"></i>{{ __('transaksi.add_item') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex align-items-center justify-content-between gap-2 pb-2">
            <a href="{{ route('transaksi.index') }}"
               class="btn btn-light fw-medium flex-shrink-0">
                <i class="bi bi-arrow-left me-1"></i>{{ __('transaksi.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary fw-semibold flex-grow-1" id="submitBtn">
                <i class="bi bi-check-lg me-2"></i>{{ __('transaksi.save') }}
            </button>
        </div>

    </div>{{-- /col kanan --}}

</div>{{-- /row --}}
</form>

@push('scripts')
<script>
(function () {
    const kategoriList = {!! $kategoriJson !!};
    const sumberList   = {!! $sumberJson !!};
    const fmt = n => new Intl.NumberFormat('id-ID').format(n);

    // ── State ────────────────────────────────────────────────
    const state = {
        jenis:           '{{ old('jenis', request('jenis', 'pengeluaran')) }}',
        jumlah:          {{ old('jumlah', 0) }},
        selectedSumberId: '{{ old('sumber_transaksi_id', '') }}',
        uploading:       false,
        ocrDone:         false,
        ocrImagePath:    '',
        ocrHistoryId:    '',
        ocrItems:        [],
        showItemDetail:  false,
        suggestions:     [],
        showSuggestions: false,
    };

    // ── Computed helpers ─────────────────────────────────────
    function saldoSumber() {
        if (!state.selectedSumberId) return null;
        const s = sumberList.find(x => String(x.id) === String(state.selectedSumberId));
        return s ? s.saldo : null;
    }
    function saldoKurang() {
        if (state.jenis !== 'pengeluaran' && state.jenis !== 'transfer') return false;
        const saldo = saldoSumber();
        if (saldo === null) return false;
        return Number(state.jumlah) > saldo;
    }
    function totalItems() {
        return state.ocrItems.reduce((s, i) => s + (Number(i.subtotal) || 0), 0);
    }

    // ── DOM refs ─────────────────────────────────────────────
    const jumlahInput    = document.getElementById('jumlahInput');
    const keteranganInput = document.getElementById('keteranganInput');
    const kategoriSelect = document.getElementById('kategoriSelect');
    const sumberSelect   = document.getElementById('sumberSelect');
    const saldoWarning   = document.getElementById('saldoWarning');
    const warnSaldo      = document.getElementById('warnSaldo');
    const warnJumlah     = document.getElementById('warnJumlah');
    const submitBtn      = document.getElementById('submitBtn');
    const transferKeRow  = document.getElementById('transferKeRow');
    const suggestDD      = document.getElementById('suggestDropdown');
    const saldoInfo      = document.getElementById('saldoInfo');
    const ocrKatLabel    = document.getElementById('ocrKategoriLabel');
    const itemPanel      = document.getElementById('itemPanel');
    const itemTbody      = document.getElementById('itemTbody');
    const itemTableWrap  = document.getElementById('itemTableWrap');
    const itemEmptyState = document.getElementById('itemEmptyState');
    const totalItemsCell = document.getElementById('totalItemsCell');
    const itemCountBadge = document.getElementById('itemCountBadge');
    const itemOptLabel   = document.getElementById('itemOptLabel');
    const itemChevron    = document.getElementById('itemChevron');

    // ── Render helpers ───────────────────────────────────────
    function updateSaldoUI() {
        const saldo = saldoSumber();
        const kurang = saldoKurang();

        if (saldo !== null && state.selectedSumberId) {
            saldoInfo.textContent = '(Saldo: Rp ' + fmt(saldo) + ')';
            saldoInfo.className = 'd-inline small fw-normal ' + (kurang ? 'text-danger' : 'text-muted');
        } else {
            saldoInfo.className = 'd-none';
        }

        if (kurang) {
            saldoWarning.classList.remove('d-none');
            warnSaldo.textContent  = 'Rp ' + fmt(saldo);
            warnJumlah.textContent = 'Rp ' + fmt(state.jumlah);
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
            jumlahInput.classList.add('is-invalid');
            sumberSelect.classList.add('is-invalid');
        } else {
            saldoWarning.classList.add('d-none');
            submitBtn.disabled = false;
            submitBtn.classList.remove('disabled');
            jumlahInput.classList.remove('is-invalid');
            sumberSelect.classList.remove('is-invalid');
        }
    }

    function updateTransferRow() {
        transferKeRow.classList.toggle('d-none', state.jenis !== 'transfer');
    }

    function updateItemTable() {
        const hasItems = state.ocrItems.length > 0;
        itemTableWrap.classList.toggle('d-none', !hasItems);
        itemEmptyState.classList.toggle('d-none', hasItems);

        if (hasItems) {
            itemCountBadge.textContent = state.ocrItems.length + ' item';
            itemCountBadge.classList.remove('d-none');
            itemOptLabel.classList.add('d-none');
        } else {
            itemCountBadge.classList.add('d-none');
            itemOptLabel.classList.remove('d-none');
        }

        totalItemsCell.textContent = 'Rp ' + fmt(totalItems());

        // Sync hidden ocr_items field
        document.getElementById('hiddenOcrItems').value = state.ocrItems.length
            ? JSON.stringify(state.ocrItems) : '';
    }

    function renderItemRow(idx) {
        const item = state.ocrItems[idx];
        const tr = document.createElement('tr');
        tr.id = 'item-row-' + idx;
        tr.innerHTML = `
            <td class="p-1">
                <input type="text" class="form-control form-control-sm"
                       placeholder="{{ __('transaksi.item_name_ph') }}" value="${escHtml(item.nama_item || '')}">
            </td>
            <td class="p-1">
                <input type="number" class="form-control form-control-sm text-center"
                       min="0" step="0.5" placeholder="1" value="${item.qty || 1}">
            </td>
            <td class="p-1">
                <input type="number" class="form-control form-control-sm text-end"
                       min="0" placeholder="0" value="${item.harga_satuan || 0}">
            </td>
            <td class="p-1">
                <input type="number" class="form-control form-control-sm text-end"
                       min="0" placeholder="0" value="${item.subtotal || 0}">
            </td>
            <td class="p-1 text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-0 px-1" style="line-height:1;">
                    <i class="bi bi-x-lg" style="font-size:.7rem;"></i>
                </button>
            </td>`;

        const [namaIn, qtyIn, hargaIn, subtotalIn] = tr.querySelectorAll('input');
        const delBtn = tr.querySelector('button');

        namaIn.addEventListener('input', () => { state.ocrItems[idx].nama_item = namaIn.value; });
        qtyIn.addEventListener('input', () => {
            state.ocrItems[idx].qty = Number(qtyIn.value) || 0;
            state.ocrItems[idx].subtotal = Math.round(state.ocrItems[idx].qty * (state.ocrItems[idx].harga_satuan || 0));
            subtotalIn.value = state.ocrItems[idx].subtotal;
            totalItemsCell.textContent = 'Rp ' + fmt(totalItems());
            document.getElementById('hiddenOcrItems').value = JSON.stringify(state.ocrItems);
        });
        hargaIn.addEventListener('input', () => {
            state.ocrItems[idx].harga_satuan = Number(hargaIn.value) || 0;
            state.ocrItems[idx].subtotal = Math.round((state.ocrItems[idx].qty || 0) * state.ocrItems[idx].harga_satuan);
            subtotalIn.value = state.ocrItems[idx].subtotal;
            totalItemsCell.textContent = 'Rp ' + fmt(totalItems());
            document.getElementById('hiddenOcrItems').value = JSON.stringify(state.ocrItems);
        });
        subtotalIn.addEventListener('input', () => {
            state.ocrItems[idx].subtotal = Number(subtotalIn.value) || 0;
            totalItemsCell.textContent = 'Rp ' + fmt(totalItems());
            document.getElementById('hiddenOcrItems').value = JSON.stringify(state.ocrItems);
        });
        delBtn.addEventListener('click', () => {
            state.ocrItems.splice(idx, 1);
            rebuildItemTable();
        });

        return tr;
    }

    function rebuildItemTable() {
        itemTbody.innerHTML = '';
        state.ocrItems.forEach((_, i) => itemTbody.appendChild(renderItemRow(i)));
        updateItemTable();
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Filter kategori berdasarkan jenis ────────────────────
    function filterKategori(jenis) {
        const select = document.getElementById('kategoriSelect');
        Array.from(select.options).forEach(function(opt) {
            if (!opt.value) return; // skip placeholder
            var optJenis = opt.dataset.jenis;
            // transfer: tampilkan semua; pengeluaran/pemasukan: filter ketat
            var match = (jenis === 'transfer') || (optJenis === jenis);
            opt.hidden   = !match;
            opt.disabled = !match;
        });
        // Reset pilihan jika opsi yang dipilih sekarang tidak sesuai jenis
        var sel = select.options[select.selectedIndex];
        if (sel && sel.hidden) {
            select.value = '';
            ocrKatLabel.classList.add('d-none');
        }
    }

    // ── Jenis radio ──────────────────────────────────────────
    document.querySelectorAll('input[name="jenis"]').forEach(radio => {
        radio.addEventListener('change', () => {
            state.jenis = radio.value;
            filterKategori(state.jenis);
            updateTransferRow();
            updateSaldoUI();
        });
    });
    filterKategori(state.jenis); // filter saat halaman pertama dimuat
    updateTransferRow();

    // ── Jumlah input ─────────────────────────────────────────
    function rawJumlah() {
        return Number(jumlahInput.value.replace(/\./g, '')) || 0;
    }
    jumlahInput.addEventListener('input', () => {
        state.jumlah = rawJumlah();
        updateSaldoUI();
    });

    // ── Sumber select ────────────────────────────────────────
    sumberSelect.addEventListener('change', () => {
        state.selectedSumberId = sumberSelect.value;
        updateSaldoUI();
    });
    // init
    state.selectedSumberId = sumberSelect.value;
    state.jumlah = rawJumlah();
    updateSaldoUI();

    // ── Autocomplete ─────────────────────────────────────────
    let suggestTimer = null;
    keteranganInput.addEventListener('input', () => {
        clearTimeout(suggestTimer);
        const q = keteranganInput.value;
        if (q.length < 2) { suggestDD.classList.add('d-none'); return; }
        suggestTimer = setTimeout(() => {
            fetch('/api/transaksi/suggest?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(d => {
                state.suggestions = d.data || [];
                if (!state.suggestions.length) { suggestDD.classList.add('d-none'); return; }
                suggestDD.innerHTML = '';
                state.suggestions.forEach(s => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'd-flex align-items-center justify-content-between w-100 px-3 py-2 text-start border-0 bg-white small';
                    btn.style.cursor = 'pointer';
                    btn.innerHTML = `<span>${escHtml(s.keterangan)}</span><span class="text-muted" style="font-size:.7rem;">Rp ${fmt(s.rata_jumlah)}</span>`;
                    btn.addEventListener('mousedown', e => {
                        e.preventDefault();
                        keteranganInput.value = s.keterangan;
                        if (s.rata_jumlah) {
                            state.jumlah = Math.round(s.rata_jumlah);
                            jumlahInput.value = state.jumlah.toLocaleString('id-ID');
                            updateSaldoUI();
                        }
                        suggestDD.classList.add('d-none');
                    });
                    suggestDD.appendChild(btn);
                });
                suggestDD.classList.remove('d-none');
            })
            .catch(() => {});
        }, 300);
    });
    keteranganInput.addEventListener('blur', () => setTimeout(() => suggestDD.classList.add('d-none'), 150));
    keteranganInput.addEventListener('focus', () => { if (state.suggestions.length) suggestDD.classList.remove('d-none'); });

    // ── Item panel toggle ────────────────────────────────────
    document.getElementById('itemToggleBtn').addEventListener('click', () => {
        state.showItemDetail = !state.showItemDetail;
        itemPanel.classList.toggle('d-none', !state.showItemDetail);
        itemChevron.style.transform = state.showItemDetail ? 'rotate(180deg)' : '';
    });

    document.getElementById('addItemBtn').addEventListener('click', () => {
        const idx = state.ocrItems.length;
        state.ocrItems.push({ nama_item: '', qty: 1, harga_satuan: 0, subtotal: 0 });
        itemTbody.appendChild(renderItemRow(idx));
        updateItemTable();
        if (!state.showItemDetail) {
            state.showItemDetail = true;
            itemPanel.classList.remove('d-none');
            itemChevron.style.transform = 'rotate(180deg)';
        }
    });

    // ── OCR helpers ──────────────────────────────────────────
    function guessKategori(tipeToko, namaToko, tipeTransaksi) {
        const jenisKat = tipeTransaksi === 'income' ? 'pemasukan' : 'pengeluaran';
        const haystack = ((tipeToko || '') + ' ' + (namaToko || '')).toLowerCase();
        const map = [
            { keys: ['rokok','tembakau','sigaret','kretek','gudang garam','sampoerna','dji sam soe','marlboro','camel','la mild','a mild','u mild','class mild','star mild','dunhill','djarum','surya','wismilak','magnum bold','diplomat','esse','filter djarum','apache','envio','neo mild','uno mild','nojorono'], nama: 'rokok' },
            { keys: ['makan','minum','resto','warung','cafe','kafe','burger','pizza','bakery','kopi','soto','nasi','ayam','seafood','sushi'], nama: 'makanan' },
            { keys: ['transport','bensin','bbm','parkir','tol','grab','gojek','ojek','taxi','bis','kereta','busway','angkot','spbu'], nama: 'transport' },
            { keys: ['tagihan','listrik','pln','pdam','air','internet','wifi','telpon','pulsa','token','indihome','myrepublic'], nama: 'tagihan' },
            { keys: ['belanja','supermarket','minimarket','indomaret','alfamart','mall','toko','shop','hypermart','carrefour','giant'], nama: 'belanja' },
            { keys: ['kesehatan','apotek','apotik','klinik','dokter','rumah sakit','obat','vitamin','rs ','puskesmas'], nama: 'kesehatan' },
            { keys: ['pendidikan','sekolah','kampus','universitas','buku','kursus','les','tutor'], nama: 'pendidikan' },
            { keys: ['hiburan','bioskop','cinema','game','streaming','netflix','spotify','musik','konser'], nama: 'hiburan' },
            { keys: ['gaji','salary','upah','honor'], nama: 'gaji' },
            { keys: ['bonus','reward','hadiah','thr'], nama: 'bonus' },
            { keys: ['investasi','saham','reksa','deposito','dividen'], nama: 'investasi' },
            { keys: ['bisnis','usaha','dagang','penjualan','omset'], nama: 'bisnis' },
        ];
        const filtered = kategoriList.filter(k => k.jenis === jenisKat);
        for (const { keys, nama } of map) {
            if (keys.some(k => haystack.includes(k))) {
                const match = filtered.find(k => k.nama.toLowerCase().includes(nama));
                if (match) return String(match.id);
            }
        }
        return '';
    }

    // ── OCR Upload ───────────────────────────────────────────
    const ocrFileInput  = document.getElementById('ocrFileInput');
    const ocrLabel      = document.getElementById('ocrLabel');
    const ocrBtnText    = document.getElementById('ocrBtnText');
    const ocrDoneIcon   = document.getElementById('ocrDoneIcon');
    const ocrPreviewArea = document.getElementById('ocrPreviewArea');
    const ocrPreviewImg = document.getElementById('ocrPreviewImg');
    const ocrResultText = document.getElementById('ocrResultText');
    const ocrAnalyzing  = document.getElementById('ocrAnalyzing');
    const ocrDetectedJumlah = document.getElementById('ocrDetectedJumlah');
    const ocrDetectedToko   = document.getElementById('ocrDetectedToko');
    const ocrDetectedItems  = document.getElementById('ocrDetectedItems');
    const ocrNoItems        = document.getElementById('ocrNoItems');
    const ocrItemCount      = document.getElementById('ocrItemCount');
    const toggleItemBtn     = document.getElementById('toggleItemBtn');

    toggleItemBtn.addEventListener('click', () => {
        state.showItemDetail = !state.showItemDetail;
        itemPanel.classList.toggle('d-none', !state.showItemDetail);
        itemChevron.style.transform = state.showItemDetail ? 'rotate(180deg)' : '';
        toggleItemBtn.textContent = state.showItemDetail ? '{{ __('transaksi.hide_items') }}' : '{{ __('transaksi.see_edit_items') }}';
    });

    ocrFileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        ocrPreviewImg.src = URL.createObjectURL(file);
        ocrPreviewArea.classList.remove('d-none');
        ocrAnalyzing.classList.remove('d-none');
        ocrResultText.classList.add('d-none');
        ocrBtnText.textContent = 'Menganalisis...';
        ocrFileInput.disabled = true;

        const fd = new FormData();
        fd.append('image', file);
        fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

        fetch('/api/ocr/extract', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                ocrBtnText.textContent = '{{ __('transaksi.change_photo') }}';
                ocrFileInput.disabled = false;

                if (d.success && d.data) {
                    const r = d.data.result || {};
                    const s = d.data.suggested_transaksi || {};

                    if (r.total) {
                        state.jumlah = Number(r.total);
                        jumlahInput.value = state.jumlah.toLocaleString('id-ID');
                        ocrDetectedJumlah.classList.remove('d-none');
                        document.getElementById('ocrJumlahVal').textContent = fmt(r.total);
                    }
                    if (r.tanggal) document.getElementById('tanggalInput').value = r.tanggal;
                    if (r.nama_toko) {
                        keteranganInput.value = r.nama_toko;
                        ocrDetectedToko.classList.remove('d-none');
                        document.getElementById('ocrTokoVal').textContent = r.nama_toko;
                    }
                    if (s.jenis) {
                        state.jenis = s.jenis;
                        const radio = document.querySelector('input[name="jenis"][value="' + s.jenis + '"]');
                        if (radio) radio.checked = true;
                        updateTransferRow();
                    }

                    const guessed = guessKategori(r.tipe_toko, r.nama_toko, r.tipe_transaksi);
                    if (guessed) {
                        kategoriSelect.value = guessed;
                        ocrKatLabel.classList.remove('d-none');
                    }

                    state.ocrImagePath  = d.data.image_path  || '';
                    state.ocrHistoryId  = d.data.history_id  || '';
                    document.getElementById('hiddenOcrImagePath').value = state.ocrImagePath;
                    document.getElementById('hiddenOcrHistoryId').value = state.ocrHistoryId;

                    state.ocrItems = (s.items && s.items.length) ? s.items : [];
                    rebuildItemTable();

                    if (state.ocrItems.length) {
                        ocrItemCount.textContent = state.ocrItems.length;
                        ocrDetectedItems.classList.remove('d-none');
                        state.showItemDetail = true;
                        itemPanel.classList.remove('d-none');
                        itemChevron.style.transform = 'rotate(180deg)';
                    } else {
                        ocrNoItems.classList.remove('d-none');
                    }

                    updateSaldoUI();
                    ocrDoneIcon.classList.remove('d-none');
                    ocrResultText.classList.remove('d-none');
                    ocrAnalyzing.classList.add('d-none');
                } else {
                    ocrAnalyzing.classList.add('d-none');
                    alert(d.message || 'OCR gagal diproses.');
                }
            })
            .catch(() => {
                ocrFileInput.disabled = false;
                ocrBtnText.textContent = '{{ __('transaksi.choose_photo') }}';
                ocrAnalyzing.classList.add('d-none');
                alert('Gagal menghubungi server OCR.');
            });
    });

    // ── Initial state for sumber (on old() repopulation) ────
    if (sumberSelect.value) {
        state.selectedSumberId = sumberSelect.value;
        updateSaldoUI();
    }
})();
</script>
@endpush
@endsection
