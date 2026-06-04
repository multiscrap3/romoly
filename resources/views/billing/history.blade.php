@extends('layouts.app')
@section('page-title', 'Riwayat Pembayaran')

@section('content')
<div class="row">
<div class="col-12">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Riwayat Pembayaran</h1>
            <p class="text-muted mb-0">Semua transaksi langganan Romoly Anda</p>
        </div>
        <a href="{{ route('billing.index') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-stars me-1"></i>Lihat Paket
        </a>
    </div>

    @if($payments->isEmpty())
        <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-receipt text-muted mb-3 d-block" style="font-size:3rem"></i>
                <h2 class="h5 text-muted">Belum ada riwayat pembayaran</h2>
                <p class="text-muted small mb-4">Pilih paket untuk memulai langganan Romoly.</p>
                <a href="{{ route('billing.index') }}" class="btn btn-primary">Lihat Paket</a>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Paket</th>
                                <th>Periode</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="pe-4">Aktif s/d</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $p)
                            <tr>
                                <td class="ps-4 text-nowrap">
                                    {{ $p->created_at->translatedFormat('d M Y') }}
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $p->plan?->nama ?? '-' }}</span>
                                </td>
                                <td>
                                    {{ $p->billing_period === 'yearly' ? 'Tahunan' : 'Bulanan' }}
                                </td>
                                <td class="text-nowrap">
                                    Rp {{ number_format((int)$p->amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    @php
                                        $badge = match($p->status) {
                                            'paid'    => ['bg-success-subtle text-success', 'bi-check-circle-fill', 'Lunas'],
                                            'pending' => ['bg-warning-subtle text-warning', 'bi-hourglass-split', 'Menunggu'],
                                            'failed'  => ['bg-danger-subtle text-danger',   'bi-x-circle-fill',   'Gagal'],
                                            default   => ['bg-secondary-subtle text-secondary', 'bi-question-circle', ucfirst($p->status)],
                                        };
                                    @endphp
                                    <span class="badge {{ $badge[0] }} px-2 py-1">
                                        <i class="bi {{ $badge[1] }} me-1"></i>{{ $badge[2] }}
                                    </span>
                                </td>
                                <td class="pe-4 text-nowrap">
                                    @if($p->status === 'paid' && $p->period_end)
                                        {{ \Carbon\Carbon::parse($p->period_end)->translatedFormat('d M Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($payments->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $payments->links() }}
        </div>
        @endif
    @endif

</div>
</div>
@endsection
