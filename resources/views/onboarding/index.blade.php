<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('onboarding.title') }} - Finanku</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('dompet/icons/bootstrap-icons/font/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('dompet/css/style.css') }}">
    <style>
        body { background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%); min-height: 100vh; padding: 2rem 1rem; }
        .step-circle {
            width: 40px; height: 40px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content-center;
            font-weight: 700; font-size: .9rem;
        }
        .step-circle.done   { background: #10b981; color: #fff; }
        .step-circle.active { background: var(--primary); color: #fff; }
        .step-circle.todo   { background: #e5e7eb; color: #6b7280; }
        .step-line { flex: 1; height: 2px; margin: 0 .5rem; }
        .step-line.done { background: #10b981; }
        .step-line.todo { background: #e5e7eb; }
        .dashed-add {
            width: 100%; border: 2px dashed #d1d5db; border-radius: 12px;
            padding: .75rem; background: transparent; color: #6b7280;
            font-size: .875rem; font-weight: 500; transition: all .2s;
        }
        .dashed-add:hover { border-color: var(--primary); color: var(--primary); }
    </style>
</head>
<body>

<div style="max-width:640px;margin:0 auto;">

    {{-- Logo --}}
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2"
             style="width:56px;height:56px;background:var(--primary);">
            <span style="color:#fff;font-weight:700;font-size:1.4rem;">F</span>
        </div>
        <h4 class="fw-bold text-dark mb-0">{{ __('onboarding.title') }}</h4>
        <p class="text-muted small">{{ __('onboarding.subtitle') }}</p>
    </div>

    {{-- Stepper --}}
    <div class="d-flex align-items-center justify-content-center mb-4">
        @foreach([1 => __('onboarding.step_household'), 2 => __('onboarding.step_account'), 3 => __('onboarding.step_budget'), 4 => __('onboarding.step_recurring'), 5 => __('onboarding.step_done')] as $num => $label)
            <div class="d-flex flex-column align-items-center">
                <div class="step-circle {{ $num < $step ? 'done' : ($num === $step ? 'active' : 'todo') }}">
                    @if($num < $step)
                        <i class="bi bi-check-lg"></i>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="d-none d-sm-block small mt-1 fw-medium {{ $num === $step ? 'text-primary' : 'text-muted' }}">{{ $label }}</span>
            </div>
            @if(!$loop->last)
                <div class="step-line {{ $num < $step ? 'done' : 'todo' }}"></div>
            @endif
        @endforeach
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Card Content --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">

            {{-- ===== STEP 1: Household ===== --}}
            @if($step === 1)
                <h5 class="fw-bold mb-1">Nama Household</h5>
                <p class="text-muted small mb-4">Beri nama household kamu (misal: Keluarga Budi, Rumah Tangga 2025).</p>

                <form method="POST" action="{{ route('onboarding.household') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nama Household <span class="text-danger">*</span></label>
                        <input type="text" name="nama_household" value="{{ old('nama_household', $household->nama) }}"
                               required class="form-control" placeholder="Keluarga Budi">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Mata Uang</label>
                        <select name="mata_uang" class="form-select">
                            <option value="IDR" selected>IDR - Rupiah Indonesia</option>
                            <option value="USD">USD - Dolar Amerika</option>
                            <option value="MYR">MYR - Ringgit Malaysia</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('onboarding.skip') }}" class="text-muted small">{{ __('onboarding.skip') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('onboarding.next') }} &rarr;</button>
                    </div>
                </form>

            {{-- ===== STEP 2: Sumber Dana ===== --}}
            @elseif($step === 2)
                <h5 class="fw-bold mb-1">Sumber Dana</h5>
                <p class="text-muted small mb-4">Tambahkan rekening bank, dompet, atau sumber dana lain yang kamu miliki.</p>

                <form method="POST" action="{{ route('onboarding.rekening') }}" id="form-rekening">
                    @csrf
                    <div id="rekening-list"></div>

                    <button type="button" class="dashed-add mb-4" onclick="addRekening()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Sumber Dana
                    </button>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('onboarding.skip') }}" class="text-muted small">{{ __('onboarding.skip') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('onboarding.next') }} &rarr;</button>
                    </div>
                </form>

            {{-- ===== STEP 3: Anggaran ===== --}}
            @elseif($step === 3)
                <h5 class="fw-bold mb-1">Anggaran Bulanan</h5>
                <p class="text-muted small mb-4">Atur batas pengeluaran per kategori untuk bulan ini. Boleh dilewati.</p>

                <form method="POST" action="{{ route('onboarding.anggaran') }}" id="form-anggaran">
                    @csrf
                    <div id="anggaran-list"></div>

                    <button type="button" class="dashed-add mb-4" onclick="addAnggaran()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Anggaran Kategori
                    </button>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('onboarding.skip') }}" class="text-muted small">{{ __('onboarding.skip') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('onboarding.next') }} &rarr;</button>
                    </div>
                </form>

            {{-- ===== STEP 4: Recurring ===== --}}
            @elseif($step === 4)
                <h5 class="fw-bold mb-1">Transaksi Rutin</h5>
                <p class="text-muted small mb-4">Daftarkan pengeluaran atau pemasukan yang rutin terjadi (gaji, tagihan, dll).</p>

                <form method="POST" action="{{ route('onboarding.recurring') }}" id="form-recurring">
                    @csrf
                    <div id="recurring-list"></div>

                    <button type="button" class="dashed-add mb-4" onclick="addRecurring()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Transaksi Rutin
                    </button>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('onboarding.skip') }}" class="text-muted small">{{ __('onboarding.skip') }}</a>
                        <button type="submit" class="btn btn-primary px-4">{{ __('onboarding.next') }} &rarr;</button>
                    </div>
                </form>

            {{-- ===== STEP 5: Selesai ===== --}}
            @elseif($step === 5)
                <div class="text-center py-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                         style="width:80px;height:80px;background:#d1fae5;">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">{{ __('onboarding.done_title') }}</h4>
                    <p class="text-muted mb-4">{{ __('onboarding.done_subtitle') }}</p>

                    @if($sumberTransaksi->isEmpty())
                        <div class="alert alert-warning text-start small">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Kamu belum menambahkan sumber dana. Bisa ditambahkan nanti di menu <strong>Sumber Dana</strong>.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('onboarding.selesai') }}">
                        @csrf
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                {{ __('onboarding.finish') }}
                            </button>
                        </div>
                    </form>
                    <p class="text-muted small">Undang anggota keluarga lewat menu Household setelah masuk.</p>
                </div>
            @endif

        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    function parseCurrency(v) {
        return String(v).replace(/\./g, '').replace(/[^\d]/g, '');
    }
    function formatCurrency(v) {
        var d = parseCurrency(v);
        return d ? parseInt(d, 10).toLocaleString('id-ID') : '';
    }
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el.classList.contains('currency-input')) return;
        var start = el.selectionStart;
        var digitsBeforeCursor = parseCurrency(el.value.substring(0, start)).length;
        el.value = formatCurrency(el.value);
        var newPos = 0, count = 0;
        for (var i = 0; i < el.value.length; i++) {
            if (el.value[i] !== '.') count++;
            if (count >= digitsBeforeCursor) { newPos = i + 1; break; }
        }
        if (digitsBeforeCursor === 0) newPos = 0;
        el.setSelectionRange(newPos, newPos);
    });
    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el.classList.contains('currency-input')) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        var nav = ['Backspace','Delete','Tab','Escape','Enter','Home','End','ArrowLeft','ArrowRight','ArrowUp','ArrowDown'];
        if (!nav.includes(e.key) && !/^\d$/.test(e.key)) e.preventDefault();
    });
    document.addEventListener('submit', function (e) {
        e.target.querySelectorAll('.currency-input').forEach(function (el) {
            el.value = parseCurrency(el.value) || '0';
        });
    });
}());
</script>

