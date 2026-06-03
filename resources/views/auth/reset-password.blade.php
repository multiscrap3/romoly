@extends('layouts.auth')

@section('title', __('auth.reset_title'))

@section('content')
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">{{ __('auth.reset_title') }}</h3>
        <p class="text-muted mb-0">{{ __('auth.reset_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold text-dark">{{ __('auth.email_label') }}</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-envelope text-muted"></i>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                       class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror"
                       placeholder="email@example.com"
                       style="border-left:none;">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold text-dark">{{ __('auth.new_password') }}</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-lock text-muted"></i>
                </span>
                <input id="password" type="password" name="password" required
                       class="form-control border-start-0 border-end-0 ps-0 @error('password') is-invalid @enderror"
                       placeholder="{{ __('auth.min_8_chars') }}"
                       style="border-left:none;">
                <button type="button" class="btn btn-outline-secondary border-start-0"
                        onclick="togglePassword('password', this)"
                        style="border-left:none;">
                    <i class="bi bi-eye"></i>
                </button>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-semibold text-dark">{{ __('auth.confirm_password') }}</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-lock-fill text-muted"></i>
                </span>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="form-control border-start-0 border-end-0 ps-0"
                       placeholder="{{ __('auth.repeat_password') }}"
                       style="border-left:none;">
                <button type="button" class="btn btn-outline-secondary border-start-0"
                        onclick="togglePassword('password_confirmation', this)"
                        style="border-left:none;">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                <i class="bi bi-shield-check me-2"></i>{{ __('auth.reset_btn') }}
            </button>
        </div>
    </form>

    <p class="text-center text-muted mb-0" style="font-size:.9rem;">
        <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color:var(--primary);">
            <i class="bi bi-arrow-left me-1"></i>{{ __('auth.back_to_login') }}
        </a>
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
