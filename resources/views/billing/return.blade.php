@extends('layouts.app')
@section('page-title', 'Status Pembayaran')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-md-6 col-lg-5">

    <div class="card shadow-sm text-center" style="border-radius:1rem">
        <div class="card-body p-5">

            {{-- Ikon status (diupdate via JS) --}}
            <div id="status-icon" class="mb-3" style="font-size:3.5rem">
                <div class="spinner-border text-primary" style="width:3.5rem;height:3.5rem" role="status">
                    <span class="visually-hidden">Memuat...</span>
                </div>
            </div>

            <h1 class="h5 fw-bold mb-2" id="status-title">Memeriksa pembayaran…</h1>
            <p class="text-muted mb-4" id="status-desc">Mohon tunggu, kami sedang mengkonfirmasi pembayaran Anda.</p>

            {{-- Info transaksi --}}
            <div class="bg-light rounded-3 p-3 mb-4 text-start" style="font-size:.85rem">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">No. Transaksi</span>
                    <span class="font-monospace small">{{ Str::after($payment->merchant_ref, 'ROM-') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Paket</span>
                    <span class="fw-semibold">{{ $payment->plan->nama }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Total</span>
                    <span class="fw-semibold">Rp {{ number_format((int)$payment->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Tombol aksi (diupdate via JS) --}}
            <div id="status-actions">
                <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">
                    Kembali ke Paket
                </a>
            </div>

        </div>
    </div>

    <div class="text-center mt-3 text-muted small">
        <i class="bi bi-shield-lock me-1"></i>Pembayaran aman via Duitku
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const ref     = @json($payment->merchant_ref);
    const icon    = document.getElementById('status-icon');
    const title   = document.getElementById('status-title');
    const desc    = document.getElementById('status-desc');
    const actions = document.getElementById('status-actions');
    const urlDashboard = @json(route('dashboard'));
    const urlHistory   = @json(route('billing.history'));
    const urlBilling   = @json(route('billing.index'));
    let tries = 0;

    function setSuccess() {
        icon.innerHTML    = '<i class="bi bi-check-circle-fill text-success"></i>';
        title.textContent = 'Pembayaran Berhasil!';
        desc.textContent  = 'Paket Anda sudah aktif. Selamat menikmati Romoly!';
        actions.innerHTML =
            `<a href="${urlDashboard}" class="btn btn-primary me-2">
                <i class="bi bi-house me-1"></i>Ke Dashboard
             </a>
             <a href="${urlHistory}" class="btn btn-outline-secondary">Riwayat</a>`;
        setTimeout(() => { window.location.href = urlDashboard; }, 4000);
    }

    function setFailed() {
        icon.innerHTML    = '<i class="bi bi-x-circle-fill text-danger"></i>';
        title.textContent = 'Pembayaran Gagal';
        desc.textContent  = 'Pembayaran tidak berhasil atau kedaluwarsa. Silakan coba lagi.';
        actions.innerHTML =
            `<a href="${urlBilling}" class="btn btn-primary me-2">Coba Lagi</a>
             <a href="${urlHistory}" class="btn btn-outline-secondary">Riwayat</a>`;
    }

    function setWaiting() {
        icon.innerHTML    = '<i class="bi bi-hourglass-split text-warning" style="font-size:3.5rem"></i>';
        title.textContent = 'Masih menunggu konfirmasi';
        desc.textContent  = 'Jika sudah bayar, refresh halaman ini dalam beberapa menit.';
        actions.innerHTML =
            `<button onclick="location.reload()" class="btn btn-outline-primary me-2">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
             </button>
             <a href="${urlBilling}" class="btn btn-outline-secondary">Kembali</a>`;
    }

    function check() {
        fetch(`/billing/status/${ref}`, {headers: {'Accept': 'application/json'}})
            .then(r => r.json())
            .then(d => {
                if (d.status === 'paid')        { setSuccess(); }
                else if (d.status === 'failed') { setFailed(); }
                else if (tries++ < 40)          { setTimeout(check, 3000); }
                else                            { setWaiting(); }
            })
            .catch(() => { if (tries++ < 40) setTimeout(check, 3000); });
    }

    check();
})();
</script>
@endpush
