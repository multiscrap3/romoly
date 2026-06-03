@extends('layouts.auth')

@section('title', __('auth.login_btn'))

@section('content')
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">{{ __('auth.welcome_back') }}</h3>
        <p class="text-muted mb-0">{{ __('auth.login_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="mb-3">
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

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold text-dark">{{ __('auth.password_label') }}</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-lock text-muted"></i>
                </span>
                <input id="password" type="password" name="password" required
                       class="form-control border-start-0 border-end-0 ps-0"
                       placeholder="{{ __('auth.password_label') }}"
                       style="border-left:none;">
                <button type="button" class="btn btn-outline-secondary border-start-0"
                        onclick="togglePassword('password', this)"
                        aria-label="{{ __('auth.toggle_password') }}"
                        style="border-left:none;">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check mb-0">
                <input type="checkbox" name="remember" value="1"
                       class="form-check-input" id="remember">
                <label class="form-check-label text-muted" for="remember">{{ __('auth.remember_me') }}</label>
            </div>
            <a href="{{ route('password.request') }}" class="text-decoration-none fw-semibold"
               style="color:var(--primary);font-size:.875rem;">{{ __('auth.forgot_password') }}</a>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i>{{ __('auth.login_btn') }}
            </button>
        </div>
    </form>

    <p class="text-center text-muted mb-0" style="font-size:.9rem;">
        {{ __('auth.no_account') }}
        <a href="{{ route('register') }}" class="fw-semibold text-decoration-none" style="color:var(--primary);">{{ __('auth.register_now') }}</a>
    </p>
@endsection

@push('scripts')
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
@endpush
