@extends('layouts.app')

@section('title', __('transaksi.title'))
@section('page-title', __('transaksi.title'))

@section('content')
<div class="row g-4 mt-1">

    {{-- Summary bar --}}
    <div class="col-12">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius:.75rem;border-left:4px solid #10b981!important;">
                    <div class="card-body py-3 px-4">
                        <div class="small text-success fw-medium mb-1">{{ __('transaksi.total_income') }}</div>
                        <h5 class="fw-bold text-success mb-0">Rp {{ number_format($summary['total_pemasukan'] ?? 0, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius:.75rem;border-left:4px solid #ef4444!important;">
                    <div class="card-body py-3 px-4">
                        <div class="small text-danger fw-medium mb-1">{{ __('transaksi.total_expense') }}</div>
                        <h5 class="fw-bold text-danger mb-0">Rp {{ number_format($summary['total_pengeluaran'] ?? 0, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius:.75rem;border-left:4px solid #3b82f6!important;">
                    <div class="card-body py-3 px-4">
                        <div class="small text-primary fw-medium mb-1">{{ __('transaksi.net_balance') }}</div>
                        <h5 class="fw-bold text-primary mb-0">Rp {{ number_format(($summary['total_pemasukan'] ?? 0) - ($summary['total_pengeluaran'] ?? 0), 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('transaksi.create') }}" class="btn btn-primary btn-sm" data-tour="transaksi-add">
                <i class="bi bi-plus-lg me-1"></i> {{ __('transaksi.add') }}
            </a>
            <button class="btn btn-outline-secondary btn-sm" type="button"
                    data-tour="transaksi-filter"
                    data-bs-toggle="collapse" data-bs-target="#filterPanel">
                <i class="bi bi-funnel me-1"></i> {{ __('transaksi.filter') }}
                @if(request()->hasAny(['jenis', 'kategori_id', 'tanggal_dari', 'tanggal_sampai', 'search']))
                    <span class="badge bg-primary ms-1">{{ __('transaksi.filter_active') }}</span>
                @endif
            </button>
            @if(request()->hasAny(['jenis', 'kategori_id', 'tanggal_dari', 'tanggal_sampai', 'search']))
                <a href="{{ route('transaksi.index') }}" class="btn btn-link btn-sm text-danger p-0">
                    <i class="bi bi-x-circle me-1"></i>{{ __('transaksi.reset_filter') }}
                </a>
            @endif
        </div>

        {{-- Filter panel collapse --}}
        <div class="collapse {{ request()->hasAny(['jenis','kategori_id','tanggal_dari','tanggal_sampai','search']) ? 'show' : '' }} mt-3"
             id="filterPanel">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body p-4">
                    <form method="GET" class="row g-3">
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label small fw-medium">{{ __('transaksi.filter_type') }}</label>
                            <select name="jenis" class="form-select form-select-sm">
                                <option value="">{{ __('transaksi.all') }}</option>
                                <option value="pemasukan"  {{ request('jenis') === 'pemasukan'  ? 'selected' : '' }}>{{ __('transaksi.income') }}</option>
                                <option value="pengeluaran"{{ request('jenis') === 'pengeluaran'? 'selected' : '' }}>{{ __('transaksi.expense') }}</option>
                                <option value="transfer"   {{ request('jenis') === 'transfer'   ? 'selected' : '' }}>{{ __('transaksi.transfer') }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label small fw-medium">{{ __('transaksi.filter_category') }}</label>
                            <select name="kategori_id" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach($kategori as $kat)
                                    <option value="{{ $kat->id }}" {{ request('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label small fw-medium">{{ __('transaksi.filter_search') }}</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   class="form-control form-control-sm" placeholder="{{ __('transaksi.filter_search_ph') }}">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small fw-medium">{{ __('transaksi.filter_from') }}</label>
                            <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small fw-medium">{{ __('transaksi.filter_to') }}</label>
                            <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-lg-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('transaksi.apply') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Duplicate warning --}}
    @if(session('warning_duplicate'))
        <div class="col-12">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>{{ __('transaksi.duplicate_warning') }}</strong> {{ session('warning_duplicate.message') }}
            </div>
        </div>
    @endif

    {{-- Transaksi list --}}
    <div class="col-12" data-tour="transaksi-list">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-0">
                @forelse($transaksi as $t)
                    <a href="{{ route('transaksi.show', $t) }}"
                       class="d-flex align-items-center gap-3 px-4 py-3 border-bottom text-decoration-none"
                       style="transition:.15s;">
                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                             style="width:42px;height:42px;
                             background:{{ $t->jenis === 'pemasukan' ? 'rgba(16,185,129,.12)' : ($t->jenis === 'pengeluaran' ? 'rgba(239,68,68,.12)' : 'rgba(59,130,246,.12)') }}">
                            @if($t->jenis === 'pemasukan')
                                <i class="bi bi-arrow-up-circle text-success fs-5"></i>
                            @elseif($t->jenis === 'pengeluaran')
                                <i class="bi bi-arrow-down-circle text-danger fs-5"></i>
                            @else
                                <i class="bi bi-arrow-left-right text-primary fs-5"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="small fw-medium text-dark text-truncate">
                                {{ $t->keterangan ?: __('transaksi.no_description') }}
                            </div>
                            <div class="text-muted d-flex align-items-center gap-1" style="font-size:.72rem;">
                                <span>{{ $t->tanggal->translatedFormat('d M Y') }}</span>
                                @if($t->kategori) <span>&bull;</span><span>{{ $t->kategori->nama }}</span> @endif
                                @if($t->sumberTransaksi) <span>&bull;</span><span>{{ $t->sumberTransaksi->nama }}</span> @endif
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="small fw-bold {{ $t->jenis === 'pemasukan' ? 'text-success' : ($t->jenis === 'pengeluaran' ? 'text-danger' : 'text-primary') }}">
                                {{ $t->jenis === 'pemasukan' ? '+' : ($t->jenis === 'pengeluaran' ? '-' : '') }}Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                            </div>
                            <div class="text-muted" style="font-size:.7rem;">{{ $t->user?->name }}</div>
                        </div>
                    </a>
                @empty
                    <div class="py-5 text-center">
                        <i class="bi bi-receipt fs-1 d-block mb-2 text-muted opacity-25"></i>
                        <p class="text-muted small mb-2">{{ __('transaksi.no_transactions') }}</p>
                        <a href="{{ route('transaksi.create') }}" class="small text-primary fw-medium text-decoration-none">
                            {{ __('transaksi.add_first') }}
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if($transaksi->hasPages())
        <div class="col-12">
            {{ $transaksi->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif

</div>
@endsection
