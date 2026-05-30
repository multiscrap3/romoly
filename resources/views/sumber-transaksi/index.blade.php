@extends('layouts.app')

@section('title', __('sumber.title'))
@section('page-title', __('sumber.title'))

@section('content')
@php
    $iconMap = [
        'bank'         => 'bi-bank',
        'e-wallet'     => 'bi-phone',
        'cash'         => 'bi-cash-stack',
        'kartu_kredit' => 'bi-credit-card',
        'investasi'    => 'bi-graph-up-arrow',
        'lainnya'      => 'bi-wallet2',
    ];
    $jenisLabel = [
        'bank'         => __('sumber.bank'),
        'e-wallet'     => __('sumber.ewallet'),
        'cash'         => __('sumber.cash'),
        'kartu_kredit' => __('sumber.card_credit'),
        'investasi'    => __('sumber.investment'),
        'lainnya'      => __('sumber.other'),
    ];
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
@endphp

{{-- Flash Messages --}}
@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $type)
    @if(session($key))
    <div class="alert alert-{{ $type }} alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-{{ $type === 'success' ? 'check-circle' : ($type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle') }} me-2"></i>
        {!! session($key) !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
@endforeach

{{-- ── Header Row ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <div class="text-muted small mb-1">{{ __('sumber.total_aset') }}</div>
        <div class="fw-bold fs-4 text-primary">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</div>
        <div class="d-flex gap-2 mt-1">
            <span class="badge rounded-pill small fw-normal" style="background:#dcfce7;color:#16a34a;">
                <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                {{ $aktif->count() }} {{ __('sumber.active') }}
            </span>
            @if($arsip->count())
            <span class="badge rounded-pill small fw-normal" style="background:#f1f5f9;color:#64748b;">
                <i class="bi bi-archive me-1" style="font-size:.72rem;"></i>
                {{ $arsip->count() }} {{ __('sumber.archived') }}
            </span>
            @endif
        </div>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2"
            data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg"></i>
        <span>{{ __('sumber.add') }}</span>
    </button>
</div>

{{-- ── Active Sources Grid ─────────────────────────────────────────────────── --}}
@if($aktif->isEmpty())
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-wallet2 d-block mb-3 text-muted" style="font-size:2.8rem;opacity:.25;"></i>
            <p class="text-muted mb-3 small">{{ __('sumber.no_active') }}</p>
            <button class="btn btn-primary btn-sm px-4"
                    data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i>{{ __('sumber.add') }}
            </button>
        </div>
    </div>
@else
    <div class="row g-3 mb-4">
        @foreach($aktif as $sumber)
        @php
            $icon     = $sumber->icon ?: ($iconMap[$sumber->jenis] ?? 'bi-wallet2');
            $warna    = $sumber->warna ?: '#6c757d';
            $label    = $jenisLabel[$sumber->jenis] ?? $sumber->jenis;
            $txCount  = $sumber->transaksi_count + $sumber->transaksi_transfer_masuk_count;
            $hasSaldo = (float) $sumber->saldo_saat_ini !== 0.0;
            $canDelete = !$hasSaldo && $txCount === 0;
        @endphp
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden sumber-card">
                {{-- Color accent strip --}}
                <div style="height:3px;background:{{ $warna }};"></div>
                <div class="card-body p-4 d-flex flex-column">

                    {{-- Icon + Identity --}}
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                             style="width:52px;height:52px;background:{{ $warna }}22;color:{{ $warna }};">
                            <i class="bi {{ $icon }} fs-4"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate mb-1" title="{{ $sumber->nama }}">
                                {{ $sumber->nama }}
                            </div>
                            <span class="badge rounded-pill fw-normal small"
                                  style="background:{{ $warna }}18;color:{{ $warna }};">
                                {{ $label }}
                            </span>
                            @if($sumber->nomor_rekening)
                            <div class="text-muted mt-1" style="font-size:.72rem;letter-spacing:.03em;">
                                <i class="bi bi-card-text me-1"></i>{{ $sumber->nomor_rekening }}
                            </div>
                            @endif
                            @if($sumber->nama_bank)
                            <div class="text-muted" style="font-size:.72rem;">
                                <i class="bi bi-building me-1"></i>{{ $sumber->nama_bank }}
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Saldo Box --}}
                    <div class="rounded-2 px-3 py-2 mb-3" style="background:#f8fafc;">
                        <div class="text-muted mb-1" style="font-size:.72rem;">{{ __('sumber.current_balance') }}</div>
                        <div class="fw-bold fs-6 text-dark">
                            Rp {{ number_format($sumber->saldo_saat_ini, 0, ',', '.') }}
                        </div>
                    </div>

                    {{-- Transaction count info --}}
                    @if($txCount > 0)
                    <div class="text-muted mb-3" style="font-size:.75rem;">
                        <i class="bi bi-arrow-left-right me-1"></i>{{ $txCount }} transaksi
                    </div>
                    @endif

                    {{-- Actions (push to bottom) --}}
                    <div class="d-flex gap-2 mt-auto">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary flex-fill btn-edit-sumber"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEdit"
                                data-id="{{ $sumber->id }}"
                                data-nama="{{ $sumber->nama }}"
                                data-jenis="{{ $sumber->jenis }}"
                                data-icon="{{ $icon }}"
                                data-warna="{{ $warna }}"
                                data-nomor="{{ $sumber->nomor_rekening }}"
                                data-bank="{{ $sumber->nama_bank }}"
                                data-saldo="{{ number_format($sumber->saldo_saat_ini, 0, ',', '.') }}"
                                data-url="{{ route('sumber-transaksi.update', $sumber) }}">
                            <i class="bi bi-pencil-fill me-1"></i>Edit
                        </button>

                        @if($canDelete)
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger flex-fill btn-delete-sumber"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDelete"
                                    data-nama="{{ $sumber->nama }}"
                                    data-url="{{ route('sumber-transaksi.destroy', $sumber) }}">
                                <i class="bi bi-trash me-1"></i>Hapus
                            </button>
                        @else
                            <form action="{{ route('sumber-transaksi.deactivate', $sumber) }}"
                                  method="POST" class="flex-fill">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-secondary w-100"
                                        title="{{ __('sumber.deactivate_hint') }}">
                                    <i class="bi bi-archive me-1"></i>{{ __('sumber.deactivate') }}
                                </button>
                            </form>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- ── Archived Section ────────────────────────────────────────────────────── --}}
