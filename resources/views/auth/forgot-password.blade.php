@extends('layouts.auth')

@section('title', __('auth.forgot_title'))

@section('content')
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">{{ __('auth.forgot_title') }}</h3>
        <p class="text-muted mb-0">{{ __('auth.forgot_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label fw-semibold text-dark">{{ __('auth.email_label') }}</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-envelope text-muted"></i>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror"
                       placeholder="email@example.com"
                       style="border-left:none;">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                <i class="bi bi-send me-2"></i>{{ __('auth.send_reset_link') }}
            </button>
        </div>
    </form>

    <p class="text-center text-muted mb-0" style="font-size:.9rem;">
        <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color:var(--primary);">
            <i class="bi bi-arrow-left me-1"></i>{{ __('auth.back_to_login') }}
        </a>
    </p>
@endsection
