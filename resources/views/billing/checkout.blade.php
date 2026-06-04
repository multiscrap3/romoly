@extends('layouts.app')
@section('page-title', 'Konfirmasi Pembayaran')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-md-7 col-lg-5">

    <div class="mb-3">
        <a href="{{ route('billing.index') }}" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Paket
        </a>
    </div>

    <h1 class="h4 fw-bold mb-4">Konfirmasi Pembayaran</h1>

    {{-- Ringkasan pesanan --}}
    <div class="card shadow-sm mb-4" style="border-radius:1rem">
        <div class="card-body p-4">
            <h2 class="h6 text-muted text-uppercase mb-3" style="letter-spacing:.05em">Ringkasan Pesanan</h2>

            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <p class="fw-semibold mb-0">Romoly {{ $plan->nama }}</p>
                    <small class="text-muted">
                        {{ $period === 'yearly' ? '12 bulan (bayar 10, hemat 2 bulan)' : '1 bulan' }}
                    </small>
                </div>
                <span class="text-muted small">
                    @if($period === 'yearly')
                        Rp {{ number_format((int)$plan->harga, 0, ',', '.') }} × 10
                    @else
                        Rp {{ number_format((int)$plan->harga, 0, ',', '.') }} × 1
                    @endif
                </span>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Total yang dibayar</span>
                <span class="h5 fw-bold mb-0 text-primary">
                    Rp {{ number_format($totalHarga, 0, ',', '.') }}
                </span>
            </div>

            @if($period === 'yearly')
            <div class="mt-2">
                <span class="badge bg-success-subtle text-success">
                    <i class="bi bi-tag me-1"></i>Hemat Rp {{ number_format((int)$plan->harga * 2, 0, ',', '.') }} vs 12 × bulanan
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- Fitur yang didapat --}}
    @php
        $highlights = match($plan->slug) {
            'basic'   => ['Recurring transaksi', 'Target tabungan', 'Hutang & piutang', 'OCR scan struk (50/bln)', 'Export Excel & PDF'],
            'premium' => ['Semua fitur Basic', 'Import mutasi bank', 'AI insights otomatis', 'OCR tak terbatas', 'Anggota tak terbatas', 'Priority support'],
            default   => [],
        };
    @endphp
    @if(count($highlights))
    <div class="card border-0 bg-light mb-4" style="border-radius:1rem">
        <div class="card-body p-3">
            <p class="fw-semibold small mb-2">Yang Anda dapatkan:</p>
            <ul class="list-unstyled mb-0" style="font-size:.85rem">
                @foreach($highlights as $item)
                    <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Form bayar --}}
    <form method="POST" action="{{ route('billing.pay') }}">
        @csrf
        <input type="hidden" name="plan"   value="{{ $plan->slug }}">
        <input type="hidden" name="period" value="{{ $period }}">

        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
            <i class="bi bi-qr-code me-2"></i>Bayar via QRIS
            — Rp {{ number_format($totalHarga, 0, ',', '.') }}
        </button>
    </form>

    {{-- Info keamanan --}}
    <div class="text-center text-muted" style="font-size:.8rem">
        <i class="bi bi-shield-lock me-1"></i>Pembayaran diproses aman oleh Duitku
        · Aktif instan setelah konfirmasi
    </div>

</div>
</div>
@endsection
