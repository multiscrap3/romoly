{{-- Banner pengingat verifikasi email (soft enforcement).
     Tampil di semua halaman app selama email user belum terverifikasi. --}}
@auth
    @if(! auth()->user()->hasVerifiedEmail())
        <div class="alert d-flex flex-wrap align-items-center gap-2 mt-3 mb-0 border-0 shadow-sm"
             role="alert"
             style="background:linear-gradient(135deg,#fff8e1 0%,#fff3cd 100%);border-left:4px solid #f0ad4e !important;">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <i class="bi bi-envelope-exclamation fs-4 text-warning"></i>
                <div>
                    <div class="fw-semibold text-dark" style="font-size:.92rem;">
                        {{ __('auth.verify_title') }}
                    </div>
                    <div class="text-muted" style="font-size:.82rem;">
                        {{ __('auth.verify_hint') }}
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('verification.send') }}" class="mb-0 flex-shrink-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning fw-semibold text-white">
                    <i class="bi bi-arrow-repeat me-1"></i>{{ __('auth.verify_resend') }}
                </button>
            </form>
        </div>
    @endif
@endauth