@if($arsip->count())
<div class="mb-4">
    <button class="btn btn-link text-muted text-decoration-none p-0 mb-3 small d-flex align-items-center gap-1"
            data-bs-toggle="collapse" data-bs-target="#arsipSection" aria-expanded="false">
        <i class="bi bi-archive"></i>
        {{ __('sumber.archive_section') }} ({{ $arsip->count() }})
        <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;" id="arsipChevron"></i>
    </button>
    <div class="collapse" id="arsipSection">
        <div class="row g-3">
            @foreach($arsip as $sumber)
            @php
                $icon    = $sumber->icon ?: ($iconMap[$sumber->jenis] ?? 'bi-wallet2');
                $warna   = $sumber->warna ?: '#6c757d';
                $label   = $jenisLabel[$sumber->jenis] ?? $sumber->jenis;
                $txCount = $sumber->transaksi_count + $sumber->transaksi_transfer_masuk_count;
                $canDelete = (float) $sumber->saldo_saat_ini === 0.0 && $txCount === 0;
            @endphp
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden" style="opacity:.75;">
                    <div style="height:3px;background:#cbd5e1;"></div>
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:52px;height:52px;background:#e2e8f0;color:#94a3b8;">
                                <i class="bi {{ $icon }} fs-4"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-muted text-truncate mb-1" title="{{ $sumber->nama }}">
                                    {{ $sumber->nama }}
                                </div>
                                <span class="badge rounded-pill fw-normal small bg-secondary-subtle text-secondary">
                                    {{ $label }}
                                </span>
                                @if($sumber->nomor_rekening)
                                <div class="text-muted mt-1" style="font-size:.72rem;">
                                    <i class="bi bi-card-text me-1"></i>{{ $sumber->nomor_rekening }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-2 px-3 py-2 mb-3" style="background:#f8fafc;">
                            <div class="text-muted" style="font-size:.72rem;">{{ __('sumber.current_balance') }}</div>
                            <div class="fw-bold text-muted">
                                Rp {{ number_format($sumber->saldo_saat_ini, 0, ',', '.') }}
                            </div>
                        </div>

                        @if($txCount > 0)
                        <div class="text-muted mb-3" style="font-size:.75rem;">
                            <i class="bi bi-arrow-left-right me-1"></i>{{ $txCount }} transaksi
                        </div>
                        @endif

                        <div class="d-flex gap-2 mt-auto">
                            <form action="{{ route('sumber-transaksi.activate', $sumber) }}"
                                  method="POST" class="flex-fill">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                    <i class="bi bi-arrow-up-circle me-1"></i>{{ __('sumber.activate') }}
                                </button>
                            </form>
                            @if($canDelete)
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger flex-fill btn-delete-sumber"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDelete"
                                    data-nama="{{ $sumber->nama }}"
                                    data-url="{{ route('sumber-transaksi.destroy', $sumber) }}">
                                <i class="bi bi-trash me-1"></i>Hapus
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Tambah Sumber Dana
     ══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form method="POST" action="{{ route('sumber-transaksi.store') }}" novalidate>
                @csrf
                <input type="hidden" name="_form" value="tambah">

                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h6 class="modal-title fw-semibold" id="modalTambahLabel">
                        <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                        {{ __('sumber.add_title') }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    @if($errors->any() && old('_form') === 'tambah')
                    <div class="alert alert-danger py-2 mb-3">
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row g-3">
                        {{-- Nama --}}
                        <div class="col-12">
                            <label class="form-label small fw-medium">
                                {{ __('sumber.name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" id="tambahNama"
                                   required maxlength="100" autocomplete="off"
                                   placeholder="{{ __('sumber.name_ph') }}"
                                   value="{{ old('nama') }}"
                                   class="form-control @error('nama') is-invalid @enderror">
                            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Jenis + Saldo --}}
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium">
                                {{ __('sumber.type') }} <span class="text-danger">*</span>
                            </label>
                            <select name="jenis" id="tambahJenis" required
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
                            <label class="form-label small fw-medium">{{ __('sumber.initial_balance') }}</label>
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
                            <label class="form-label small fw-medium">{{ __('sumber.account_number') }}</label>
                            <input type="text" name="nomor_rekening" maxlength="50"
                                   placeholder="Opsional"
                                   value="{{ old('nomor_rekening') }}"
                                   class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium">{{ __('sumber.bank_name') }}</label>
                            <input type="text" name="nama_bank" maxlength="100"
                                   placeholder="Opsional"
                                   value="{{ old('nama_bank') }}"
                                   class="form-control">
                        </div>

                        {{-- Warna + Icon Picker --}}
                        <div class="col-12">
                            <div class="d-flex gap-4 flex-wrap align-items-start">
                                {{-- Color --}}
                                <div class="flex-shrink-0">
                                    <label class="form-label small fw-medium d-block">{{ __('sumber.color') }}</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" name="warna" id="warnaTambah"
                                               value="{{ old('warna', '#3b82f6') }}"
                                               class="form-control form-control-color"
                                               style="width:46px;height:38px;">
                                        <code class="small text-muted" id="warnaTextTambah">
                                            {{ old('warna', '#3b82f6') }}
                                        </code>
                                    </div>
                                </div>
                                {{-- Icon grid --}}
                                <div class="flex-grow-1">
                                    <label class="form-label small fw-medium">{{ __('sumber.icon_pick') }}</label>
                                    <input type="hidden" name="icon" id="iconTambah"
                                           value="{{ old('icon', 'bi-wallet2') }}">
                                    <div class="d-flex flex-wrap gap-1" id="iconGridTambah">
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
                            <div class="d-flex align-items-center gap-3 rounded-2 px-3 py-2"
                                 style="background:#f8fafc;">
                                <div id="previewBoxTambah"
                                     class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                     style="width:48px;height:48px;background:#3b82f622;color:#3b82f6;">
                                    <i class="bi bi-wallet2 fs-5" id="previewIconTambah"></i>
                                </div>
                                <div>
                                    <div class="fw-medium small" id="previewNamaTambah">—</div>
                                    <div class="text-muted" style="font-size:.72rem;" id="previewJenisTambah">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary px-4"
                            data-bs-dismiss="modal">{{ __('sumber.cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>{{ __('sumber.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Edit Sumber Dana
     ══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form method="POST" id="formEdit" novalidate>
                @csrf
                @method('PUT')

                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h6 class="modal-title fw-semibold" id="modalEditLabel">
                        <i class="bi bi-pencil-fill text-primary me-2"></i>
                        {{ __('sumber.edit_title') }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-medium">
                                {{ __('sumber.name') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" id="editNama"
                                   required maxlength="100" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium">{{ __('sumber.type') }} <span class="text-danger">*</span></label>
                            <select name="jenis" id="editJenis" required class="form-select">
                                <option value="cash">{{ __('sumber.cash') }}</option>
                                <option value="bank">{{ __('sumber.bank') }}</option>
                                <option value="e-wallet">{{ __('sumber.ewallet') }}</option>
                                <option value="kartu_kredit">{{ __('sumber.card_credit') }}</option>
                                <option value="investasi">{{ __('sumber.investment') }}</option>
                                <option value="lainnya">{{ __('sumber.other') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium">{{ __('sumber.current_balance') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="editSaldo" readonly
                                       class="form-control bg-light text-muted">
                            </div>
                            <div class="form-text small text-muted">{{ __('sumber.notes') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium">{{ __('sumber.account_number') }}</label>
                            <input type="text" name="nomor_rekening" id="editNomor"
                                   maxlength="50" placeholder="Opsional" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-medium">{{ __('sumber.bank_name') }}</label>
                            <input type="text" name="nama_bank" id="editBank"
                                   maxlength="100" placeholder="Opsional" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-4 flex-wrap align-items-start">
                                <div class="flex-shrink-0">
                                    <label class="form-label small fw-medium d-block">{{ __('sumber.color') }}</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" name="warna" id="warnaEdit"
                                               value="#3b82f6"
                                               class="form-control form-control-color"
                                               style="width:46px;height:38px;">
                                        <code class="small text-muted" id="warnaTextEdit">#3b82f6</code>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <label class="form-label small fw-medium">{{ __('sumber.icon_pick') }}</label>
                                    <input type="hidden" name="icon" id="iconEdit" value="bi-wallet2">
                                    <div class="d-flex flex-wrap gap-1" id="iconGridEdit">
                                        @foreach($iconList as $ico)
                                        <button type="button"
                                                class="btn btn-sm btn-light p-0 d-flex align-items-center justify-content-center icon-pick-btn"
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
                            <div class="d-flex align-items-center gap-3 rounded-2 px-3 py-2"
                                 style="background:#f8fafc;">
                                <div id="previewBoxEdit"
                                     class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                     style="width:48px;height:48px;background:#3b82f622;color:#3b82f6;">
                                    <i class="bi bi-wallet2 fs-5" id="previewIconEdit"></i>
                                </div>
                                <div>
                                    <div class="fw-medium small" id="previewNamaEdit">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary px-4"
                            data-bs-dismiss="modal">{{ __('sumber.cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>{{ __('sumber.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Delete Confirmation
     ══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-body px-4 pt-4 pb-3 text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:56px;height:56px;background:#fee2e2;">
                    <i class="bi bi-trash-fill text-danger fs-4"></i>
                </div>
                <h6 class="fw-semibold mb-2">{{ __('sumber.delete_title') }}</h6>
                <p class="text-muted small mb-0">
                    Sumber dana <strong id="deleteNama">—</strong> akan dihapus permanen.
                    <br>Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-1 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill"
                        data-bs-dismiss="modal">{{ __('sumber.cancel') }}</button>
                <form method="POST" id="formDelete" class="flex-fill">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-trash me-1"></i>Hapus Permanen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const iconAutoMap = {
        bank:         'bi-bank',
        'e-wallet':   'bi-phone',
        cash:         'bi-cash-stack',
        kartu_kredit: 'bi-credit-card',
        investasi:    'bi-graph-up-arrow',
        lainnya:      'bi-wallet2',
    };

    // ── Generic icon-picker setup ──────────────────────────────────────────
    function initIconPicker(opts) {
        const grid       = document.getElementById(opts.gridId);
        const hiddenInput= document.getElementById(opts.hiddenId);
        const previewBox = document.getElementById(opts.previewBoxId);
        const previewIco = document.getElementById(opts.previewIconId);
        const colorInput = document.getElementById(opts.colorId);
        const colorText  = document.getElementById(opts.colorTextId);

        if (!grid || !hiddenInput) return;

        function applyColor(hex) {
            previewBox.style.background = hex + '22';
            previewBox.style.color      = hex;
            if (colorText) colorText.textContent = hex;
        }

        function setIcon(icon, markManual) {
            hiddenInput.value = icon;
            if (markManual) hiddenInput._manual = true;
            previewIco.className = 'bi ' + icon + ' fs-5';
            grid.querySelectorAll('.icon-pick-btn').forEach(b => {
                const active = b.dataset.icon === icon;
                b.classList.toggle('btn-primary', active);
                b.classList.toggle('btn-light',   !active);
            });
        }

        grid.addEventListener('click', e => {
            const btn = e.target.closest('.icon-pick-btn');
            if (btn) setIcon(btn.dataset.icon, true);
        });

        colorInput.addEventListener('input', () => applyColor(colorInput.value));

        // Expose for external use
        hiddenInput._setIcon  = setIcon;
        hiddenInput._setColor = (hex) => {
            colorInput.value = hex;
            applyColor(hex);
        };
        hiddenInput._resetManual = () => { hiddenInput._manual = false; };
        hiddenInput._applyColor = applyColor;

        // Init state
        setIcon(hiddenInput.value || 'bi-wallet2', false);
        applyColor(colorInput.value);

        // Plug into optional name input for live preview
        if (opts.nameId && opts.previewNameId) {
            const nameEl    = document.getElementById(opts.nameId);
            const previewNm = document.getElementById(opts.previewNameId);
            nameEl.addEventListener('input', () => {
                previewNm.textContent = nameEl.value || '—';
            });
        }
        if (opts.jenisId && opts.previewJenisId) {
            const jenisEl    = document.getElementById(opts.jenisId);
            const previewJns = document.getElementById(opts.previewJenisId);
            jenisEl.addEventListener('change', () => {
                previewJns.textContent = jenisEl.options[jenisEl.selectedIndex]?.text || '—';
                // Auto-set icon if not manually chosen
                if (!hiddenInput._manual) {
                    const autoIcon = iconAutoMap[jenisEl.value] || 'bi-wallet2';
                    setIcon(autoIcon, false);
                }
            });
        }
    }

    // ── Init Tambah picker ─────────────────────────────────────────────────
    initIconPicker({
        gridId:       'iconGridTambah',
        hiddenId:     'iconTambah',
        previewBoxId: 'previewBoxTambah',
        previewIconId:'previewIconTambah',
        colorId:      'warnaTambah',
        colorTextId:  'warnaTextTambah',
        nameId:       'tambahNama',
        previewNameId:'previewNamaTambah',
        jenisId:      'tambahJenis',
        previewJenisId:'previewJenisTambah',
    });

    // ── Init Edit picker ───────────────────────────────────────────────────
    initIconPicker({
        gridId:       'iconGridEdit',
        hiddenId:     'iconEdit',
        previewBoxId: 'previewBoxEdit',
        previewIconId:'previewIconEdit',
        colorId:      'warnaEdit',
        colorTextId:  'warnaTextEdit',
        nameId:       'editNama',
        previewNameId:'previewNamaEdit',
    });

    // Reset manual flag when Tambah modal closes
    document.getElementById('modalTambah')?.addEventListener('hidden.bs.modal', () => {
        const h = document.getElementById('iconTambah');
        if (h) h._manual = false;
    });

    // ── Populate Edit modal ────────────────────────────────────────────────
    document.querySelectorAll('.btn-edit-sumber').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = this.dataset;

            document.getElementById('editNama').value  = d.nama  ?? '';
            document.getElementById('editNomor').value = d.nomor ?? '';
            document.getElementById('editBank').value  = d.bank  ?? '';
            document.getElementById('editSaldo').value = d.saldo ?? '0';
            document.getElementById('editJenis').value = d.jenis ?? 'lainnya';

            const iconH = document.getElementById('iconEdit');
            iconH._manual = true;
            iconH._setIcon(d.icon || 'bi-wallet2', false);
            iconH._setColor(d.warna || '#6c757d');

            document.getElementById('previewNamaEdit').textContent = d.nama || '—';
            document.getElementById('formEdit').action = d.url;
        });
    });

    // ── Populate Delete modal ──────────────────────────────────────────────
    document.querySelectorAll('.btn-delete-sumber').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('deleteNama').textContent = this.dataset.nama || '—';
            document.getElementById('formDelete').action      = this.dataset.url;
        });
    });

    // ── Chevron rotation for archive collapse ──────────────────────────────
    const arsipSection = document.getElementById('arsipSection');
    const chevron      = document.getElementById('arsipChevron');
    if (arsipSection && chevron) {
        arsipSection.addEventListener('show.bs.collapse',  () => chevron.style.transform = 'rotate(180deg)');
        arsipSection.addEventListener('hide.bs.collapse',  () => chevron.style.transform = 'rotate(0deg)');
    }

    // ── Re-open Tambah modal on validation error ───────────────────────────
    @if($errors->any() && old('_form') === 'tambah')
        new bootstrap.Modal(document.getElementById('modalTambah')).show();
    @endif

})();
</script>
@endpush
