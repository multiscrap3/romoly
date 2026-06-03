@extends('layouts.auth')

@section('title', __('auth.verify_title'))

@section('content')
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
             style="width:72px;height:72px;background:rgba(26,115,232,.1);">
            <i class="bi bi-envelope-check" style="font-size:2rem;color:var(--primary);"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">{{ __('auth.verify_title') }}</h3>
        <p class="text-muted mb-0">{{ __('auth.verify_subtitle') }}</p>
    </div>

    <div class="alert alert-light border d-flex align-items-start gap-2 small" role="alert">
        <i class="bi bi-info-circle text-primary mt-1"></i>
        <span>{{ __('auth.verify_hint') }}</span>
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                <i class="bi bi-arrow-repeat me-2"></i>{{ __('auth.verify_resend') }}
            </button>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('dashboard') }}" class="text-decoration-none fw-semibold" style="color:var(--primary);font-size:.9rem;">
            {{ __('auth.verify_continue') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-link text-muted text-decoration-none p-0" style="font-size:.9rem;">
                <i class="bi bi-box-arrow-right me-1"></i>{{ __('auth.verify_logout') }}
            </button>
        </form>
    </div>
@endsection
