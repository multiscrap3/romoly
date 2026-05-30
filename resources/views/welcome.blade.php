<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Romoly') }}</title>
    <link rel="stylesheet" href="{{ asset('dompet/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('dompet/icons/bootstrap-icons/font/bootstrap-icons.css') }}">
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%); display: flex; flex-direction: column; justify-content: center; align-items: center; }
    </style>
</head>
<body>

    <div class="text-center" style="max-width:480px;padding:2rem;">
        <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                 style="width:72px;height:72px;background:#3b82f6;">
                <i class="bi bi-wallet2 text-white" style="font-size:2rem;"></i>
            </div>
            <h1 class="fw-bold fs-3 mb-1">{{ config('app.name', 'Romoly') }}</h1>
            <p class="text-muted">{{ __('messages.footer_tagline') }}</p>
        </div>

        <div class="d-flex gap-3 justify-content-center">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary px-4">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary px-4">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline-primary px-4">
                        <i class="bi bi-person-plus me-1"></i>Daftar
                    </a>
                @endif
            @endauth
        </div>

        <div class="row g-3 mt-5">
            @foreach([
                ['icon' => 'bi-bar-chart-line', 'title' => 'Laporan Lengkap', 'desc' => 'Pantau pemasukan & pengeluaran harian, mingguan, bulanan, dan tahunan.'],
                ['icon' => 'bi-piggy-bank', 'title' => 'Tabungan & Anggaran', 'desc' => 'Atur target tabungan dan anggaran pengeluaran tiap kategori.'],
                ['icon' => 'bi-people', 'title' => 'Multi-Anggota', 'desc' => 'Kelola keuangan bersama seluruh anggota keluarga dalam satu household.'],
            ] as $f)
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
                        <div class="card-body p-3 text-center">
                            <i class="bi {{ $f['icon'] }} text-primary fs-4 mb-2 d-block"></i>
                            <div class="fw-semibold small mb-1">{{ $f['title'] }}</div>
                            <div class="text-muted" style="font-size:.72rem;">{{ $f['desc'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
