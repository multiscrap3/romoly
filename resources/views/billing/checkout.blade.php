@extends('layouts.app')
@section('content')
<div class="container py-4" style="max-width:480px">
    <h1 class="h4 mb-3">Konfirmasi Pembayaran</h1>
    <div class="card mb-3"><div class="card-body">
        <p class="mb-1">Paket: <strong>{{ $plan->nama }}</strong></p>
        <p class="mb-1">Periode: <strong>{{ $period === 'yearly' ? 'Tahunan' : 'Bulanan' }}</strong></p>
        <p class="h5 mb-0">Total: Rp {{ number_format($plan->harga, 0, ',', '.') }}</p>
    </div></div>
    <form method="POST" action="{{ route('billing.pay') }}">
        @csrf
        <input type="hidden" name="plan" value="{{ $plan->slug }}">
        <input type="hidden" name="period" value="{{ $period }}">
        <button type="submit" class="btn btn-primary w-100">Bayar Sekarang</button>
    </form>
    <p class="text-muted small mt-2">Anda akan diarahkan ke halaman pembayaran QRIS.</p>
</div>
@endsection
