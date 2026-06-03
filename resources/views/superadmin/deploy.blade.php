@extends('layouts.superadmin')

@section('title', 'Deploy & Migrasi')
@section('page-title', 'Deploy & Migrasi')

@section('content')
<div class="row g-4">

    {{-- Info --}}
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-start gap-2 mb-0" style="border-radius:.75rem;">
            <i class="bi bi-info-circle fs-5"></i>
            <div class="small">
                Halaman ini untuk menjalankan <strong>migrasi database</strong> dan membersihkan cache
                tanpa akses SSH/terminal (shared hosting). Jalankan setelah meng-upload kode terbaru.
                Migrasi bersifat <strong>idempoten</strong> — hanya memproses yang belum dijalankan.
            </div>
        </div>
    </div>

    {{-- Status migrasi --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between"
                 style="border-radius:.75rem .75rem 0 0;">
                <span class="fw-semibold"><i class="bi bi-database-gear me-2"></i>Status Migrasi</span>
                <span class="badge rounded-pill {{ count($pending) ? 'bg-warning text-dark' : 'bg-success' }}">
                    {{ $ranCount }}/{{ $totalCount }} dijalankan
                </span>
            </div>
            <div class="card-body p-4">
                @if(count($pending))
                    <p class="text-muted small mb-2">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                        Ada <strong>{{ count($pending) }}</strong> migrasi yang belum dijalankan:
                    </p>
                    <ul class="list-group list-group-flush small mb-3">
                        @foreach($pending as $name)
                            <li class="list-group-item px-0 py-1 d-flex align-items-center gap-2">
                                <i class="bi bi-dot text-warning"></i>
                                <code class="text-warning">{{ $name }}</code>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-success small mb-3">
                        <i class="bi bi-check-circle me-1"></i>
                        Semua migrasi sudah dijalankan. Database mutakhir.
                    </p>
                @endif

                <form method="POST" action="{{ route('superadmin.deploy.migrate') }}"
                      onsubmit="return confirm('Jalankan migrasi database sekarang? Pastikan kode terbaru sudah ter-upload.');">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-play-fill me-1"></i> Jalankan Migrasi
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Clear cache --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <span class="fw-semibold"><i class="bi bi-arrow-clockwise me-2"></i>Bersihkan Cache</span>
            </div>
            <div class="card-body p-4 d-flex flex-column">
                <p class="text-muted small">
                    Bersihkan cache <code>config</code>, <code>route</code>, <code>view</code>, dan <code>cache</code>
                    setelah deploy (pengganti <code>php artisan optimize:clear</code>).
                </p>
                <form method="POST" action="{{ route('superadmin.deploy.clear-cache') }}" class="mt-auto"
                      onsubmit="return confirm('Bersihkan seluruh cache aplikasi sekarang?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-trash3 me-1"></i> Bersihkan Cache
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Seed Data Awal --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <span class="fw-semibold"><i class="bi bi-box-seam me-2"></i>Seed Data Awal</span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Mengisi data referensi yang dibutuhkan aplikasi. Seeder di bawah ini
                    <strong>idempoten</strong> — aman dijalankan berulang, tidak menduplikasi data yang sudah ada.
                </p>
                <div class="row g-3">
                    @foreach($seeders as $key => $s)
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100 d-flex flex-column" style="border-radius:.75rem;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-semibold small">{{ $s['label'] }}</span>
                                <span class="badge rounded-pill {{ $s['count'] > 0 ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $s['count'] }} data
                                </span>
                            </div>
                            <p class="text-muted mb-3" style="font-size:.8rem;">{{ $s['desc'] }}</p>
                            <form method="POST" action="{{ route('superadmin.deploy.seed') }}" class="mt-auto"
                                  onsubmit="return confirm('Jalankan seeder {{ $s['label'] }} sekarang?');">
                                @csrf
                                <input type="hidden" name="seeder" value="{{ $key }}">
                                <button type="submit" class="btn btn-sm {{ $s['count'] > 0 ? 'btn-outline-primary' : 'btn-primary' }}">
                                    <i class="bi bi-play-fill me-1"></i>
                                    {{ $s['count'] > 0 ? 'Jalankan Ulang' : 'Jalankan Seeder' }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Diagnostik Email & Server --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <span class="fw-semibold"><i class="bi bi-envelope-gear me-2"></i>Diagnostik Email & Server</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">

                    {{-- Konfigurasi mail aktif --}}
                    <div class="col-lg-5">
                        <h6 class="fw-semibold small mb-2"><i class="bi bi-sliders me-1"></i>Konfigurasi Email Aktif</h6>
                        <table class="table table-sm small mb-0">
                            <tbody>
                                <tr><td class="text-muted">Mailer</td><td><code>{{ $mail['mailer'] }}</code></td></tr>
                                <tr><td class="text-muted">Host</td><td><code>{{ $mail['host'] }}</code></td></tr>
                                <tr><td class="text-muted">Port</td><td><code>{{ $mail['port'] }}</code></td></tr>
                                <tr><td class="text-muted">Username</td><td><code>{{ $mail['user'] }}</code></td></tr>
                                <tr><td class="text-muted">From</td><td><code>{{ $mail['from'] }}</code></td></tr>
                                <tr><td class="text-muted">Queue</td><td><code>{{ $mail['queue'] }}</code></td></tr>
                            </tbody>
                        </table>
                        @if($mail['mailer'] === 'log')
                            <div class="alert alert-warning small mt-2 mb-0 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Mailer masih <code>log</code> — email tidak terkirim ke inbox. Set SMTP di <code>.env</code> lalu Bersihkan Cache.
                            </div>
                        @endif
                    </div>

                    {{-- IP server untuk otorisasi provider (mis. Brevo) --}}
                    <div class="col-lg-7">
                        <h6 class="fw-semibold small mb-2"><i class="bi bi-hdd-network me-1"></i>IP Server (untuk Authorized IPs Brevo)</h6>
                        <p class="text-muted mb-2" style="font-size:.8rem;">
                            Jika muncul error <code>525 Unauthorized IP address</code>, daftarkan IP keluar server ini
                            di <strong>Brevo → Security → Authorized IPs</strong>.
                        </p>

                        @if(!empty($serverIp))
                            <div class="alert alert-success d-flex align-items-center justify-content-between gap-2 py-2 mb-2">
                                <span class="small mb-0">IP keluar server: <strong id="serverIpVal">{{ $serverIp }}</strong></span>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                        onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('serverIpVal').textContent)">
                                    <i class="bi bi-clipboard me-1"></i>Salin
                                </button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('superadmin.deploy.check-ip') }}" class="mb-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-search me-1"></i>{{ !empty($serverIp) ? 'Cek Ulang IP Server' : 'Cek IP Server' }}
                            </button>
                        </form>

                        <hr class="my-3">

                        {{-- Kirim email uji --}}
                        <h6 class="fw-semibold small mb-2"><i class="bi bi-send me-1"></i>Kirim Email Uji</h6>
                        <p class="text-muted mb-2" style="font-size:.8rem;">
                            Pengganti <code>php artisan mail:test</code>. Dikirim langsung — error SMTP (mis. 525) akan tampil di bawah.
                        </p>
                        <form method="POST" action="{{ route('superadmin.deploy.test-email') }}" class="row g-2">
                            @csrf
                            <div class="col-sm-8">
                                <input type="email" name="email" required
                                       value="{{ old('email', auth()->user()->email) }}"
                                       class="form-control form-control-sm" placeholder="email@tujuan.com">
                            </div>
                            <div class="col-sm-4">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-envelope-arrow-up me-1"></i>Kirim Uji
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Output hasil migrasi/seed terakhir --}}
    @if(!empty($output))
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                    <span class="fw-semibold"><i class="bi bi-terminal me-2"></i>Output Terakhir</span>
                </div>
                <div class="card-body p-0">
                    <pre class="mb-0 p-4 small" style="background:#1e1b4b;color:#c4b5fd;border-radius:0 0 .75rem .75rem;white-space:pre-wrap;word-break:break-word;">{{ trim($output) }}</pre>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
