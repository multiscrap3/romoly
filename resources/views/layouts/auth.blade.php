<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('auth.login_btn')) - Romoly</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('dompet/icons/bootstrap-icons/font/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('dompet/css/style.css') }}">

    <style>
        /* Mobile/tablet (< lg): jangan paksa form ke tengah vertikal — mulai dari atas
           agar konten bawah (link Daftar/Masuk) tetap terlihat & bisa di-scroll. */
        @media (max-width: 991.98px) {
            /* Template men-set body{height:100%}, ditambah rantai flex min-height:100vh,
               membuat dokumen ke-pin setinggi layar → form panjang (halaman daftar)
               meluber ke bawah tanpa bisa di-scroll. Di mobile kita kembalikan ke
               block flow normal + bebaskan tinggi html/body supaya bisa di-scroll. */
            html, body {
                height: auto !important;
                min-height: 100vh;
                overflow-y: auto !important;
            }
            .authincation { display: block !important; min-height: 0 !important; }
            .authincation .container-fluid,
            .authincation .row { min-height: auto !important; }

            .auth-form-panel {
                display: block !important;
                min-height: 100vh;            /* tetap penuhi layar untuk background */
            }
            .auth-form-wrap {
                margin-left: auto;
                margin-right: auto;
                padding: 28px 20px 40px !important;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

<div class="authincation" style="min-height:100vh;">
    <div class="container-fluid p-0" style="min-height:100vh;">
        <div class="row g-0" style="min-height:100vh;">

            {{-- Panel Kiri --}}
            <div class="col-xl-6 col-lg-6 d-none d-lg-flex flex-column justify-content-between"
                 style="background:linear-gradient(150deg,#1a73e8 0%,#0d47a1 100%);min-height:100vh;">
                <div class="px-5 pt-5">
                    <a href="/" class="text-decoration-none d-flex align-items-center gap-3 mb-5">
                        <div class="d-flex align-items-center justify-content-center rounded-3"
                             style="width:48px;height:48px;background:rgba(255,255,255,0.2);">
                            <i class="bi bi-wallet2 text-white" style="font-size:1.4rem;"></i>
                        </div>
                        <span class="text-white fw-bold fs-4">Romoly</span>
                    </a>

                    <h2 class="text-white fw-bold mb-3" style="font-size:2rem;line-height:1.3;">
                        {!! nl2br(e(__('auth.tagline'))) !!}
                    </h2>
                    <p style="color:rgba(255,255,255,.75);font-size:1rem;">
                        {{ __('auth.tagline_sub') }}
                    </p>

                    <div class="mt-5 d-flex flex-column gap-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:44px;height:44px;background:rgba(255,255,255,0.15);">
                                <i class="bi bi-bar-chart-fill text-white fs-5"></i>
                            </div>
                            <div>
                                <div class="text-white fw-semibold">{{ __('auth.feature_reports') }}</div>
                                <div style="color:rgba(255,255,255,.65);font-size:.875rem;">{{ __('auth.feature_reports_sub') }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:44px;height:44px;background:rgba(255,255,255,0.15);">
                                <i class="bi bi-people-fill text-white fs-5"></i>
                            </div>
                            <div>
                                <div class="text-white fw-semibold">{{ __('auth.feature_family') }}</div>
                                <div style="color:rgba(255,255,255,.65);font-size:.875rem;">{{ __('auth.feature_family_sub') }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                 style="width:44px;height:44px;background:rgba(255,255,255,0.15);">
                                <i class="bi bi-cash-stack text-white fs-5"></i>
                            </div>
                            <div>
                                <div class="text-white fw-semibold">{{ __('auth.feature_savings') }}</div>
                                <div style="color:rgba(255,255,255,.65);font-size:.875rem;">{{ __('auth.feature_savings_sub') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="px-5 pb-4 mb-0" style="color:rgba(255,255,255,.45);font-size:.8rem;">
                    &copy; {{ date('Y') }} Romoly. {{ __('messages.all_rights') }}
                    &nbsp;&middot;&nbsp; v{{ config('app.version') }}
                    &nbsp;&middot;&nbsp;
                    <a href="{{ route('privacy.policy') }}" style="color:rgba(255,255,255,.45);">{{ __('messages.privacy_policy') }}</a>
                    &nbsp;&middot;&nbsp;
                    <a href="{{ route('privacy.terms') }}" style="color:rgba(255,255,255,.45);">{{ __('messages.terms') }}</a>
                </p>
            </div>

            {{-- Panel Kanan - Form --}}
            <div class="col-xl-6 col-lg-6 col-12 d-flex align-items-center justify-content-center auth-form-panel"
                 style="background:#f8f9fa;min-height:100vh;">
                <div class="w-100 auth-form-wrap" style="max-width:480px;padding:40px 24px;">

                    {{-- Logo mobile --}}
                    <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-3"
                             style="width:36px;height:36px;background:var(--primary);">
                            <i class="bi bi-wallet2 text-white"></i>
                        </div>
                        <span class="fw-bold fs-5" style="color:var(--primary);">Romoly</span>
                    </div>

                    {{-- Error Validasi --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Flash Success --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Flash Warning --}}
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Flash Info --}}
                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle me-2"></i> {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Cegah double-submit: sekali form dikirim, kunci tombol submit + tampilkan spinner.
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.dataset.submitting === '1') { e.preventDefault(); return; }
    form.dataset.submitting = '1';
    var btn = form.querySelector('button[type="submit"], [type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + (btn.textContent || '').trim();
    }
}, true);

// Jika user kembali ke halaman lewat tombol back (bfcache), buka kunci tombol lagi.
window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-submitting="1"]').forEach(function (form) {
        form.dataset.submitting = '0';
        var btn = form.querySelector('button[type="submit"], [type="submit"]');
        if (btn && btn.dataset.originalHtml) {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml;
        }
    });
});
</script>

@stack('scripts')
</body>
</html>