<script>
// ===== STEP 2: Rekening =====
var rekeningCount = 0;
function addRekening() {
    var i = rekeningCount++;
    var html = `
    <div class="border rounded-3 p-3 mb-3 position-relative" id="rekening-${i}">
        <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                onclick="removeItem('rekening-${i}')"></button>
        <div class="mb-2">
            <label class="form-label small fw-medium">Nama Sumber Dana</label>
            <input type="text" name="rekening[${i}][nama]" required class="form-control form-control-sm"
                   placeholder="BCA Tabungan, GoPay, Kas Tunai...">
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small fw-medium">Jenis</label>
                <select name="rekening[${i}][jenis]" class="form-select form-select-sm">
                    <option value="cash">Uang Tunai</option>
                    <option value="bank" selected>Bank</option>
                    <option value="ewallet">E-Wallet</option>
                    <option value="investasi">Investasi</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-medium">Saldo Awal (Rp)</label>
                <input type="text" inputmode="numeric" name="rekening[${i}][saldo_awal]"
                       class="form-control form-control-sm currency-input" placeholder="0">
            </div>
        </div>
    </div>`;
    document.getElementById('rekening-list').insertAdjacentHTML('beforeend', html);
}

// ===== STEP 3: Anggaran =====
var anggaranCount = 0;
var kategoriOptions = `@foreach($kategori ?? [] as $kat)<option value="{{ $kat->id }}">{{ $kat->nama }}</option>@endforeach`;
function addAnggaran() {
    var i = anggaranCount++;
    var html = `
    <div class="d-flex align-items-center gap-2 border rounded-3 p-3 mb-3" id="anggaran-${i}">
        <div class="flex-grow-1">
            <select name="anggaran[${i}][kategori_id]" required class="form-select form-select-sm">
                <option value="">Pilih kategori...</option>
                ${kategoriOptions}
            </select>
        </div>
        <div style="width:130px;">
            <input type="text" inputmode="numeric" name="anggaran[${i}][limit]" required
                   class="form-control form-control-sm currency-input" placeholder="Limit Rp">
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="removeItem('anggaran-${i}')">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>`;
    document.getElementById('anggaran-list').insertAdjacentHTML('beforeend', html);
}

