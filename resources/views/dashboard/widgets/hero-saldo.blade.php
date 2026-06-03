@php
    $saldoTotal  = $summary['saldo_total'] ?? 0;
    $pemasukan   = $summary['transaksi_bulan_ini']['pemasukan'] ?? 0;
    $pengeluaran = $summary['transaksi_bulan_ini']['pengeluaran'] ?? 0;
    $selisih     = $summary['transaksi_bulan_ini']['selisih'] ?? 0;

    // Rasio pengeluaran terhadap pemasukan (untuk bar mini)
    $ratio = $pemasukan > 0 ? min(100, ($pengeluaran / $pemasukan) * 100) : ($pengeluaran > 0 ? 100 : 0);

    // Sparkline cashflow bersih 6 bulan terakhir dari chart_data
    $chart = $summary['chart_data'] ?? ['labels' => [], 'pemasukan' => [], 'pengeluaran' => []];
    $net   = [];
    foreach (($chart['pemasukan'] ?? []) as $idx => $p) {
        $net[] = (float) $p - (float) ($chart['pengeluaran'][$idx] ?? 0);
    }
    $hasTrend = count($net) >= 2 && array_sum(array_map('abs', $net)) > 0;

    // Geometri SVG sparkline
    $spark = '';
    $area  = '';
    $dots  = [];
    if (count($net) >= 2) {
        $n    = count($net);
        $minV = min($net);
        $maxV = max($net);
        $rng  = ($maxV - $minV) ?: 1;
        $H    = 40; $W = 100; $pad = 5;
        $pts  = [];
        foreach ($net as $i => $v) {
            $x = round($i / ($n - 1) * $W, 2);
            $y = round($pad + (1 - ($v - $minV) / $rng) * ($H - 2 * $pad), 2);
            $pts[] = "$x,$y";
            $dots[] = ['x' => $x, 'y' => $y, 'label' => ($chart['labels'][$i] ?? ''), 'val' => $v];
        }
        $spark = implode(' ', $pts);
        $area  = "0,{$H} " . $spark . ",{$W},{$H}";
    }
@endphp

