@extends('layouts.app')

@section('title', __('transaksi.edit'))
@section('page-title', __('transaksi.edit'))

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
</style>
@endpush

@section('content')

<form method="POST" action="{{ route('transaksi.update', $transaksi) }}" enctype="multipart/form-data" id="editForm">
@csrf @method('PUT')

<div class="row g-3 align-items-start">

    {{-- ═══════════════════════════════════════════════════════
         KOLOM KIRI — Jenis + Jumlah + Tanggal
    ═══════════════════════════════════════════════════════ --}}
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">

                {{-- Jenis Transaksi --}}
                <div class="section-label">{{ __('transaksi.transaction_type') }}</div>
                <div class="row g-2 mb-4">
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="jenis" id="jenisPengeluaran" value="pengeluaran"
                               {{ old('jenis', $transaksi->jenis) === 'pengeluaran' ? 'checked' : '' }}>
                        <label class="jenis-card card-pengeluaran d-block text-center py-3 px-2" for="jenisPengeluaran">
                            <i class="bi bi-arrow-down-circle-fill text-danger d-block mb-1" style="font-size:1.6rem;"></i>
                            <span class="fw-semibold text-danger d-block" style="font-size:.8rem;">{{ __('transaksi.expense') }}</span>
                        </label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="jenis" id="jenisPemasukan" value="pemasukan"
                               {{ old('jenis', $transaksi->jenis) === 'pemasukan' ? 'checked' : '' }}>
                        <label class="jenis-card card-pemasukan d-block text-center py-3 px-2" for="jenisPemasukan">
                            <i class="bi bi-arrow-up-circle-fill text-success d-block mb-1" style="font-size:1.6rem;"></i>
                            <span class="fw-semibold text-success d-block" style="font-size:.8rem;">{{ __('transaksi.income') }}</span>
                        </label>
                    </div>
                    <div class="col-4">
                        <input type="radio" class="btn-check" name="jenis" id="jenisTransfer" value="transfer"
                               {{ old('jenis', $transaksi->jenis) === 'transfer' ? 'checked' : '' }}>
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
                        <input type="text" inputmode="numeric" name="jumlah"
                               value="{{ old('jumlah', $transaksi->jumlah) }}" required
                               class="jumlah-input currency-input @error('jumlah') border-danger @enderror"
                               style="max-width:220px;"
                               placeholder="0">
                    </div>
                    @error('jumlah')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Tanggal --}}
                <div class="section-label">{{ __('transaksi.date') }}</div>
                <input type="date" name="tanggal"
                       value="{{ old('tanggal', $transaksi->tanggal->format('Y-m-d')) }}" required
                       class="form-control @error('tanggal') is-invalid @enderror">
                @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         KOLOM KANAN — Detail + Bukti + Actions
    ═══════════════════════════════════════════════════════ --}}
    <div class="col-12 col-lg-7 d-flex flex-column gap-3">

        {{-- Card: Detail Transaksi --}}
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <div class="section-label">{{ __('transaksi.detail') }}</div>

                {{-- Keterangan --}}
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('transaksi.description') }}</label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0">
                            <i class="bi bi-chat-left-text text-muted"></i>
                        </span>
                        <input type="text" name="keterangan"
                               value="{{ old('keterangan', $transaksi->keterangan) }}"
                               class="form-control border-start-0 ps-0"
                               placeholder="{{ __('transaksi.description_ph') }}"
                               style="border-left:none;">
                    </div>
                </div>

                {{-- Kategori + Sumber Dana --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('transaksi.category') }}</label>
                        <select name="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror">
                            <option value="">{{ __('transaksi.category_ph') }}</option>
                            @foreach($kategori as $kat)
                                <option value="{{ $kat->id }}" {{ old('kategori_id', $transaksi->kategori_id) == $kat->id ? 'selected' : '' }}>
                                    {{ $kat->nama }}
                                </option>
                                @foreach($kat->children as $child)
                                    <option value="{{ $child->id }}" {{ old('kategori_id', $transaksi->kategori_id) == $child->id ? 'selected' : '' }}>
                                        &nbsp;&nbsp;↳ {{ $child->nama }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-medium">{{ __('transaksi.source') }}</label>
                        <select name="sumber_transaksi_id" class="form-select @error('sumber_transaksi_id') is-invalid @enderror">
                            <option value="">{{ __('transaksi.source_ph') }}</option>
                            @foreach($sumberTransaksi as $s)
                                <option value="{{ $s->id }}" {{ old('sumber_transaksi_id', $transaksi->sumber_transaksi_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('sumber_transaksi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Transfer ke --}}
                <div class="mb-3 {{ old('jenis', $transaksi->jenis) !== 'transfer' ? 'd-none' : '' }}" id="transferKeRow">
                    <label class="form-label fw-medium">{{ __('transaksi.transfer_to') }}</label>
                    <select name="transfer_ke" class="form-select">
                        <option value="">{{ __('transaksi.transfer_to_ph') }}</option>
                        @foreach($sumberTransaksi as $s)
                            <option value="{{ $s->id }}" {{ old('transfer_ke', $transaksi->transfer_ke) == $s->id ? 'selected' : '' }}>
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
                                       {{ in_array($tag->id, old('tags', is_array($transaksi->tags) ? $transaksi->tags : [])) ? 'checked' : '' }}>
                                <label for="tag{{ $tag->id }}">{{ $tag->nama }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Catatan --}}
                <div class="mb-0">
                    <label class="form-label fw-medium">{{ __('transaksi.notes') }}</label>
                    <textarea name="catatan" rows="2" class="form-control"
                              placeholder="{{ __('transaksi.notes_ph') }}">{{ old('catatan', $transaksi->catatan) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Card: Bukti Transaksi --}}
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <div class="section-label">Bukti Transaksi</div>

                @if($transaksi->bukti_transaksi)
                    <div class="mb-3 d-flex align-items-center gap-3">
                        <img src="{{ asset('storage/' . $transaksi->bukti_transaksi) }}" alt="Bukti"
                             class="rounded-3 border"
                             style="width:72px;height:72px;object-fit:cover;">
                        <div class="text-muted small">
                            <i class="bi bi-image me-1"></i>Bukti saat ini tersimpan.<br>
                            Pilih file baru untuk mengganti.
                        </div>
                    </div>
                @endif

                <input type="file" name="bukti_transaksi" accept="image/jpeg,image/png,image/webp"
                       class="form-control">
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex align-items-center justify-content-between pb-2">
            <a href="{{ route('transaksi.show', $transaksi) }}"
               class="btn btn-light px-4 fw-medium">
                <i class="bi bi-arrow-left me-1"></i>{{ __('transaksi.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary px-5 fw-semibold">
                <i class="bi bi-check-lg me-2"></i>{{ __('transaksi.save') }}
            </button>
        </div>

    </div>{{-- /col kanan --}}

</div>{{-- /row --}}
</form>

@push('scripts')
<script>
document.querySelectorAll('input[name="jenis"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.getElementById('transferKeRow').classList.toggle('d-none', this.value !== 'transfer');
    });
});
</script>
@endpush
@endsection