// ===== STEP 4: Recurring =====
var recurringCount = 0;
function addRecurring() {
    var i = recurringCount++;
    var html = `
    <div class="border rounded-3 p-3 mb-3 position-relative" id="recurring-${i}">
        <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2"
                onclick="removeItem('recurring-${i}')"></button>
        <div class="mb-2">
            <label class="form-label small fw-medium">Nama Transaksi</label>
            <input type="text" name="recurring[${i}][nama]" required class="form-control form-control-sm"
                   placeholder="Gaji Bulanan, Cicilan Motor...">
        </div>
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small fw-medium">Jenis</label>
                <select name="recurring[${i}][jenis]" class="form-select form-select-sm">
                    <option value="pengeluaran">Pengeluaran</option>
                    <option value="pemasukan">Pemasukan</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-medium">Jumlah (Rp)</label>
                <input type="text" inputmode="numeric" name="recurring[${i}][jumlah]" required
                       class="form-control form-control-sm currency-input" placeholder="500.000">
            </div>
            <div class="col-6">
                <label class="form-label small fw-medium">Frekuensi</label>
                <select name="recurring[${i}][frekuensi]" class="form-select form-select-sm">
                    <option value="bulanan">Bulanan</option>
                    <option value="mingguan">Mingguan</option>
                    <option value="harian">Harian</option>
                    <option value="tahunan">Tahunan</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-medium">Mulai Tanggal</label>
                <input type="date" name="recurring[${i}][tanggal_mulai]" required
                       class="form-control form-control-sm">
            </div>
        </div>
    </div>`;
    document.getElementById('recurring-list').insertAdjacentHTML('beforeend', html);
}

function removeItem(id) {
    var el = document.getElementById(id);
    if (el) el.remove();
}

// Auto-add satu row di step 2, 3, 4
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('rekening-list'))  addRekening();
    if (document.getElementById('anggaran-list'))  addAnggaran();
    if (document.getElementById('recurring-list')) addRecurring();
});
</script>

</body>
</html>
