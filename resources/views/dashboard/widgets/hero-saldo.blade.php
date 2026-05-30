<div class="card h-100 border-0 text-white"
     style="background:linear-gradient(135deg,var(--primary) 0%,#217069 100%);border-radius:1rem;">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="overflow-hidden">
                <p class="mb-1 small" style="opacity:.85;">Total Saldo</p>
                <h2 class="fw-bold mb-0" style="font-size:clamp(1.25rem, 5vw, 2rem);word-break:break-all;">
                    Rp {{ number_format($summary['saldo_total'] ?? 0, 0, ',', '.') }}
                </h2>
            </div>
            <div class="text-end small flex-shrink-0" style="opacity:.85;">
                <div>{{ now()->translatedFormat('F Y') }}</div>
                <div class="mt-1">{{ auth()->user()->household?->nama ?? 'Household' }}</div>
            </div>
        </div>

        <hr style="border-color:rgba(255,255,255,.25);margin:1.25rem 0 1rem;">

        <div class="row g-2 g-sm-3">
            <div class="col-4">
                <p class="mb-1" style="font-size:.7rem;opacity:.75;">Pemasukan</p>
                <div class="fw-bold" style="font-size:clamp(.75rem, 2.5vw, 1.1rem);word-break:break-all;">
                    Rp {{ number_format($summary['transaksi_bulan_ini']['pemasukan'] ?? 0, 0, ',', '.') }}
                </div>
            </div>
            <div class="col-4">
                <p class="mb-1" style="font-size:.7rem;opacity:.75;">Pengeluaran</p>
                <div class="fw-bold" style="font-size:clamp(.75rem, 2.5vw, 1.1rem);word-break:break-all;">
                    Rp {{ number_format($summary['transaksi_bulan_ini']['pengeluaran'] ?? 0, 0, ',', '.') }}
                </div>
            </div>
            <div class="col-4">
                @php $selisih = $summary['transaksi_bulan_ini']['selisih'] ?? 0; @endphp
                <p class="mb-1" style="font-size:.7rem;opacity:.75;">Cashflow</p>
                <div class="fw-bold" style="font-size:clamp(.75rem, 2.5vw, 1.1rem);word-break:break-all;color:{{ $selisih >= 0 ? '#86efac' : '#fca5a5' }}">
                    {{ $selisih >= 0 ? '+' : '' }}Rp {{ number_format($selisih, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
</div>
