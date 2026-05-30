@extends('layouts.app')

@section('title', __('household.members'))
@section('page-title', __('household.members'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7" style="align-self:flex-start;">

    {{-- Link undangan --}}
    @if(session('invite_link'))
        <div class="alert alert-success border-0 shadow-sm mb-3" style="border-radius:.75rem;">
            <div class="small fw-semibold mb-2">{{ session('success') ?? session('info') }}</div>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" id="inviteLink" value="{{ session('invite_link') }}" readonly>
                <button class="btn btn-outline-secondary" type="button"
                        onclick="var inp=document.getElementById('inviteLink');inp.select();inp.setSelectionRange(0,99999);try{document.execCommand('copy')}catch(e){};this.textContent='Tersalin!';setTimeout(()=>this.textContent='Salin',2000)">
                    Salin
                </button>
            </div>
            <div class="text-muted mt-1" style="font-size:.72rem;">Link berlaku 7 hari. Bagikan ke orang yang ingin Anda undang.</div>
        </div>
    @endif

    {{-- Daftar anggota aktif --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:.75rem;">
        <div class="card-header border-bottom py-3 px-4 d-flex align-items-center justify-content-between" style="border-radius:.75rem .75rem 0 0;">
            <h6 class="fw-semibold mb-0">{{ __('household.members') }} ({{ $members->count() }})</h6>
            <button type="button" class="btn btn-link btn-sm text-muted p-0 lh-1"
                    data-bs-toggle="modal" data-bs-target="#roleGuideModal" title="Panduan Role">
                <i class="bi bi-info-circle"></i>
            </button>
        </div>
        <div class="card-body p-0">
            @foreach($members as $member)
                <div class="d-flex align-items-center gap-3 px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-semibold flex-shrink-0"
                         style="width:38px;height:38px;background:#3b82f6;font-size:.85rem;">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 small">
                        <div class="fw-medium">
                            {{ $member->name }}
                            @if($member->id === auth()->id())
                                <span class="text-primary ms-1" style="font-size:.72rem;">(Kamu)</span>
                            @endif
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $member->email }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
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
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0" style="font-size:.78rem;">Hapus</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Undangan pending --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:.75rem;">
        <div class="card-header border-bottom py-2 px-4" style="border-radius:.75rem .75rem 0 0;">
            <h6 class="fw-semibold mb-0 small">{{ __('household.pending') }} ({{ $invitations->count() }})</h6>
        </div>
        <div class="card-body p-0">
            @forelse($invitations as $inv)
                <div class="px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="flex-grow-1 small">
                            <div class="fw-medium">{{ $inv->email }}</div>
                            <div class="text-muted" style="font-size:.72rem;">
                                Kadaluarsa {{ $inv->expires_at->translatedFormat('d M Y') }}
                            </div>
                        </div>
                        <span class="badge rounded-pill bg-warning text-dark small flex-shrink-0">Pending</span>
                        @can('manage members')
                            <form method="POST"
                                  action="{{ route('household.invitations.cancel', $inv) }}"
                                  onsubmit="return confirm('Batalkan undangan ke {{ $inv->email }}?')"
                                  class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" style="font-size:.75rem;">Hapus</button>
                            </form>
                        @endcan
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm invite-link-input"
                               value="{{ route('register', ['token' => $inv->token]) }}" readonly
                               style="font-size:.72rem;">
                        <button class="btn btn-outline-secondary btn-sm" type="button"
                                onclick="var inp=this.previousElementSibling;inp.select();inp.setSelectionRange(0,99999);try{document.execCommand('copy')}catch(e){};this.textContent='Tersalin!';setTimeout(()=>this.textContent='Salin',2000)">
                            Salin
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-4 py-3 text-muted small">{{ __('household.no_pending') }}</div>
            @endforelse
        </div>
    </div>

    {{-- Undang anggota baru --}}
    @can('manage members')
        <div class="card border-0 shadow-sm mb-3" style="border-radius:.75rem;">
            <div class="card-header border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">{{ __('household.invite') }}</h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('household.invite') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-12 col-sm-5">
                            <input type="email" name="email" required
                                   placeholder="{{ __('household.invite_email') }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-sm-4">
                            <select name="role" class="form-select form-select-sm">
                                <option value="member">{{ __('household.role_member') }}</option>
                                <option value="admin">{{ __('household.role_admin') }}</option>
                                <option value="analyst">{{ __('household.role_analyst') }}</option>
                                <option value="viewer">{{ __('household.role_viewer') }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-3">
                            <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('household.send_invite') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <a href="{{ route('household.index') }}" class="btn btn-link btn-sm text-muted text-decoration-none p-0">
        &larr; {{ __('messages.back') }}
    </a>

</div>
</div>

{{-- Modal Panduan Role --}}
<div class="modal fade" id="roleGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header pb-2" style="border-bottom:1px solid #f0f0f0;">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                          style="width:28px;height:28px;background:#5bcfc5;">
                        <i class="bi bi-people-fill text-white" style="font-size:.7rem;"></i>
                    </span>
                    <h6 class="modal-title fw-semibold mb-0">Panduan Role Anggota</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">

                {{-- Role kamu saat ini --}}
                @php $myRole = auth()->user()->getRoleNames()->first() ?? 'member'; @endphp
                <div class="d-flex align-items-center gap-2 p-3 rounded-3 mb-4" style="background:#f0fffe;border:1px solid #b2ede9;">
                    <i class="bi bi-person-badge-fill" style="color:#5bcfc5;font-size:1rem;"></i>
                    <span class="small text-muted">Role kamu saat ini:</span>
                    @if($myRole === 'owner')
                        <span class="badge rounded-pill bg-warning text-dark">Owner</span>
                    @elseif($myRole === 'admin')
                        <span class="badge rounded-pill text-white" style="background:#3b82f6;">Admin</span>
                    @elseif($myRole === 'member')
                        <span class="badge rounded-pill text-white" style="background:#10b981;">Member</span>
                    @elseif($myRole === 'analyst')
                        <span class="badge rounded-pill text-white" style="background:#8b5cf6;">Analyst</span>
                    @elseif($myRole === 'viewer')
                        <span class="badge rounded-pill bg-secondary">Viewer</span>
                    @else
                        <span class="badge rounded-pill bg-secondary">{{ ucfirst($myRole) }}</span>
                    @endif
                </div>

                {{-- Role Cards --}}
                <div class="small fw-semibold text-uppercase text-muted mb-2" style="font-size:.65rem;letter-spacing:.05em;">Deskripsi Role</div>
                <div class="row g-2 mb-4">
                    {{-- Owner --}}
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded-3 h-100" style="background:#fffbeb;border:1px solid #fde68a;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:30px;height:30px;background:#f59e0b;">
                                    <i class="bi bi-crown-fill text-white" style="font-size:.75rem;"></i>
                                </span>
                                <span class="badge rounded-pill bg-warning text-dark">Owner</span>
                                <span class="text-muted" style="font-size:.7rem;">· Pemilik</span>
                            </div>
                            <div class="fw-semibold small mb-1">Akses penuh, tidak bisa dihapus</div>
                            <ul class="text-muted mb-0 ps-3" style="font-size:.75rem;line-height:1.7;">
                                <li>Buat, edit & hapus semua data keuangan</li>
                                <li>Undang, hapus & ubah role anggota</li>
                                <li>Ubah nama & pengaturan household</li>
                                <li>Lihat semua laporan keuangan</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Admin --}}
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded-3 h-100" style="background:#eff6ff;border:1px solid #bfdbfe;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:30px;height:30px;background:#3b82f6;">
                                    <i class="bi bi-shield-fill text-white" style="font-size:.75rem;"></i>
                                </span>
                                <span class="badge rounded-pill text-white" style="background:#3b82f6;">Admin</span>
                                <span class="text-muted" style="font-size:.7rem;">· Administrator</span>
                            </div>
                            <div class="fw-semibold small mb-1">Kelola anggota, tidak bisa ubah struktur</div>
                            <ul class="text-muted mb-0 ps-3" style="font-size:.75rem;line-height:1.7;">
                                <li>Buat, edit & hapus semua data keuangan</li>
                                <li>Undang & hapus anggota</li>
                                <li>Lihat semua laporan keuangan</li>
                                <li class="text-danger-emphasis">Tidak bisa ubah role & pengaturan</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Member --}}
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded-3 h-100" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:30px;height:30px;background:#10b981;">
                                    <i class="bi bi-person-check-fill text-white" style="font-size:.75rem;"></i>
                                </span>
                                <span class="badge rounded-pill text-white" style="background:#10b981;">Member</span>
                                <span class="text-muted" style="font-size:.7rem;">· Kontributor</span>
                            </div>
                            <div class="fw-semibold small mb-1">Kontributor aktif data keuangan</div>
                            <ul class="text-muted mb-0 ps-3" style="font-size:.75rem;line-height:1.7;">
                                <li>Buat, edit & hapus semua data keuangan</li>
                                <li>Lihat laporan keuangan</li>
                                <li class="text-danger-emphasis">Tidak bisa kelola anggota</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Analyst --}}
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded-3 h-100" style="background:#f5f3ff;border:1px solid #ddd6fe;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:30px;height:30px;background:#8b5cf6;">
                                    <i class="bi bi-graph-up-arrow text-white" style="font-size:.75rem;"></i>
                                </span>
                                <span class="badge rounded-pill text-white" style="background:#8b5cf6;">Analyst</span>
                                <span class="text-muted" style="font-size:.7rem;">· Pengamat</span>
                            </div>
                            <div class="fw-semibold small mb-1">Lihat data & laporan, tidak bisa edit</div>
                            <ul class="text-muted mb-0 ps-3" style="font-size:.75rem;line-height:1.7;">
                                <li>Lihat semua data keuangan</li>
                                <li>Akses penuh ke laporan</li>
                                <li class="text-danger-emphasis">Tidak bisa buat atau edit data</li>
                                <li class="text-danger-emphasis">Tidak bisa kelola anggota</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Viewer --}}
                    <div class="col-12 col-sm-6">
                        <div class="p-3 rounded-3 h-100" style="background:#f9fafb;border:1px solid #e5e7eb;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:30px;height:30px;background:#6b7280;">
                                    <i class="bi bi-eye-fill text-white" style="font-size:.75rem;"></i>
                                </span>
                                <span class="badge rounded-pill bg-secondary">Viewer</span>
                                <span class="text-muted" style="font-size:.7rem;">· Pengamat terbatas</span>
                            </div>
                            <div class="fw-semibold small mb-1">Hanya lihat data, tanpa laporan</div>
                            <ul class="text-muted mb-0 ps-3" style="font-size:.75rem;line-height:1.7;">
                                <li>Lihat transaksi, anggaran, tabungan & hutang</li>
                                <li class="text-danger-emphasis">Tidak bisa lihat laporan</li>
                                <li class="text-danger-emphasis">Tidak bisa buat atau edit data</li>
                                <li class="text-danger-emphasis">Tidak bisa kelola anggota</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Tabel Perbandingan --}}
                <div class="small fw-semibold text-uppercase text-muted mb-2" style="font-size:.65rem;letter-spacing:.05em;">Perbandingan Lengkap</div>
                <div class="table-responsive rounded-3" style="border:1px solid #e5e7eb;">
                    <table class="table table-sm mb-0" style="font-size:.75rem;">
                        <thead style="background:#f9fafb;">
                            <tr class="text-center">
                                <th class="text-start fw-medium py-2 px-3 border-0" style="min-width:150px;">Fitur</th>
                                <th class="border-0 py-2"><span class="badge rounded-pill bg-warning text-dark">Owner</span></th>
                                <th class="border-0 py-2"><span class="badge rounded-pill text-white" style="background:#3b82f6;">Admin</span></th>
                                <th class="border-0 py-2"><span class="badge rounded-pill text-white" style="background:#10b981;">Member</span></th>
                                <th class="border-0 py-2"><span class="badge rounded-pill text-white" style="background:#8b5cf6;">Analyst</span></th>
                                <th class="border-0 py-2"><span class="badge rounded-pill bg-secondary">Viewer</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-center">
                                <td class="text-start text-muted px-3 py-2" style="border-color:#f0f0f0;">Lihat data keuangan</td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                            </tr>
                            <tr class="text-center">
                                <td class="text-start text-muted px-3 py-2" style="border-color:#f0f0f0;">Buat & edit data keuangan</td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                            </tr>
                            <tr class="text-center">
                                <td class="text-start text-muted px-3 py-2" style="border-color:#f0f0f0;">Lihat laporan</td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                            </tr>
                            <tr class="text-center">
                                <td class="text-start text-muted px-3 py-2" style="border-color:#f0f0f0;">Undang & hapus anggota</td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                            </tr>
                            <tr class="text-center">
                                <td class="text-start text-muted px-3 py-2" style="border-color:#f0f0f0;">Ubah role anggota</td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td style="border-color:#f0f0f0;"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                            </tr>
                            <tr class="text-center" style="border-bottom:none;">
                                <td class="text-start text-muted px-3 py-2 border-0">Pengaturan household</td>
                                <td class="border-0"><i class="bi bi-check-circle-fill text-success"></i></td>
                                <td class="border-0"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td class="border-0"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td class="border-0"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                                <td class="border-0"><i class="bi bi-x-circle-fill text-danger opacity-50"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex align-items-center gap-3 mt-2" style="font-size:.72rem;color:#9ca3af;">
                    <span><i class="bi bi-check-circle-fill text-success me-1"></i>Diizinkan</span>
                    <span><i class="bi bi-x-circle-fill text-danger opacity-50 me-1"></i>Tidak bisa</span>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
