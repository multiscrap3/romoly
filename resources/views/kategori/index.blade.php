@extends('layouts.app')

@section('title', __('kategori.title'))
@section('page-title', __('kategori.title'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
            {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KONFLIK: kategori masih dipakai --}}
    @if(session('konflik_id'))
    @php
        $parts = [];
        if (session('konflik_trx', 0) > 0) $parts[] = session('konflik_trx') . ' transaksi';
        if (session('konflik_ang', 0) > 0)  $parts[] = session('konflik_ang')  . ' anggaran';
    @endphp
    <div class="card mb-3" style="border:2px solid #f59e0b; border-radius:.6rem;">
        <div class="card-body p-3">
            <p class="fw-semibold mb-1" style="color:#f59e0b;">
                &#9888; Kategori <strong>{{ session('konflik_nama') }}</strong>
                masih digunakan dalam {{ implode(' dan ', $parts) }}.
            </p>
            <p class="small text-muted mb-3">
                Pilih kategori pengganti untuk memindahkan semua data tersebut,
                lalu kategori ini akan dihapus. Atau batalkan penghapusan.
            </p>
            <form method="POST" action="{{ session('konflik_url') }}">
                @csrf @method('DELETE')
                <input type="hidden" name="action" value="ganti_kategori">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <select name="replace_kategori_id" class="form-select form-select-sm flex-grow-1" required
                            style="min-width:200px;">
                        <option value="">— Pilih kategori pengganti —</option>
                        @foreach(session('konflik_pengganti', []) as $pg)
                            <option value="{{ $pg['id'] }}">{{ $pg['nama'] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-warning btn-sm fw-medium"
                            onclick="return confirm('{{ __('kategori.delete_confirm') }}')">
                        {{ __('messages.delete') }}
                    </button>
                    <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary btn-sm">
                        {{ __('kategori.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════
         DAFTAR KATEGORI  (tampil pertama)
    ══════════════════════════════════════ --}}
    @php $parents = $kategori->where('parent_id', null); @endphp

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-semibold mb-0">{{ __('kategori.all') }} ({{ $parents->count() }})</h6>
        <button class="btn btn-primary btn-sm" type="button" id="btnToggleTambah" data-tour="kategori-add"
                onclick="var f=document.getElementById('formTambah');f.style.display=f.style.display==='none'?'block':'none';">
            + {{ __('kategori.add') }}
        </button>
    </div>

    @if($parents->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5 mb-4">
            <p class="text-muted small mb-0">{{ __('kategori.no_categories') }}</p>
        </div>
    @else
        @foreach($parents as $kat)
        @php $children = $kategori->where('parent_id', $kat->id)->values(); @endphp
        <div class="mb-2" style="border:1px solid rgba(128,128,128,.3); border-radius:.6rem; overflow:hidden;">

            {{-- Baris utama --}}
            <div class="d-flex align-items-center px-3 py-2 gap-2">
                <div class="flex-grow-1">
                    <span class="fw-medium small">{{ $kat->nama }}</span>
                    <span class="badge ms-1 {{ $kat->jenis==='pemasukan' ? 'bg-success' : 'bg-danger' }}"
                          style="font-size:.65rem;">{{ $kat->jenis }}</span>
                    @if($children->count())
                        <span class="text-muted small ms-1">({{ $children->count() }} sub)</span>
                    @endif
                </div>
                <a href="{{ route('kategori.edit',$kat) }}" class="btn btn-outline-secondary btn-sm">{{ __('messages.edit') }}</a>
                <form method="POST" action="{{ route('kategori.destroy',$kat) }}"
                      onsubmit="return confirm('{{ __('kategori.delete_confirm') }}')" class="m-0">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('messages.delete') }}</button>
                </form>
            </div>

            {{-- Sub-kategori --}}
            @foreach($children as $child)
            <div class="d-flex align-items-center px-3 py-2 gap-2"
                 style="border-top:1px solid rgba(128,128,128,.2); padding-left:2rem!important;">
                <span class="text-muted me-1" style="font-size:.7rem;">&#9492;</span>
                <div class="flex-grow-1 small">{{ $child->nama }}</div>
                <a href="{{ route('kategori.edit',$child) }}" class="btn btn-outline-secondary btn-sm" style="font-size:.75rem;padding:.15rem .5rem;">{{ __('messages.edit') }}</a>
                <form method="POST" action="{{ route('kategori.destroy',$child) }}"
                      onsubmit="return confirm('{{ __('kategori.delete_confirm') }}')" class="m-0">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="font-size:.75rem;padding:.15rem .5rem;">{{ __('messages.delete') }}</button>
                </form>
            </div>
            @endforeach

        </div>
        @endforeach
    @endif

    {{-- ══════════════════════════════════════
         FORM TAMBAH  (tersembunyi, di bawah)
    ══════════════════════════════════════ --}}
    <div id="formTambah" style="display:{{ $errors->any()||old('nama') ? 'block' : 'none' }};" class="mt-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">{{ __('kategori.add') }}</h6>
                <form method="POST" action="{{ route('kategori.store') }}" id="formTambahInner">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-medium mb-1">1. {{ __('kategori.type') }} <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <label class="jenis-btn flex-fill text-center py-2 rounded border small fw-medium" style="cursor:pointer;" id="btnP">
                                <input type="radio" name="jenis" value="pemasukan" class="d-none" {{ old('jenis')==='pemasukan'?'checked':'' }}>
                                &#8593; {{ __('kategori.income') }}
                            </label>
                            <label class="jenis-btn flex-fill text-center py-2 rounded border small fw-medium" style="cursor:pointer;" id="btnK">
                                <input type="radio" name="jenis" value="pengeluaran" class="d-none" {{ old('jenis')==='pengeluaran'?'checked':'' }}>
                                &#8595; {{ __('kategori.expense') }}
                            </label>
                        </div>
                        @error('jenis')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">2. {{ __('kategori.name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama') }}" required maxlength="255"
                               placeholder="{{ __('kategori.name_ph') }}"
                               class="form-control form-control-sm @error('nama') is-invalid @enderror">
                        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">3. {{ __('kategori.parent') }} <span class="text-muted small">(opsional)</span></label>
                        <select name="parent_id" id="selectParent" class="form-select form-select-sm">
                            <option value="">{{ __('kategori.parent_ph') }}</option>
                            @foreach($parents as $opt)
                                <option value="{{ $opt->id }}" data-jenis="{{ $opt->jenis }}" class="opt-parent">
                                    {{ $opt->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('kategori.add') }}</button>
                </form>
            </div>
        </div>
    </div>

</div>
</div>

@push('scripts')
<script>
(function () {
    var opts = [];
    document.querySelectorAll('.opt-parent').forEach(function(o){
        opts.push({id:o.value, jenis:o.dataset.jenis, nama:o.textContent.trim()});
    });
    function refreshJenis() {
        var checked = document.querySelector('#formTambahInner input[name="jenis"]:checked');
        document.querySelectorAll('.jenis-btn').forEach(function(b){
            b.style.background=''; b.style.borderColor='#dee2e6';
        });
        if (!checked) return;
        var lbl = checked.closest('.jenis-btn');
        lbl.style.background  = checked.value==='pemasukan' ? '#d1fae5' : '#fee2e2';
        lbl.style.borderColor = checked.value==='pemasukan' ? '#10b981' : '#ef4444';
        var sel = document.getElementById('selectParent');
        var old = sel.value;
        sel.innerHTML = '<option value="">{{ __('kategori.parent_ph') }}</option>';
        opts.forEach(function(k){
            if (k.jenis!==checked.value) return;
            var o = document.createElement('option');
            o.value=k.id; o.textContent=k.nama;
            if (k.id===old) o.selected=true;
            sel.appendChild(o);
        });
    }
    document.querySelectorAll('#formTambahInner input[name="jenis"]').forEach(function(r){
        r.addEventListener('change', refreshJenis);
    });
    refreshJenis();
})();
</script>
@endpush
@endsection
