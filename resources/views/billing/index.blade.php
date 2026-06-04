@extends('layouts.app')
@section('page-title', 'Paket Langganan')

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">Pilih Paket Langganan</h1>
            <p class="text-muted mb-0">Paket fleksibel untuk semua ukuran keluarga</p>
        </div>
        <a href="{{ route('billing.history') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i>Riwayat
        </a>
    </div>

    {{-- Alert: langganan akan habis --}}
    @if($expirySoon && $daysLeft !== null && $daysLeft >= 0)
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 flex-shrink-0"></i>
            <div>
                <strong>Langganan segera berakhir.</strong>
                Paket <strong>{{ $current->nama }}</strong> Anda akan berakhir dalam
                <strong>{{ $daysLeft }} hari</strong>
                ({{ $household->subscription_end->translatedFormat('d F Y') }}).
                Perpanjang sekarang agar tidak terputus.
            </div>
        </div>
    @endif

    {{-- Alert: sudah tidak aktif --}}
    @if($household && $household->subscription_end && $household->subscription_end->isPast() && $current?->slug !== 'free')
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-x-circle-fill me-2 fs-5 flex-shrink-0"></i>
            <div>Langganan Anda telah berakhir. Silakan pilih paket di bawah untuk melanjutkan akses penuh.</div>
        </div>
    @endif

    {{-- Toggle Bulanan / Tahunan --}}
    <div class="text-center mb-4">
        <div class="btn-group" role="group" aria-label="Periode langganan">
            <input type="radio" class="btn-check" name="periode" id="periodeMonthly" value="monthly" checked>
            <label class="btn btn-outline-primary" for="periodeMonthly">Bulanan</label>

            <input type="radio" class="btn-check" name="periode" id="periodeYearly" value="yearly">
            <label class="btn btn-outline-primary" for="periodeYearly">
                Tahunan
                <span class="badge bg-success ms-1 text-white" style="font-size:.65rem">Hemat 17%</span>
            </label>
        </div>
        <p class="text-muted small mt-1 mb-0 js-yearly-note d-none">
            Bayar 10 bulan, gunakan 12 bulan
        </p>
    </div>

    {{-- Kartu Paket --}}
    <div class="row g-3 mb-4">
        @foreach($plans as $plan)
        @php
            $isActive     = $current?->id === $plan->id;
            $isFree       = (float)$plan->harga <= 0;
            $isPremium    = $plan->slug === 'premium';
            $hargaMonthly = (int)$plan->harga;
            $hargaYearly  = (int)($hargaMonthly * 10);
            $fitur        = is_array($plan->fitur) ? $plan->fitur : (json_decode($plan->fitur, true) ?? []);
        @endphp
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm {{ $isPremium ? 'border-warning border-2' : ($isActive ? 'border-primary border-2' : '') }}"
                 style="border-radius:1rem;position:relative;">

                @if($isPremium)
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge bg-warning text-dark px-3 py-1" style="border-radius:1rem;font-size:.75rem">
                            <i class="bi bi-stars me-1"></i>Terpopuler
                        </span>
                    </div>
                @endif

                @if($isActive)
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-primary px-2 py-1" style="font-size:.7rem">
                            <i class="bi bi-check-circle me-1"></i>Paket Anda
                        </span>
                    </div>
                @endif

                <div class="card-body d-flex flex-column p-4"
                     style="padding-top:{{ $isPremium ? '2rem' : '1.5rem' }} !important">

                    {{-- Nama & harga --}}
                    <h2 class="h5 fw-bold mb-1">{{ $plan->nama }}</h2>

                    @if($isFree)
                        <div class="mb-3">
                            <span class="display-6 fw-bold">Gratis</span>
                        </div>
                    @else
                        <div class="mb-1 js-price-monthly">
                            <span class="display-6 fw-bold">Rp {{ number_format($hargaMonthly, 0, ',', '.') }}</span>
                            <small class="text-muted">/bulan</small>
                        </div>
                        <div class="mb-1 js-price-yearly d-none">
                            <span class="display-6 fw-bold">Rp {{ number_format($hargaYearly, 0, ',', '.') }}</span>
                            <small class="text-muted">/tahun</small>
                            <br><small class="text-success">≈ Rp {{ number_format((int)($hargaYearly/12), 0, ',', '.') }}/bln</small>
                        </div>
                    @endif

                    {{-- Limit ringkas --}}
                    <p class="text-muted small mb-3">
                        @if($plan->max_anggota == -1) Anggota tak terbatas
                        @else {{ $plan->max_anggota }} anggota @endif
                        ·
                        @if($plan->max_transaksi == -1) Transaksi tak terbatas
                        @else {{ number_format($plan->max_transaksi, 0, ',', '.') }} transaksi/bln @endif
                    </p>

                    {{-- Fitur list --}}
                    @php
                        $fiturList = [
                            ['key' => null,              'label' => 'Transaksi, kategori & anggaran'],
                            ['key' => null,              'label' => 'Laporan bulanan & tahunan'],
                            ['key' => null,              'label' => 'Multi sumber dana'],
                            ['key' => 'recurring',       'label' => 'Transaksi berulang (recurring)'],
                            ['key' => 'tabungan',        'label' => 'Target tabungan'],
                            ['key' => 'hutang_piutang',  'label' => 'Hutang & piutang'],
                            ['key' => 'ocr',             'label' => 'OCR scan struk (' . ($plan->max_ocr == -1 ? '∞' : $plan->max_ocr) . '/bln)'],
                            ['key' => 'import_bank',     'label' => 'Import mutasi bank (CSV/Excel)'],
                            ['key' => 'ai_insights',     'label' => 'AI insights & analisis otomatis'],
                            ['key' => 'export_excel',    'label' => 'Export Excel & PDF'],
                            ['key' => 'priority_support','label' => 'Prioritas dukungan'],
                        ];
                    @endphp
                    <ul class="list-unstyled mb-4 flex-grow-1" style="font-size:.875rem">
                        @foreach($fiturList as $f)
                        @php $ada = $f['key'] === null ? true : ($fitur[$f['key']] ?? false); @endphp
                        <li class="mb-1 {{ $ada ? '' : 'text-muted' }}">
                            @if($ada)
                                <i class="bi bi-check-lg text-success me-2"></i>{{ $f['label'] }}
                            @else
                                <i class="bi bi-x-lg me-2 text-danger opacity-50" style="font-size:.75rem"></i><s style="opacity:.5">{{ $f['label'] }}</s>
                            @endif
                        </li>
                        @endforeach
                    </ul>

                    {{-- CTA --}}
                    @if($isActive)
                        <div class="btn btn-outline-primary disabled w-100">
                            <i class="bi bi-check-circle me-1"></i>Paket Aktif
                            @if($household?->subscription_end)
                                <br><small style="font-size:.75rem">s/d {{ $household->subscription_end->translatedFormat('d M Y') }}</small>
                            @endif
                        </div>
                    @elseif($isFree)
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100">Mulai Gratis</a>
                    @else
                        <a href="{{ route('billing.checkout', ['plan' => $plan->slug]) }}"
                           class="btn {{ $isPremium ? 'btn-warning text-dark' : 'btn-primary' }} w-100 js-checkout-btn"
                           data-slug="{{ $plan->slug }}">
                            Pilih {{ $plan->nama }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Catatan bawah --}}
    <div class="text-center text-muted small mb-4">
        <i class="bi bi-shield-check me-1"></i>Pembayaran aman via QRIS ·
        Aktif instan setelah konfirmasi ·
        <a href="{{ route('billing.history') }}" class="text-muted">Lihat riwayat pembayaran</a>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const monthly    = document.getElementById('periodeMonthly');
    const yearly     = document.getElementById('periodeYearly');
    const yearlyNote = document.querySelector('.js-yearly-note');

    function toggle() {
        const isYearly = yearly.checked;
        document.querySelectorAll('.js-price-monthly').forEach(el => el.classList.toggle('d-none', isYearly));
        document.querySelectorAll('.js-price-yearly').forEach(el => el.classList.toggle('d-none', !isYearly));
        if (yearlyNote) yearlyNote.classList.toggle('d-none', !isYearly);

        document.querySelectorAll('.js-checkout-btn').forEach(btn => {
            const slug   = btn.dataset.slug;
            const period = isYearly ? 'yearly' : 'monthly';
            btn.href     = `/billing/checkout/${slug}?period=${period}`;
        });
    }

    if (monthly) monthly.addEventListener('change', toggle);
    if (yearly)  yearly.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
