@extends('layouts.app')
@section('content')
<div class="container py-4 text-center" style="max-width:480px">
    <h1 class="h4 mb-3">Status Pembayaran</h1>
    <div id="billing-status" class="alert alert-info">Memeriksa status pembayaran…</div>
    <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">Kembali ke Paket</a>
</div>
@push('scripts')
<script>
(function () {
    const ref = @json($payment->merchant_ref);
    const box = document.getElementById('billing-status');
    let tries = 0;
    function check() {
        fetch(`{{ url('billing/status') }}/${ref}`, {headers: {'Accept': 'application/json'}})
            .then(r => r.json())
            .then(d => {
                if (d.status === 'paid') {
                    box.className = 'alert alert-success';
                    box.textContent = 'Pembayaran berhasil! Paket Anda sudah aktif.';
                } else if (d.status === 'failed') {
                    box.className = 'alert alert-danger';
                    box.textContent = 'Pembayaran gagal atau kedaluwarsa.';
                } else if (tries++ < 40) {
                    setTimeout(check, 3000);
                } else {
                    box.textContent = 'Masih menunggu konfirmasi. Refresh halaman ini nanti.';
                }
            })
            .catch(() => { if (tries++ < 40) setTimeout(check, 3000); });
    }
    check();
})();
</script>
@endpush
@endsection
