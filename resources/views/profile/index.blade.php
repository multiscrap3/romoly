@extends('layouts.app')

@section('title', __('profile.title'))
@section('page-title', __('profile.title'))

@section('content')
<div class="row g-4 justify-content-center">
<div class="col-12 col-lg-8">

    {{-- Avatar + info --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 d-flex align-items-center gap-4">
            <div class="position-relative flex-shrink-0">
                @if($user->photo)
                    <img src="{{ asset('storage/' . $user->photo) }}" alt="Foto profil"
                         class="rounded-circle object-fit-cover"
                         style="width:72px;height:72px;">
                @else
                    <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                         style="width:72px;height:72px;background:#3b82f6;font-size:1.6rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                <div class="text-muted small">{{ $user->email }}</div>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('profile.photo.upload') }}" enctype="multipart/form-data" class="d-inline"
                          onsubmit="return this.querySelector('input[type=file]').files.length > 0">
                        @csrf
                        <label class="btn btn-outline-secondary btn-sm" style="cursor:pointer;">
                            <i class="bi bi-camera me-1"></i>Ganti Foto
                            <input type="file" name="photo" accept="image/*" class="d-none"
                                   onchange="this.closest('form').submit()">
                        </label>
                    </form>
                    @if($user->photo)
                        <form method="POST" action="{{ route('profile.photo.delete') }}" class="d-inline"
                              onsubmit="return confirm('Hapus foto profil?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Foto</button>
                        </form>
                    @endif
                    <a href="{{ route('settings.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-gear me-1"></i>Pengaturan
                    </a>
                </div>
            </div>
            <div class="flex-shrink-0 text-end d-none d-md-block">
                <span class="badge rounded-pill bg-primary" title="{{ __('profile.role') }}">{{ $user->getRoleNames()->map('ucfirst')->implode(', ') ?: 'member' }}</span>
                @if($user->household)
                    <div class="small text-muted mt-1" title="{{ __('profile.household') }}">{{ $user->household->nama }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Update profil --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-4">{{ __('profile.edit') }}</h6>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('profile.name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('profile.email') }} <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="form-control @error('email') is-invalid @enderror">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-medium">No. HP</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                           class="form-control @error('phone') is-invalid @enderror"
                           placeholder="contoh: 08123456789">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ __('profile.save') }}</button>
            </form>
        </div>
    </div>

    {{-- Ubah password --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-4">Ubah Password</h6>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-medium">Password Saat Ini</label>
                    <input type="password" name="current_password" required class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Password Baru</label>
                    <input type="password" name="password" required minlength="8" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-medium">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Ubah Password</button>
            </form>
        </div>
    </div>

    {{-- Hapus akun --}}
    <div class="card border-0 shadow-sm border-danger" style="border-radius:.75rem;border:1px solid #fca5a5 !important;">
        <div class="card-body p-4">
            <h6 class="fw-semibold text-danger mb-2">Hapus Akun</h6>
            <p class="text-muted small mb-3">Tindakan ini tidak dapat dibatalkan. Semua data yang terhubung akan ikut terhapus.</p>
            <form method="POST" action="{{ route('profile.destroy') }}"
                  onsubmit="return confirm('Yakin hapus akun? Tindakan ini permanen.')">
                @csrf @method('DELETE')
                <div class="mb-3">
                    <label class="form-label small fw-medium">Konfirmasi dengan password Anda</label>
                    <input type="password" name="password" required class="form-control form-control-sm" style="max-width:260px;">
                </div>
                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Akun Saya</button>
            </form>
        </div>
    </div>

</div>
</div>
@endsection
