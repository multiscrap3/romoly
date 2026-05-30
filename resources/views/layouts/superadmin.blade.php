<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Superadmin') - Romoly Admin</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('dompet/icons/bootstrap-icons/font/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/metisMenu/3.0.7/metisMenu.min.css">
    <link rel="stylesheet" href="{{ asset('dompet/css/perfect-scrollbar.css') }}">
    <link rel="stylesheet" href="{{ asset('dompet/css/style.css') }}">

    <style>
        /* Override warna primary ke ungu untuk superadmin */
        :root {
            --primary: #7C3AED;
            --primary-hover: #6D28D9;
            --rgba-primary-1: rgba(124,58,237,.1);
            --nav-headbg: #1e1b4b;
            --sidebar-bg: #1e1b4b;
        }
        .dlabnav { background-color: #1e1b4b; }
        .nav-header { background-color: #1e1b4b; }
        .dlabnav .metismenu > li.mm-active > a,
        .dlabnav .metismenu > li:hover > a { background: rgba(124,58,237,.2); color: #a78bfa; }
        .dlabnav .metismenu a { color: #c4b5fd; }
        .superadmin-badge {
            font-size: .65rem; background: #7C3AED; color: #fff;
            padding: 2px 8px; border-radius: 999px; margin-left: 8px; vertical-align: middle;
        }
    </style>

    @stack('styles')
</head>
<body data-sidebar-style="full" data-layout="vertical" data-header-position="fixed" data-container="wide">

<div id="main-wrapper" class="show">

    {{-- Nav Header --}}
    <div class="nav-header">
        <a href="{{ route('superadmin.dashboard') }}" class="brand-logo">
            <div class="logo-abbr d-flex align-items-center justify-content-center"
                 style="width:47px;height:47px;background:#7C3AED;border-radius:12px;">
                <span style="color:#fff;font-weight:700;font-size:1.2rem;">R</span>
            </div>
            <div class="brand-title ms-2" style="font-size:1.1rem;font-weight:700;color:#c4b5fd;">
                Romoly <span class="superadmin-badge">Admin</span>
            </div>
        </a>
        <div class="nav-control">
            <div class="hamburger">
                <span class="line"></span>
                <span class="line"></span>
                <span class="line"></span>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="header">
        <div class="header-content">
            <nav class="navbar navbar-expand">
                <div class="collapse navbar-collapse justify-content-between">
                    <div class="header-left">
                        <div class="dashboard_bar">@yield('page-title', 'Superadmin')</div>
                    </div>
                    <ul class="navbar-nav header-right">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link" title="Kembali ke App">
                                <i class="bi bi-arrow-left-circle fs-5"></i>
                                <span class="d-none d-md-inline ms-1 small">Kembali ke App</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown header-profile">
                            <a class="nav-link" href="#" data-bs-toggle="dropdown">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle text-white"
                                         style="width:38px;height:38px;background:#7C3AED;font-weight:600;">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                    <span class="d-none d-md-block fw-semibold small">{{ auth()->user()->name }}</span>
                                    <i class="bi bi-chevron-down small text-muted"></i>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Keluar
                                    </button>
                                </form>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="dlabnav">
        <div class="dlabnav-scroll">
            <ul class="metismenu" id="menu">

                <li class="{{ request()->routeIs('superadmin.dashboard') ? 'mm-active' : '' }}">
                    <a href="{{ route('superadmin.dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('superadmin.households*') ? 'mm-active' : '' }}">
                    <a href="{{ route('superadmin.households') }}">
                        <i class="bi bi-house-heart"></i>
                        <span class="nav-text">Households</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('superadmin.users') ? 'mm-active' : '' }}">
                    <a href="{{ route('superadmin.users') }}">
                        <i class="bi bi-people"></i>
                        <span class="nav-text">Users</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('superadmin.logs') ? 'mm-active' : '' }}">
                    <a href="{{ route('superadmin.logs') }}">
                        <i class="bi bi-journal-text"></i>
                        <span class="nav-text">Audit Logs</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('superadmin.health') ? 'mm-active' : '' }}">
                    <a href="{{ route('superadmin.health') }}">
                        <i class="bi bi-heart-pulse"></i>
                        <span class="nav-text">Health Check</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('superadmin.ai-monitor') ? 'mm-active' : '' }}">
                    <a href="{{ route('superadmin.ai-monitor') }}">
                        <i class="bi bi-robot"></i>
                        <span class="nav-text">AI & OCR Monitor</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>

    {{-- Content --}}
    <div class="content-body">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3">
                    <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mt-3">
                    <i class="bi bi-x-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show mt-3">
                    <i class="bi bi-exclamation-triangle me-2"></i> {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')

        </div>

        <div class="footer">
            <div class="copyright">
                <p class="mb-0">
                    &copy; {{ date('Y') }} <strong>Romoly</strong> — Superadmin Panel
                    <span class="ms-2 text-muted" style="font-size:.75rem;">v{{ config('app.version') }}</span>
                </p>
            </div>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/metisMenu/3.0.7/metisMenu.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/perfect-scrollbar/1.5.5/perfect-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $("#menu").metisMenu();
    $(".hamburger").on("click", function () {
        $("body").toggleClass("menu-toggle");
        $(this).toggleClass("is-active");
    });
    setTimeout(function () {
        $(".alert").fadeOut("slow", function () { $(this).remove(); });
    }, 5000);
</script>

@stack('scripts')
</body>
</html>
