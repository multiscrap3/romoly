@extends('layouts.superadmin')

@section('title', __('superadmin.users'))
@section('page-title', __('superadmin.users'))

@section('content')
<div class="row g-4">

    {{-- Filter --}}
    <div class="col-12">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('superadmin.search') }}"
                   class="form-control form-control-sm" style="max-width:260px;">
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="">Semua Status</option>
                <option value="aktif"    {{ request('status') === 'aktif'    ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('superadmin.search') }}</button>
        </form>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('superadmin.name') }}</th>
                            <th>{{ __('superadmin.email') }}</th>
                            <th>{{ __('superadmin.households') }}</th>
                            <th>Role</th>
                            <th>{{ __('superadmin.status') }}</th>
                            <th>{{ __('superadmin.created') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="fw-medium">{{ $user->name }}</td>
                                <td class="text-muted">{{ $user->email }}</td>
                                <td>
                                    @if($user->household)
                                        <a href="{{ route('superadmin.household-show', $user->household) }}" class="text-primary text-decoration-none">
                                            {{ $user->household->nama }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $user->getRoleNames()->implode(', ') ?: '-' }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                                <td>
                                    @if(!$user->hasRole('superadmin'))
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <form method="POST" action="{{ route('superadmin.users.toggle-status', $user) }}" class="d-inline">
                                                @csrf @method('PUT')
                                                <button type="submit"
                                                        class="btn btn-link btn-sm p-0 {{ $user->is_active ? 'text-danger' : 'text-success' }}"
                                                        style="font-size:.78rem;">
                                                    {{ $user->is_active ? __('superadmin.ban') : __('superadmin.unban') }}
                                                </button>
                                            </form>
                                            <span class="text-muted" style="font-size:.7rem;">|</span>
                                            <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}" class="d-inline js-delete-user"
                                                  data-confirm="{{ __('superadmin.delete_confirm', ['name' => $user->name]) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-danger" style="font-size:.78rem;">
                                                    {{ __('superadmin.delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size:.72rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top px-4 py-3" style="border-radius:0 0 .75rem .75rem;">
                {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Konfirmasi hapus user. Pesan diambil dari data-confirm (string murni),
    // bukan di-inline ke JS, agar nama user yang dikontrol pengguna tidak
    // bisa menjadi vektor XSS.
    document.querySelectorAll('.js-delete-user').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
