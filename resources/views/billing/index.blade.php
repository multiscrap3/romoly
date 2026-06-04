@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Pilih Paket Langganan</h1>
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row g-3">
        @foreach($plans as $plan)
            <div class="col-md-4">
                <div class="card h-100 {{ $current?->id === $plan->id ? 'border-primary' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5">{{ $plan->nama }}</h2>
                        <p class="h4 mb-3">Rp {{ number_format($plan->harga, 0, ',', '.') }}<small class="text-muted fs-6">/bulan</small></p>
                        @if($current?->id === $plan->id)
                            <span class="badge bg-primary mt-auto">Paket Anda</span>
                        @elseif((float) $plan->harga > 0)
                            <a href="{{ route('billing.checkout', ['plan' => $plan->slug]) }}" class="btn btn-primary mt-auto">Pilih</a>
                        @else
                            <span class="text-muted mt-auto">Gratis</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
