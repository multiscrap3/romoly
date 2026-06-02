@extends('layouts.app')

@section('title', 'Laporan Tag: ' . $tag->nama)
@section('page-title', 'Laporan Tag')

@section('content')
<div class="row g-4">

    {{-- Header Tag --}}
    <div class="col-12">
        <div class="d-flex align-items-center gap-3">
            <span class="rounded-circle flex-shrink-0"
                  style="width:18px;height:18px;background:{{ $tag->warna }};display:inline-block;"></span>
            <h5 class="mb-0 fw-bold">{{ $tag->nama }}</h5>
            <a href="{{ route('laporan.index') }}" class="btn btn-outline-secondary btn-sm ms-auto">
                <i class="bi bi-arrow-left me-1"></i> Laporan
            </a>
        </div>
    </div>

    {{-- Filter Tanggal --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <form method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-sm-auto">
                            <label class="form-label small fw-medium text-muted mb-1">Dari</label>
                            <input type="date" name="dari" value="{{ $dari }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-sm-auto">
                            <label class="form-label small fw-medium text-muted mb-1">Sampai</label>
                            <input type="date" name="sampai" value="{{ $sampai }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                        </div>
                        <div class="col-auto ms-auto align-self-end">
                            <span class="text-muted small">{{ $data['periode'] }}</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #10b981;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Pemasukan</div>
                <div class="fw-bold fs-6 text-success">Rp {{ number_format($data['total_pemasukan'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #ef4444;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Pengeluaran</div>
                <div class="fw-bold fs-6 text-danger">Rp {{ number_format($data['total_pengeluaran'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #3b82f6;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Cashflow</div>
                <div class="fw-bold fs-6 {{ $data['cashflow'] >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($data['cashflow'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #8b5cf6;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Jumlah Transaksi</div>
                <div class="fw-bold fs-6" style="color:#7c3aed;">{{ $data['summary']['total_transaksi'] }}</div>
            </div>
        </div>
    </div>

    {{-- Chart Tren + Breakdown Kategori --}}
    @if($data['summary']['total_transaksi'] > 0)
    <div class="col-12 {{ count($data['per_kategori']) > 0 ? 'col-md-8' : '' }}">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">Tren 6 Bulan Terakhir</h6>
            </div>
            <div class="card-body p-4">
                <canvas id="chartTren" height="200"></canvas>
            </div>
        </div>
    </div>

    @if(count($data['per_kategori']) > 0)
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">Pengeluaran per Kategori</h6>
            </div>
            <div class="card-body p-0">
                @foreach($data['per_kategori'] as $kat)
                <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom">
                    <div>
                        <div class="small fw-medium">{{ $kat['nama'] }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $kat['count'] }} transaksi · {{ $kat['persentase'] }}%</div>
                    </div>
                    <div class="small fw-bold text-danger">Rp {{ number_format($kat['total'], 0, ',', '.') }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Daftar Transaksi --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">Daftar Transaksi ({{ $data['summary']['total_transaksi'] }})</h6>
            </div>
            <div class="card-body p-0">
                @forelse($data['transaksi'] as $t)
                <a href="{{ route('transaksi.show', $t) }}"
                   class="d-flex align-items-center gap-3 px-4 py-3 border-bottom text-decoration-none">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                         style="width:36px;height:36px;background:{{ $t->jenis === 'pemasukan' ? 'rgba(16,185,129,.12)' : ($t->jenis === 'pengeluaran' ? 'rgba(239,68,68,.12)' : 'rgba(59,130,246,.12)') }}">
                        @if($t->jenis === 'pemasukan')
                            <i class="bi bi-arrow-up-circle text-success"></i>
                        @elseif($t->jenis === 'pengeluaran')
                            <i class="bi bi-arrow-down-circle text-danger"></i>
                        @else
                            <i class="bi bi-arrow-left-right text-primary"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-medium text-dark text-truncate">
                            {{ $t->keterangan ?: 'Tanpa keterangan' }}
                        </div>
                        <div class="text-muted d-flex align-items-center gap-1" style="font-size:.72rem;">
                            <span>{{ $t->tanggal->translatedFormat('d M Y') }}</span>
                            @if($t->kategori)<span>&bull;</span><span>{{ $t->kategori->nama }}</span>@endif
                            @if($t->sumberTransaksi)<span>&bull;</span><span>{{ $t->sumberTransaksi->nama }}</span>@endif
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
                    <i class="bi bi-tags fs-1 d-block mb-2 text-muted opacity-25"></i>
                    <p class="text-muted small">Tidak ada transaksi dengan tag ini pada periode ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@if($data['summary']['total_transaksi'] > 0)
<script>
(function () {
    const labels      = @json(collect($data['per_bulan'])->pluck('bulan'));
    const pemasukan   = @json(collect($data['per_bulan'])->pluck('pemasukan'));
    const pengeluaran = @json(collect($data['per_bulan'])->pluck('pengeluaran'));

    new Chart(document.getElementById('chartTren'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: pemasukan,
                    backgroundColor: 'rgba(16,185,129,.7)',
                    borderRadius: 4,
                },
                {
                    label: 'Pengeluaran',
                    data: pengeluaran,
                    backgroundColor: 'rgba(239,68,68,.7)',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: {
                    ticks: {
                        callback: val => 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
                    }
                }
            }
        }
    });
})();
</script>
@endif
@endpush
