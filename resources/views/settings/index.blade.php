@extends('layouts.app')

@section('title', __('settings.title'))
@section('page-title', __('settings.title'))

@section('content')
@php $activeTab = request('tab', 'profil'); @endphp
<div class="row g-4 justify-content-center">
<div class="col-12 col-lg-8">

    {{-- Nav tabs — scrollable horizontal di mobile --}}
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;margin-bottom:1.25rem;">
        <ul class="nav nav-pills flex-nowrap gap-1 pb-1" id="settingsTabs" role="tablist"
            style="min-width:max-content;" data-tour="settings-profile">
            @foreach([
                'profil'    => __('settings.tab_profile'),
                'password'  => __('settings.tab_password'),
                'household' => __('settings.tab_household'),
                'preferensi'=> __('settings.tab_preferences'),
                'privasi'   => __('settings.tab_privacy'),
            ] as $t => $label)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === $t ? 'active' : '' }}"
                            id="{{ $t }}-tab"
                            @if($t === 'preferensi') data-tour="settings-preferensi" @endif
                            data-bs-toggle="pill"
                            data-bs-target="#tab-{{ $t }}"
                            type="button" role="tab"
                            style="white-space:nowrap;">
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="tab-content">

        {{-- Profil --}}
        <div class="tab-pane fade {{ $activeTab === 'profil' ? 'show active' : '' }}" id="tab-profil" role="tabpanel">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body p-4 p-md-5">
                    <h6 class="fw-semibold mb-4">{{ __('settings.edit_profile') }}</h6>
                    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('settings.name') }}</label>
                            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                                   class="form-control">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium">{{ __('settings.email') }}</label>
                            <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                                   class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('settings.save') }}</button>
                    </form>

                    {{-- Putar ulang panduan (Guided Tour) --}}
                    <div class="mt-4 pt-4 border-top" data-tour="settings-replay-tour">
                        <h6 class="fw-semibold mb-1">{{ __('tour.common.replay_button') }}</h6>
                        <p class="text-muted small mb-2">{{ __('tour.common.replay_title') }}</p>
                        <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="window.RomolyTour && window.RomolyTour.reset()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('tour.common.replay_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Password --}}
        <div class="tab-pane fade {{ $activeTab === 'password' ? 'show active' : '' }}" id="tab-password" role="tabpanel">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body p-4 p-md-5">
                    <h6 class="fw-semibold mb-4">{{ __('settings.change_password') }}</h6>
                    <form method="POST" action="{{ route('settings.password.update') }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('settings.current_password') }}</label>
                            <input type="password" name="current_password" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('settings.new_password') }}</label>
                            <input type="password" name="password" required minlength="8" class="form-control">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium">{{ __('settings.confirm_password') }}</label>
                            <input type="password" name="password_confirmation" required class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('settings.change_password_btn') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Household --}}
        <div class="tab-pane fade {{ $activeTab === 'household' ? 'show active' : '' }}" id="tab-household" role="tabpanel">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body p-4 p-md-5">
                    <h6 class="fw-semibold mb-4">{{ __('settings.household_settings') }}</h6>
                    <form method="POST" action="{{ route('settings.household.update') }}">
                        @csrf @method('PUT')
                        <div class="mb-4">
                            <label class="form-label fw-medium">{{ __('settings.household_name') }}</label>
                            <input type="text" name="nama"
                                   value="{{ old('nama', auth()->user()->household?->nama) }}" required
                                   class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('settings.save') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Preferensi --}}
        <div class="tab-pane fade {{ $activeTab === 'preferensi' ? 'show active' : '' }}" id="tab-preferensi" role="tabpanel">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body p-4 p-md-5">
                    <h6 class="fw-semibold mb-4">{{ __('settings.preferences') }}</h6>
                    <form method="POST" action="{{ route('settings.preferences.update') }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('settings.theme') }}</label>
                            <select name="theme" class="form-select">
                                <option value="light" {{ ($settings['theme'] ?? 'light') === 'light' ? 'selected' : '' }}>{{ __('settings.theme_light') }}</option>
                                <option value="dark" {{ ($settings['theme'] ?? '') === 'dark' ? 'selected' : '' }}>{{ __('settings.theme_dark') }}</option>
                                <option value="system" {{ ($settings['theme'] ?? '') === 'system' ? 'selected' : '' }}>{{ __('settings.theme_system') }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium">{{ __('settings.language') }}</label>
                            <select name="language" class="form-select">
                                <option value="id" {{ ($settings['language'] ?? 'id') === 'id' ? 'selected' : '' }}>🇮🇩 Bahasa Indonesia</option>
                                <option value="en" {{ ($settings['language'] ?? '') === 'en' ? 'selected' : '' }}>🇺🇸 English (US)</option>
                            </select>
                        </div>

                        {{-- PDP H2: Kontrol Fitur AI --}}
                        <hr class="my-4">
                        <h6 class="fw-semibold mb-1">
                            {{ __('settings.ai_controls') }}
                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:.65rem;">{{ __('settings.ai_pdp_badge') }}</span>
                        </h6>
                        <p class="small text-muted mb-3">{{ __('settings.ai_controls_desc') }}</p>
                        <div class="d-flex justify-content-between align-items-start p-3 border rounded mb-3">
                            <div class="me-3">
                                <div class="fw-medium small">{{ __('settings.ai_insights') }}</div>
                                <div class="small text-muted">{{ __('settings.ai_insights_desc') }}</div>
                            </div>
                            <div class="form-check form-switch flex-shrink-0 mt-1">
                                <input class="form-check-input" type="checkbox" name="ai_opt_out" value="1"
                                       id="aiOptOut"
                                       {{ ($settings['ai_opt_out'] ?? '0') === '1' ? '' : 'checked' }}
                                       onchange="this.value = this.checked ? '0' : '1'">
                                <label class="form-check-label small" for="aiOptOut">{{ __('settings.ai_active') }}</label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-start p-3 border rounded mb-4">
                            <div class="me-3">
                                <div class="fw-medium small">{{ __('settings.ai_ocr') }}</div>
                                <div class="small text-muted">{{ __('settings.ai_ocr_desc') }}</div>
                            </div>
                            <div class="form-check form-switch flex-shrink-0 mt-1">
                                <input class="form-check-input" type="checkbox" name="ai_ocr_opt_out" value="1"
                                       id="aiOcrOptOut"
                                       {{ ($settings['ai_ocr_opt_out'] ?? '0') === '1' ? '' : 'checked' }}
                                       onchange="this.value = this.checked ? '0' : '1'">
                                <label class="form-check-label small" for="aiOcrOptOut">{{ __('settings.ai_active') }}</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('settings.save_preferences') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Privasi & Data --}}
        <div class="tab-pane fade {{ $activeTab === 'privasi' ? 'show active' : '' }}" id="tab-privasi" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:.75rem;">
                <div class="card-body p-4 p-md-5">
                    <h6 class="fw-semibold mb-1">{{ __('settings.privacy_title') }}</h6>
                    <p class="small text-muted mb-4">{{ __('settings.privacy_desc') }}</p>

                    {{-- Status consent --}}
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-shield-check text-success"></i>
                            <span class="fw-semibold small">{{ __('settings.consent_status') }}</span>
                        </div>
                        @if(auth()->user()->consent_given_at)
                            <div class="small text-muted">
                                {{ __('settings.consent_given') }}
                                <a href="{{ route('privacy.policy') }}" target="_blank">{{ __('messages.privacy_policy') }}</a>
                                {{ __('settings.consent_version', ['version' => auth()->user()->privacy_policy_version]) }}
                                {{ __('settings.consent_at', ['date' => auth()->user()->consent_given_at->format('d M Y, H:i')]) }}
                            </div>
                        @else
                            <div class="small text-warning">{{ __('settings.consent_missing') }}</div>
                        @endif
                    </div>

                    {{-- Aksi hak subjek data --}}
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <div>
                                <div class="fw-semibold small">{{ __('settings.download_data') }}</div>
                                <div class="small text-muted">{{ __('settings.download_data_desc') }}</div>
                            </div>
                            <a href="{{ route('privacy.download') }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
                                <i class="bi bi-download me-1"></i>{{ __('settings.download_btn') }}
                            </a>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <div>
                                <div class="fw-semibold small">{{ __('settings.consent_history') }}</div>
                                <div class="small text-muted">{{ __('settings.consent_history_desc') }}</div>
                            </div>
                            <a href="{{ route('privacy.export') }}" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                <i class="bi bi-eye me-1"></i>{{ __('settings.view_btn') }}
                            </a>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-3">
                        <a href="{{ route('privacy.policy') }}" target="_blank" class="small text-decoration-none">
                            <i class="bi bi-file-text me-1"></i>{{ __('messages.privacy_policy') }}
                        </a>
                        <a href="{{ route('privacy.terms') }}" target="_blank" class="small text-decoration-none">
                            <i class="bi bi-file-text me-1"></i>{{ __('messages.terms') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="card border-danger shadow-sm" style="border-radius:.75rem; border-width:1.5px!important;">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                        <h6 class="fw-semibold text-danger mb-0">{{ __('settings.reset_data_section') }}</h6>
                    </div>

                    <hr class="border-danger opacity-25 my-3">

                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold small">{{ __('settings.reset_data_title') }}</div>
                            <div class="small text-muted mt-1">{{ __('settings.reset_data_desc') }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-danger-subtle text-danger mb-2 d-block text-center" style="font-size:.65rem;">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ __('settings.reset_data_warning') }}
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#modalResetData">
                                <i class="bi bi-trash3 me-1"></i>{{ __('settings.reset_data_btn') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>
</div>

@endsection

@push('modals')
{{-- Modal ditempatkan sebagai child <body> langsung agar tidak ter-clip
     oleh stacking context / overflow dari #main-wrapper template Dompet --}}
<div class="modal fade" id="modalResetData" tabindex="-1" aria-labelledby="modalResetDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center"
                         style="width:2.25rem;height:2.25rem;flex-shrink:0;">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    </div>
                    <h6 class="modal-title fw-semibold mb-0" id="modalResetDataLabel">
                        {{ __('settings.reset_data_modal_title') }}
                    </h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="small mb-3">{!! __('settings.reset_data_modal_desc') !!}</p>
                <ul class="small mb-3 ps-3">
                    <li>{{ __('settings.reset_data_modal_item1') }}</li>
                    <li>{{ __('settings.reset_data_modal_item2') }}</li>
                    <li>{{ __('settings.reset_data_modal_item3') }}</li>
                </ul>
                <div class="alert alert-danger py-2 small mb-4">
                    <i class="bi bi-shield-x me-1"></i>
                    {!! __('settings.reset_data_modal_warning') !!}
                </div>

                <form id="formResetData" method="POST" action="{{ route('settings.reset-data') }}">
                    @csrf
                    @method('DELETE')
                    <div class="mb-3">
                        <label class="form-label small fw-medium" for="resetConfirmInput">
                            {!! __('settings.reset_data_type_label') !!}
                        </label>
                        <input type="text"
                               id="resetConfirmInput"
                               name="confirm_word"
                               class="form-control"
                               placeholder="{{ __('settings.reset_data_type_placeholder') }}"
                               autocomplete="off"
                               spellcheck="false">
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-dismiss="modal">
                            {{ __('settings.reset_data_cancel_btn') }}
                        </button>
                        <button type="submit"
                                id="btnConfirmReset"
                                class="btn btn-sm btn-danger"
                                disabled>
                            <i class="bi bi-trash3 me-1"></i>{{ __('settings.reset_data_confirm_btn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const confirmWord = @json(__('settings.reset_data_confirm_word'));
    const input       = document.getElementById('resetConfirmInput');
    const btn         = document.getElementById('btnConfirmReset');
    const modal       = document.getElementById('modalResetData');

    if (!input || !btn || !modal) return;

    input.addEventListener('input', function () {
        btn.disabled = this.value.trim() !== confirmWord;
    });

    modal.addEventListener('hidden.bs.modal', function () {
        input.value  = '';
        btn.disabled = true;
    });
})();
</script>
@endpush
