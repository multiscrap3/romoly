@extends('layouts.app')

@section('title', __('household.title'))
@section('page-title', __('household.title'))

@section('content')
<div class="row g-4 justify-content-center">
<div class="col-12 col-lg-9">

    @php $household = auth()->user()->household; @endphp

    {{-- Info household --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div>
                    <h5 class="fw-bold mb-1">{{ $household?->nama ?? 'Household Kamu' }}</h5>
                    <p class="text-muted small mb-0">
                        Plan: <span class="fw-medium text-dark">{{ $household?->plan?->nama ?? 'Free' }}</span>
                    </p>
                </div>
                <a href="{{ route('household.members') }}" class="small text-primary text-decoration-none">{{ __('household.members') }}</a>
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <div class="p-3 rounded-3" style="background:#f8f9fa;">
                        <div class="text-muted small mb-1">{{ __('household.household_name') }}</div>
                        <div class="fw-semibold {{ $household?->isSubscriptionActive() ? 'text-success' : 'text-danger' }}">
                            {{ $household?->isSubscriptionActive() ? __('recurring.active') : __('recurring.inactive') }}
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded-3" style="background:#f8f9fa;">
                        <div class="text-muted small mb-1">{{ __('household.members') }}</div>
                        <div class="fw-semibold text-dark">{{ $household?->users->count() ?? 0 }} orang</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Anggota --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
            <h6 class="fw-semibold mb-0">{{ __('household.members') }}</h6>
        </div>
        <div class="card-body p-0">
            @foreach($household?->users ?? [] as $member)
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
                    @php $memberRole = $member->getRoleNames()->first() ?? 'member'; @endphp
                    <span class="badge rounded-pill {{ $member->hasRole('owner') ? 'bg-warning text-dark' : 'bg-secondary' }}">
                        {{ ucfirst($memberRole) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Undang anggota --}}
    @can('manage members')
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-3">{{ __('household.invite') }}</h6>
                <form method="POST" action="{{ route('household.invite') }}" class="d-flex gap-2" data-tour="household-invite">
                    @csrf
                    <input type="email" name="email" required placeholder="{{ __('household.invite_email') }}"
                           class="form-control form-control-sm flex-grow-1">
                    <select name="role" class="form-select form-select-sm" style="width:auto;">
                        <option value="member">{{ __('household.role_member') }}</option>
                        <option value="admin">{{ __('household.role_admin') }}</option>
                        <option value="analyst">{{ __('household.role_analyst') }}</option>
                        <option value="viewer">{{ __('household.role_viewer') }}</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('household.invite') }}</button>
                </form>
            </div>
        </div>
    @endcan

    {{-- Bergabung via kode --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-3">{{ __('household.invite') }}</h6>
            <form method="POST" action="{{ route('household.join') }}" class="d-flex gap-2" data-tour="household-join">
                @csrf
                <input type="text" name="token" required placeholder="{{ __('household.invite_email') }}"
                       class="form-control form-control-sm flex-grow-1">
                <button type="submit" class="btn btn-success btn-sm">{{ __('household.send_invite') }}</button>
            </form>
        </div>
    </div>

</div>
</div>
@endsection
