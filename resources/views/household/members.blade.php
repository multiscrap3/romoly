@extends('layouts.app')

@section('title', __('household.members'))
@section('page-title', __('household.members'))

@section('content')
<div class="row g-4 justify-content-center">
<div class="col-12 col-lg-8">

    {{-- Daftar anggota aktif --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
            <h6 class="fw-semibold mb-0">{{ __('household.members') }} ({{ $members->count() }})</h6>
        </div>
        <div class="card-body p-0">
            @foreach($members as $member)
                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-semibold flex-shrink-0"
                         style="width:40px;height:40px;background:#3b82f6;font-size:.85rem;">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 small">
                        <div class="fw-medium text-dark">
                            {{ $member->name }}
                            @if($member->id === auth()->id())
                                <span class="text-primary ms-1" style="font-size:.72rem;">(Kamu)</span>
                            @endif
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $member->email }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @php $memberRole = $member->getRoleNames()->first() ?? 'member'; @endphp
                        <span class="badge rounded-pill {{ $member->hasRole('owner') ? 'bg-warning text-dark' : 'bg-secondary' }}">
                            {{ ucfirst($memberRole) }}
                        </span>
                        @can('manage members')
                            @if(!$member->hasRole('owner') && $member->id !== auth()->id())
                                <form method="POST" action="{{ route('household.members.remove', $member) }}"
                                      onsubmit="return confirm('{{ __('household.remove_member') }}: {{ $member->name }}?')"
                                      class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0" style="font-size:.78rem;">{{ __('messages.delete') }}</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Undangan pending --}}
    @if($invitations->isNotEmpty())
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">{{ __('household.pending') }} ({{ $invitations->count() }})</h6>
            </div>
            <div class="card-body p-0">
                @foreach($invitations as $inv)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="flex-grow-1 small">
                            <div class="fw-medium text-dark">{{ $inv->email }}</div>
                            <div class="text-muted" style="font-size:.72rem;">
                                Dikirim {{ $inv->created_at->translatedFormat('d M Y') }} &bull;
                                Kadaluarsa {{ $inv->expired_at->translatedFormat('d M Y') }}
                            </div>
                        </div>
                        <span class="badge rounded-pill bg-warning text-dark">{{ __('household.pending') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Undang anggota baru --}}
    @can('manage members')
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">{{ __('household.invite') }}</h6>
                <form method="POST" action="{{ route('household.invite') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">{{ __('household.invite_email') }}</label>
                        <input type="email" name="email" required placeholder="{{ __('household.invite_email') }}"
                               class="form-control form-control-sm">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('household.send_invite') }}</button>
                </form>
            </div>
        </div>
    @endcan

    <a href="{{ route('household.index') }}" class="btn btn-link btn-sm text-muted text-decoration-none p-0">
        &larr; {{ __('messages.back') }}
    </a>

</div>
</div>
@endsection