<div class="card h-100 border-0 text-white hero-saldo-card"
     style="background:linear-gradient(135deg,var(--primary) 0%,#217069 100%);border-radius:1rem;">
    <div class="hero-glow"></div>
    <div class="card-body p-4 d-flex flex-column position-relative" style="z-index:1;">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="overflow-hidden">
                <p class="mb-1 small d-flex align-items-center gap-1" style="opacity:.85;">
                    <i class="bi bi-wallet2"></i> Total Saldo
                </p>
                <h2 class="fw-bold mb-0" style="font-size:clamp(1.25rem, 5vw, 2rem);word-break:break-all;">
                    Rp {{ number_format($saldoTotal, 0, ',', '.') }}
                </h2>
            </div>
            <div class="text-end small flex-shrink-0" style="opacity:.85;">
                <div>{{ now()->translatedFormat('F Y') }}</div>
                <div class="mt-1">{{ auth()->user()->household?->nama ?? 'Household' }}</div>
            </div>
        </div>

        {{-- Sparkline cashflow 6 bulan (mengisi ruang tengah agar tinggi penuh) --}}
        <div class="flex-grow-1 d-flex flex-column justify-content-center my-2">
            @if($hasTrend)
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:.62rem;opacity:.7;text-transform:uppercase;letter-spacing:.5px;">Cashflow 6 bulan</span>
                    <span class="hero-trend-chip" style="background:{{ end($net) >= 0 ? 'rgba(134,239,172,.18)' : 'rgba(252,165,165,.18)' }};">
                        <i class="bi bi-{{ end($net) >= 0 ? 'graph-up' : 'graph-down' }}"
                           style="color:{{ end($net) >= 0 ? '#86efac' : '#fca5a5' }};"></i>
                        <span style="color:{{ end($net) >= 0 ? '#86efac' : '#fca5a5' }};">
                            {{ end($net) >= 0 ? 'Surplus' : 'Defisit' }}
                        </span>
                    </span>
                </div>
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="hero-spark" style="width:100%;height:46px;">
                    <defs>
                        <linearGradient id="heroSparkFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%"  stop-color="rgba(255,255,255,.35)"/>
                            <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
                        </linearGradient>
                    </defs>
                    <polygon points="{{ $area }}" fill="url(#heroSparkFill)"/>
                    <polyline points="{{ $spark }}" fill="none" stroke="#fff" stroke-width="1.5"
                              stroke-linejoin="round" stroke-linecap="round" class="hero-spark-line"
                              vector-effect="non-scaling-stroke"/>
                    @foreach($dots as $d)
                        <circle cx="{{ $d['x'] }}" cy="{{ $d['y'] }}" r="1.6" fill="#fff" class="hero-spark-dot">
                            <title>{{ $d['label'] }}: {{ ($d['val'] >= 0 ? '+' : '') . 'Rp ' . number_format($d['val'], 0, ',', '.') }}</title>
                        </circle>
                    @endforeach
                </svg>
            @else
                <div class="text-center py-2" style="opacity:.6;font-size:.72rem;">
                    <i class="bi bi-graph-up d-block mb-1" style="font-size:1.1rem;"></i>
                    Tren cashflow muncul setelah ada transaksi
                </div>
            @endif
        </div>

        <hr style="border-color:rgba(255,255,255,.25);margin:.5rem 0 .85rem;">

        {{-- Stats bulan ini --}}
        <div class="row g-2 g-sm-3">
            <div class="col-4 hero-stat">
                <p class="mb-1 d-flex align-items-center gap-1" style="font-size:.7rem;opacity:.75;">
                    <i class="bi bi-arrow-down-circle" style="color:#86efac;"></i> Pemasukan
                </p>
                <div class="fw-bold" style="font-size:clamp(.75rem, 2.5vw, 1.1rem);word-break:break-all;">
                    Rp {{ number_format($pemasukan, 0, ',', '.') }}
                </div>
            </div>
            <div class="col-4 hero-stat">
                <p class="mb-1 d-flex align-items-center gap-1" style="font-size:.7rem;opacity:.75;">
                    <i class="bi bi-arrow-up-circle" style="color:#fca5a5;"></i> Pengeluaran
                </p>
                <div class="fw-bold" style="font-size:clamp(.75rem, 2.5vw, 1.1rem);word-break:break-all;">
                    Rp {{ number_format($pengeluaran, 0, ',', '.') }}
                </div>
            </div>
            <div class="col-4 hero-stat">
                <p class="mb-1 d-flex align-items-center gap-1" style="font-size:.7rem;opacity:.75;">
                    <i class="bi bi-arrow-left-right"></i> Cashflow
                </p>
                <div class="fw-bold" style="font-size:clamp(.75rem, 2.5vw, 1.1rem);word-break:break-all;color:{{ $selisih >= 0 ? '#86efac' : '#fca5a5' }}">
                    {{ $selisih >= 0 ? '+' : '' }}Rp {{ number_format($selisih, 0, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Bar rasio pemasukan vs pengeluaran --}}
        <div class="hero-ratio mt-3" title="Pengeluaran {{ round($ratio) }}% dari pemasukan">
            <div class="hero-ratio-fill" style="width:{{ $ratio }}%;"></div>
        </div>
        <div class="d-flex justify-content-between mt-1" style="font-size:.58rem;opacity:.7;">
            <span>Rasio pengeluaran</span>
            <span class="fw-semibold">{{ round($ratio) }}%</span>
        </div>

    </div>
</div>

<style>
.hero-saldo-card {
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
}
.hero-saldo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(33,112,105,.35);
}
/* Cahaya dekoratif yang bergerak halus */
.hero-glow {
    position: absolute;
    top: -40%; right: -10%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(255,255,255,.18), transparent 70%);
    pointer-events: none;
    animation: hero-float 8s ease-in-out infinite;
}
@keyframes hero-float {
    0%,100% { transform: translate(0,0); }
    50%     { transform: translate(-12px,14px); }
}

.hero-trend-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .6rem; font-weight: 600;
    padding: 2px 8px; border-radius: 999px;
}

/* Sparkline: garis "tergambar" saat load + titik membesar saat hover */
.hero-spark-line {
    stroke-dasharray: 240;
    stroke-dashoffset: 240;
    animation: hero-draw 1.4s cubic-bezier(.4,0,.2,1) forwards;
}
@keyframes hero-draw { to { stroke-dashoffset: 0; } }
.hero-spark-dot { transition: r .15s ease; cursor: pointer; }
.hero-spark:hover .hero-spark-dot { r: 2.4; }

.hero-stat { transition: transform .14s ease; }
.hero-stat:hover { transform: translateY(-1px); }

.hero-ratio {
    height: 6px; border-radius: 99px;
    background: rgba(255,255,255,.18); overflow: hidden;
}
.hero-ratio-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, #fde68a, #fca5a5);
    transition: width 800ms cubic-bezier(.4,0,.2,1);
    animation: hero-ratio-in 1s cubic-bezier(.4,0,.2,1);
}
@keyframes hero-ratio-in { from { width: 0; } }
</style>
