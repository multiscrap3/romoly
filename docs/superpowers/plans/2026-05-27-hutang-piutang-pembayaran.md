# Hutang-Piutang Pembayaran — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Perbaiki bug pembayaran yang gagal disimpan dan tambahkan fitur edit/delete per cicilan serta mode pembayaran cicilan (recurring) vs sekali bayar.

**Architecture:** Tambah kolom `sumber_transaksi_id` yang hilang di tabel `hutang_piutang_pembayaran` via migration baru, perbaiki model & service, tambahkan `PembayaranController` untuk CRUD record pembayaran individu, dan extend form create/show untuk mendukung mode cicilan.

**Tech Stack:** Laravel 11, Blade, Bootstrap 5, MySQL, Carbon

---

## Root Cause Summary

| # | Bug | Lokasi |
|---|-----|--------|
| 1 | Kolom `sumber_transaksi_id` tidak ada di tabel | Migration `..._create_hutang_piutang_pembayaran_table.php` |
| 2 | `user_id` NOT NULL tapi tidak di-set di service | `HutangPiutangService::bayar()` |
| 3 | `sumber_transaksi_id` tidak ada di `$fillable` model | `HutangPiutangPembayaran` |
| 4 | Relasi `sumberTransaksi()` tidak didefinisikan di model | `HutangPiutangPembayaran` |
| 5 | Tidak ada edit/delete per record pembayaran | - |
| 6 | Tidak ada mode cicilan/recurring | - |

---

## File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `database/migrations/TIMESTAMP_fix_hutang_piutang_pembayaran_table.php` | Tambah `sumber_transaksi_id`, buat `user_id` nullable |
| Create | `database/migrations/TIMESTAMP_add_cicilan_fields_to_hutang_piutang.php` | Tambah `tipe_pembayaran`, `jumlah_cicilan`, `frekuensi_cicilan` |
| Modify | `app/Models/HutangPiutangPembayaran.php` | Tambah fillable, relasi `sumberTransaksi` & `user` |
| Modify | `app/Services/HutangPiutangService.php` | Fix `bayar()`, tambah `editPembayaran()`, `hapusPembayaran()` |
| Create | `app/Http/Controllers/PembayaranController.php` | Edit & delete record pembayaran individu |
| Modify | `app/Http/Controllers/HutangPiutangController.php` | Pass flash errors ke show, validasi cicilan |
| Modify | `routes/web.php` | Tambah routes `pembayaran.update` & `pembayaran.destroy` |
| Modify | `resources/views/hutang-piutang/create.blade.php` | Tambah pilihan mode cicilan |
| Modify | `resources/views/hutang-piutang/show.blade.php` | Edit/delete di riwayat, tampilkan jadwal cicilan |
| Create | `resources/views/hutang-piutang/pembayaran/edit.blade.php` | Form edit pembayaran individu |
| Modify | `lang/id/hutang.php` | Key baru |
| Modify | `lang/en/hutang.php` | Key baru |
| Modify | `CHANGELOG.md` | Entry v1.3.0 |
| Modify | `VERSION` | `1.3.0` |

---

## Task 1 — Fix Database Schema

**Files:**
- Create: `database/migrations/2026_05_27_000001_fix_hutang_piutang_pembayaran_table.php`
- Create: `database/migrations/2026_05_27_000002_add_cicilan_fields_to_hutang_piutang.php`

- [ ] **Step 1: Buat migration fix tabel pembayaran**

```php
<?php
// database/migrations/2026_05_27_000001_fix_hutang_piutang_pembayaran_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hutang_piutang_pembayaran', function (Blueprint $table) {
            // user_id jadi nullable karena tidak selalu ada context auth
            $table->foreignId('user_id')->nullable()->change();
            // tambah sumber_transaksi_id yang hilang dari migration awal
            $table->foreignId('sumber_transaksi_id')
                  ->nullable()
                  ->after('hutang_piutang_id')
                  ->constrained('sumber_transaksi')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hutang_piutang_pembayaran', function (Blueprint $table) {
            $table->dropForeign(['sumber_transaksi_id']);
            $table->dropColumn('sumber_transaksi_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 2: Buat migration cicilan fields di hutang_piutang**

```php
<?php
// database/migrations/2026_05_27_000002_add_cicilan_fields_to_hutang_piutang.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hutang_piutang', function (Blueprint $table) {
            $table->enum('tipe_pembayaran', ['sekali', 'cicilan'])->default('sekali')->after('status');
            $table->decimal('jumlah_cicilan', 15, 2)->nullable()->after('tipe_pembayaran');
            $table->enum('frekuensi_cicilan', ['mingguan', 'bulanan', 'tahunan'])->nullable()->after('jumlah_cicilan');
        });
    }

    public function down(): void
    {
        Schema::table('hutang_piutang', function (Blueprint $table) {
            $table->dropColumn(['tipe_pembayaran', 'jumlah_cicilan', 'frekuensi_cicilan']);
        });
    }
};
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

Expected output:
```
INFO  Running migrations.
  2026_05_27_000001_fix_hutang_piutang_pembayaran_table ........... DONE
  2026_05_27_000002_add_cicilan_fields_to_hutang_piutang .......... DONE
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_27_000001_fix_hutang_piutang_pembayaran_table.php
git add database/migrations/2026_05_27_000002_add_cicilan_fields_to_hutang_piutang.php
git commit -m "fix: tambah sumber_transaksi_id & cicilan fields via migration"
```

---

## Task 2 — Fix Model HutangPiutangPembayaran

**Files:**
- Modify: `app/Models/HutangPiutangPembayaran.php`
- Modify: `app/Models/HutangPiutang.php`

- [ ] **Step 1: Update HutangPiutangPembayaran model**

Ganti seluruh isi `app/Models/HutangPiutangPembayaran.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HutangPiutangPembayaran extends Model
{
    use HasFactory;

    protected $table = 'hutang_piutang_pembayaran';

    protected $fillable = [
        'hutang_piutang_id',
        'user_id',
        'sumber_transaksi_id',
        'jumlah',
        'tanggal',
        'keterangan',
    ];

    protected $casts = [
        'jumlah'  => 'decimal:2',
        'tanggal' => 'date',
    ];

    public function hutangPiutang(): BelongsTo
    {
        return $this->belongsTo(HutangPiutang::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sumberTransaksi(): BelongsTo
    {
        return $this->belongsTo(SumberTransaksi::class);
    }
}
```

- [ ] **Step 2: Update HutangPiutang model — tambah cicilan fields & helper**

Tambahkan ke `$fillable` dan `$casts`, serta accessor baru di `app/Models/HutangPiutang.php`:

```php
// Di $fillable, tambahkan:
'tipe_pembayaran',
'jumlah_cicilan',
'frekuensi_cicilan',

// Di $casts, tambahkan:
'jumlah_cicilan' => 'decimal:2',

// Tambah method baru di bawah getPersentaseTerbayarAttribute():
public function getJadwalCicilanBerikutnyaAttribute(): ?\Carbon\Carbon
{
    if ($this->tipe_pembayaran !== 'cicilan' || $this->status === 'lunas') {
        return null;
    }

    $pembayaranTerakhir = $this->pembayaran()->latest('tanggal')->first();
    $base = $pembayaranTerakhir
        ? \Carbon\Carbon::parse($pembayaranTerakhir->tanggal)
        : \Carbon\Carbon::parse($this->tanggal_mulai);

    return match ($this->frekuensi_cicilan) {
        'mingguan' => $base->addWeek(),
        'tahunan'  => $base->addYear(),
        default    => $base->addMonth(),
    };
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/HutangPiutangPembayaran.php app/Models/HutangPiutang.php
git commit -m "fix: perbaiki model HutangPiutangPembayaran — fillable, relasi, cicilan accessor"
```

---

## Task 3 — Fix HutangPiutangService

**Files:**
- Modify: `app/Services/HutangPiutangService.php`

- [ ] **Step 1: Perbaiki `create()` — tambah cicilan fields**

Ganti method `create()`:

```php
public function create(array $data): HutangPiutang
{
    return HutangPiutang::create([
        'household_id'        => auth()->user()->household_id,
        'jenis'               => $data['jenis'],
        'nama_pihak'          => $data['nama_pihak'],
        'jumlah_total'        => $data['jumlah'],
        'jumlah_terbayar'     => 0,
        'tanggal_mulai'       => $data['tanggal'] ?? Carbon::now(),
        'tanggal_jatuh_tempo' => $data['jatuh_tempo'] ?? null,
        'keterangan'          => $data['keterangan'] ?? null,
        'status'              => 'aktif',
        'tipe_pembayaran'     => $data['tipe_pembayaran'] ?? 'sekali',
        'jumlah_cicilan'      => $data['tipe_pembayaran'] === 'cicilan' ? $data['jumlah_cicilan'] : null,
        'frekuensi_cicilan'   => $data['tipe_pembayaran'] === 'cicilan' ? $data['frekuensi_cicilan'] : null,
    ]);
}
```

- [ ] **Step 2: Perbaiki `bayar()` — set user_id, sumber_transaksi_id**

Ganti blok `HutangPiutangPembayaran::create(...)` di dalam method `bayar()`:

```php
$pembayaran = HutangPiutangPembayaran::create([
    'hutang_piutang_id'   => $hutangPiutang->id,
    'user_id'             => auth()->id(),
    'sumber_transaksi_id' => $data['sumber_transaksi_id'],
    'jumlah'              => $jumlahBayar,
    'tanggal'             => $data['tanggal'] ?? Carbon::now(),
    'keterangan'          => $data['keterangan'] ?? null,
]);
```

- [ ] **Step 3: Tambah method `editPembayaran()`**

Tambahkan setelah method `bayar()`:

```php
/**
 * Edit record pembayaran yang sudah ada.
 * Reversal saldo lama, terapkan saldo baru.
 */
public function editPembayaran(HutangPiutangPembayaran $pembayaran, array $data): HutangPiutangPembayaran
{
    return DB::transaction(function () use ($pembayaran, $data) {
        $hp     = $pembayaran->hutangPiutang;
        $selisih = $data['jumlah'] - $pembayaran->jumlah;

        // Validasi: jumlah baru tidak boleh melebihi (sisa + jumlah lama)
        if ($selisih > $hp->sisa) {
            throw new \Exception('Jumlah pembayaran melebihi sisa hutang/piutang');
        }

        // Reversal saldo sumber lama
        if ($pembayaran->sumber_transaksi_id) {
            $sumberLama = SumberTransaksi::findOrFail($pembayaran->sumber_transaksi_id);
            if ($hp->jenis === 'hutang') {
                $sumberLama->increment('saldo_saat_ini', $pembayaran->jumlah);
            } else {
                $sumberLama->decrement('saldo_saat_ini', $pembayaran->jumlah);
            }
        }

        // Terapkan sumber & jumlah baru
        $sumberBaru = SumberTransaksi::findOrFail($data['sumber_transaksi_id']);
        if ($hp->jenis === 'hutang') {
            if ($sumberBaru->saldo_saat_ini < $data['jumlah']) {
                throw new \Exception('Saldo sumber transaksi tidak mencukupi');
            }
            $sumberBaru->decrement('saldo_saat_ini', $data['jumlah']);
        } else {
            $sumberBaru->increment('saldo_saat_ini', $data['jumlah']);
        }

        // Update jumlah_terbayar di hutang_piutang
        $hp->decrement('jumlah_terbayar', $pembayaran->jumlah);
        $hp->increment('jumlah_terbayar', $data['jumlah']);

        // Update status lunas jika perlu
        $hp->refresh();
        if ($hp->sisa <= 0) {
            $hp->update(['status' => 'lunas']);
        } elseif ($hp->status === 'lunas') {
            $hp->update(['status' => 'aktif']);
        }

        $pembayaran->update([
            'sumber_transaksi_id' => $data['sumber_transaksi_id'],
            'jumlah'              => $data['jumlah'],
            'tanggal'             => $data['tanggal'],
            'keterangan'          => $data['keterangan'] ?? null,
        ]);

        return $pembayaran->fresh();
    });
}
```

- [ ] **Step 4: Tambah method `hapusPembayaran()`**

Tambahkan setelah `editPembayaran()`:

```php
/**
 * Hapus record pembayaran dan reversal saldo.
 */
public function hapusPembayaran(HutangPiutangPembayaran $pembayaran): bool
{
    return DB::transaction(function () use ($pembayaran) {
        $hp = $pembayaran->hutangPiutang;

        // Reversal saldo
        if ($pembayaran->sumber_transaksi_id) {
            $sumber = SumberTransaksi::findOrFail($pembayaran->sumber_transaksi_id);
            if ($hp->jenis === 'hutang') {
                $sumber->increment('saldo_saat_ini', $pembayaran->jumlah);
            } else {
                $sumber->decrement('saldo_saat_ini', $pembayaran->jumlah);
            }
        }

        // Kembalikan jumlah_terbayar
        $hp->decrement('jumlah_terbayar', $pembayaran->jumlah);

        // Jika sebelumnya lunas, kembalikan ke aktif
        if ($hp->status === 'lunas') {
            $hp->update(['status' => 'aktif']);
        }

        return $pembayaran->delete();
    });
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Services/HutangPiutangService.php
git commit -m "fix: perbaiki bayar() dan tambah editPembayaran(), hapusPembayaran() dengan reversal saldo"
```

---

## Task 4 — PembayaranController & Routes

**Files:**
- Create: `app/Http/Controllers/PembayaranController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Buat PembayaranController**

```php
<?php
// app/Http/Controllers/PembayaranController.php

namespace App\Http\Controllers;

use App\Models\HutangPiutangPembayaran;
use App\Models\SumberTransaksi;
use App\Services\HutangPiutangService;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    public function __construct(private HutangPiutangService $service) {}

    public function edit(HutangPiutangPembayaran $pembayaran)
    {
        $sumberTransaksi = SumberTransaksi::orderBy('nama')->get();

        return view('hutang-piutang.pembayaran.edit', compact('pembayaran', 'sumberTransaksi'));
    }

    public function update(Request $request, HutangPiutangPembayaran $pembayaran)
    {
        $request->validate([
            'sumber_transaksi_id' => 'required|exists:sumber_transaksi,id',
            'jumlah'              => 'required|numeric|min:1',
            'tanggal'             => 'required|date',
            'keterangan'          => 'nullable|string|max:500',
        ]);

        try {
            $this->service->editPembayaran($pembayaran, $request->all());

            return redirect()
                ->route('hutang-piutang.show', $pembayaran->hutang_piutang_id)
                ->with('success', __('hutang.payment_updated'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(HutangPiutangPembayaran $pembayaran)
    {
        $hutangPiutangId = $pembayaran->hutang_piutang_id;

        try {
            $this->service->hapusPembayaran($pembayaran);

            return redirect()
                ->route('hutang-piutang.show', $hutangPiutangId)
                ->with('success', __('hutang.payment_deleted'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

- [ ] **Step 2: Tambah routes di `routes/web.php`**

Temukan baris:
```php
Route::post('/hutang-piutang/{hutangPiutang}/bayar', ...)->name('hutang-piutang.bayar');
```

Tambahkan setelah baris tersebut:
```php
Route::get('/pembayaran/{pembayaran}/edit', [\App\Http\Controllers\PembayaranController::class, 'edit'])->name('pembayaran.edit');
Route::put('/pembayaran/{pembayaran}', [\App\Http\Controllers\PembayaranController::class, 'update'])->name('pembayaran.update');
Route::delete('/pembayaran/{pembayaran}', [\App\Http\Controllers\PembayaranController::class, 'destroy'])->name('pembayaran.destroy');
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/PembayaranController.php routes/web.php
git commit -m "feat: tambah PembayaranController dan routes edit/delete pembayaran"
```

---

## Task 5 — View: Form Edit Pembayaran

**Files:**
- Create: `resources/views/hutang-piutang/pembayaran/edit.blade.php`

- [ ] **Step 1: Buat view edit pembayaran**

```bash
mkdir -p resources/views/hutang-piutang/pembayaran
```

Buat file `resources/views/hutang-piutang/pembayaran/edit.blade.php`:

```blade
@extends('layouts.app')

@section('title', __('hutang.edit_payment'))
@section('page-title', __('hutang.edit_payment'))

@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4 p-md-5">

            @if($errors->any())
                <div class="alert alert-danger py-2 mb-4">
                    <ul class="mb-0 ps-3 small">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-4 p-3 bg-light rounded">
                <div class="small text-muted mb-1">{{ ucfirst($pembayaran->hutangPiutang->jenis) }} — {{ $pembayaran->hutangPiutang->nama_pihak }}</div>
                <div class="fw-semibold">Rp {{ number_format($pembayaran->hutangPiutang->jumlah_total, 0, ',', '.') }}</div>
            </div>

            <form method="POST" action="{{ route('pembayaran.update', $pembayaran) }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.source') }} <span class="text-danger">*</span></label>
                    <select name="sumber_transaksi_id" required
                            class="form-select @error('sumber_transaksi_id') is-invalid @enderror">
                        <option value="">— {{ __('hutang.source') }} —</option>
                        @foreach($sumberTransaksi as $s)
                            <option value="{{ $s->id }}"
                                {{ old('sumber_transaksi_id', $pembayaran->sumber_transaksi_id) == $s->id ? 'selected' : '' }}>
                                {{ $s->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('sumber_transaksi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('hutang.amount') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="jumlah" min="1" step="any" required
                               value="{{ old('jumlah', $pembayaran->jumlah) }}"
                               class="form-control @error('jumlah') is-invalid @enderror">
                        @error('jumlah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('messages.date') }}</label>
                    <input type="date" name="tanggal" required
                           value="{{ old('tanggal', optional($pembayaran->tanggal)->format('Y-m-d')) }}"
                           class="form-control @error('tanggal') is-invalid @enderror">
                    @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">{{ __('hutang.notes') }}</label>
                    <input type="text" name="keterangan" placeholder="{{ __('hutang.notes') }}"
                           value="{{ old('keterangan', $pembayaran->keterangan) }}"
                           class="form-control">
                </div>

                <div class="d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary flex-fill fw-medium">{{ __('hutang.save') }}</button>
                    <a href="{{ route('hutang-piutang.show', $pembayaran->hutang_piutang_id) }}"
                       class="btn btn-outline-secondary flex-fill">{{ __('hutang.cancel') }}</a>
                </div>
            </form>

        </div>
    </div>
</div>
</div>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/hutang-piutang/pembayaran/edit.blade.php
git commit -m "feat: tambah view edit pembayaran individu"
```

---

## Task 6 — View: Update create.blade.php (mode cicilan)

**Files:**
- Modify: `resources/views/hutang-piutang/create.blade.php`
- Modify: `app/Http/Controllers/HutangPiutangController.php`

- [ ] **Step 1: Tambah validasi cicilan di HutangPiutangController::store()**

Ganti blok `$request->validate(...)` di method `store()`:

```php
$request->validate([
    'jenis'             => 'required|in:hutang,piutang',
    'nama_pihak'        => 'required|string|max:255',
    'jumlah'            => 'required|numeric|min:1',
    'tanggal'           => 'nullable|date',
    'jatuh_tempo'       => 'nullable|date|after:tanggal',
    'keterangan'        => 'nullable|string|max:500',
    'tipe_pembayaran'   => 'required|in:sekali,cicilan',
    'jumlah_cicilan'    => 'required_if:tipe_pembayaran,cicilan|nullable|numeric|min:1',
    'frekuensi_cicilan' => 'required_if:tipe_pembayaran,cicilan|nullable|in:mingguan,bulanan,tahunan',
]);
```

- [ ] **Step 2: Update create.blade.php — tambah section mode pembayaran**

Tambahkan blok berikut **setelah** `</div>` penutup field `jatuh_tempo` (setelah baris `</div>` dari `.row.g-3.mb-3`), sebelum field `keterangan`:

```blade
{{-- Mode Pembayaran --}}
<div class="mb-3">
    <label class="form-label fw-medium">{{ __('hutang.payment_mode') }} <span class="text-danger">*</span></label>
    <div class="row g-2">
        <div class="col-6">
            <div class="form-check border rounded p-3" style="cursor:pointer;" id="card-sekali">
                <input class="form-check-input" type="radio" name="tipe_pembayaran" value="sekali"
                       id="tipeSekali" {{ old('tipe_pembayaran', 'sekali') === 'sekali' ? 'checked' : '' }}>
                <label class="form-check-label w-100" for="tipeSekali" style="cursor:pointer;">
                    <div class="fw-medium small">{{ __('hutang.payment_once') }}</div>
                    <div class="text-muted" style="font-size:.7rem;">{{ __('hutang.payment_once_desc') }}</div>
                </label>
            </div>
        </div>
        <div class="col-6">
            <div class="form-check border rounded p-3" style="cursor:pointer;" id="card-cicilan">
                <input class="form-check-input" type="radio" name="tipe_pembayaran" value="cicilan"
                       id="tipeCicilan" {{ old('tipe_pembayaran') === 'cicilan' ? 'checked' : '' }}>
                <label class="form-check-label w-100" for="tipeCicilan" style="cursor:pointer;">
                    <div class="fw-medium small">{{ __('hutang.payment_installment') }}</div>
                    <div class="text-muted" style="font-size:.7rem;">{{ __('hutang.payment_installment_desc') }}</div>
                </label>
            </div>
        </div>
    </div>
</div>

{{-- Detail cicilan (tampil jika cicilan dipilih) --}}
<div id="cicilan-fields" class="mb-3 {{ old('tipe_pembayaran') === 'cicilan' ? '' : 'd-none' }}">
    <div class="row g-3 p-3 border rounded bg-light">
        <div class="col-md-6">
            <label class="form-label fw-medium small">{{ __('hutang.installment_amount') }} <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
                <span class="input-group-text">Rp</span>
                <input type="number" name="jumlah_cicilan" min="1" step="any"
                       value="{{ old('jumlah_cicilan') }}" placeholder="0"
                       class="form-control @error('jumlah_cicilan') is-invalid @enderror">
                @error('jumlah_cicilan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium small">{{ __('hutang.installment_freq') }} <span class="text-danger">*</span></label>
            <select name="frekuensi_cicilan"
                    class="form-select form-select-sm @error('frekuensi_cicilan') is-invalid @enderror">
                <option value="">—</option>
                <option value="mingguan" {{ old('frekuensi_cicilan') === 'mingguan' ? 'selected' : '' }}>{{ __('hutang.freq_weekly') }}</option>
                <option value="bulanan"  {{ old('frekuensi_cicilan') === 'bulanan'  ? 'selected' : '' }}>{{ __('hutang.freq_monthly') }}</option>
                <option value="tahunan"  {{ old('frekuensi_cicilan') === 'tahunan'  ? 'selected' : '' }}>{{ __('hutang.freq_yearly') }}</option>
            </select>
            @error('frekuensi_cicilan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
```

- [ ] **Step 3: Tambah JavaScript toggle cicilan di akhir blade (sebelum `@endsection`)**

```blade
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios  = document.querySelectorAll('input[name="tipe_pembayaran"]');
    const fields  = document.getElementById('cicilan-fields');

    function toggle() {
        const isCicilan = document.querySelector('input[name="tipe_pembayaran"]:checked')?.value === 'cicilan';
        fields.classList.toggle('d-none', !isCicilan);
    }

    radios.forEach(r => r.addEventListener('change', toggle));
});
</script>
@endpush
```

Pastikan layout `layouts/app.blade.php` memiliki `@stack('scripts')` sebelum `</body>`. Jika belum ada, tambahkan:
```blade
@stack('scripts')
</body>
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/hutang-piutang/create.blade.php app/Http/Controllers/HutangPiutangController.php
git commit -m "feat: tambah pilihan mode pembayaran sekali/cicilan di form create"
```

---

## Task 7 — View: Update show.blade.php

**Files:**
- Modify: `resources/views/hutang-piutang/show.blade.php`

- [ ] **Step 1: Ganti section riwayat pembayaran dengan edit/delete per row**

Ganti keseluruhan blok `{{-- Riwayat pembayaran --}}` (dari baris `<div class="card border-0 shadow-sm"...>` hingga akhir `@forelse`):

```blade
{{-- Jadwal cicilan berikutnya (hanya jika tipe cicilan) --}}
@if($hutangPiutang->tipe_pembayaran === 'cicilan' && $hutangPiutang->status !== 'lunas')
    <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-4 small border-0 rounded-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16">
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
        </svg>
        <span>
            {{ __('hutang.next_installment') }}:
            <strong>Rp {{ number_format($hutangPiutang->jumlah_cicilan, 0, ',', '.') }}</strong>
            — {{ optional($hutangPiutang->jadwal_cicilan_berikutnya)->translatedFormat('d M Y') ?? '-' }}
            ({{ __('hutang.freq_' . $hutangPiutang->frekuensi_cicilan) }})
        </span>
    </div>
@endif

{{-- Riwayat pembayaran --}}
<div class="card border-0 shadow-sm" style="border-radius:.75rem;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
        <h6 class="fw-semibold mb-0">{{ __('laporan.transactions') }}</h6>
    </div>
    <div class="card-body p-0">
        @forelse($riwayat ?? [] as $r)
            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                <div class="flex-grow-1">
                    <div class="small fw-medium">{{ $r->keterangan ?: __('hutang.payment') }}</div>
                    <div class="text-muted" style="font-size:.72rem;">
                        {{ optional($r->tanggal)->translatedFormat('d M Y') ?? $r->created_at->translatedFormat('d M Y') }}
                        @if($r->sumberTransaksi)
                            · {{ $r->sumberTransaksi->nama }}
                        @endif
                    </div>
                </div>
                <div class="small fw-semibold text-success me-2">
                    Rp {{ number_format($r->jumlah, 0, ',', '.') }}
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('pembayaran.edit', $r) }}"
                       class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:.72rem;">
                        {{ __('messages.edit') }}
                    </a>
                    <form method="POST" action="{{ route('pembayaran.destroy', $r) }}"
                          onsubmit="return confirm('{{ __('hutang.delete_payment_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size:.72rem;">
                            {{ __('messages.delete') }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="py-4 text-center text-muted small">{{ __('messages.no_data') }}</div>
        @endforelse
    </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/hutang-piutang/show.blade.php
git commit -m "feat: tampilkan tombol edit/delete per riwayat pembayaran dan info jadwal cicilan"
```

---

## Task 8 — i18n: Lang Files

**Files:**
- Modify: `lang/id/hutang.php`
- Modify: `lang/en/hutang.php`

- [ ] **Step 1: Tambah keys baru di `lang/id/hutang.php`**

Tambahkan di akhir array sebelum `]`:

```php
    'payment_mode'              => 'Mode Pembayaran',
    'payment_once'              => 'Sekali Bayar',
    'payment_once_desc'         => 'Lunas dalam satu kali pembayaran',
    'payment_installment'       => 'Cicilan',
    'payment_installment_desc'  => 'Dibayar bertahap secara berkala',
    'installment_amount'        => 'Nominal per Cicilan',
    'installment_freq'          => 'Frekuensi Cicilan',
    'freq_weekly'               => 'Mingguan',
    'freq_monthly'              => 'Bulanan',
    'freq_yearly'               => 'Tahunan',
    'next_installment'          => 'Cicilan berikutnya',
    'payment'                   => 'Pembayaran',
    'payment_updated'           => 'Pembayaran berhasil diperbarui',
    'payment_deleted'           => 'Pembayaran berhasil dihapus',
    'delete_payment_confirm'    => 'Hapus catatan pembayaran ini?',
    'edit_payment'              => 'Edit Pembayaran',
```

- [ ] **Step 2: Tambah keys baru di `lang/en/hutang.php`**

Tambahkan di akhir array sebelum `]`:

```php
    'payment_mode'              => 'Payment Mode',
    'payment_once'              => 'One-Time',
    'payment_once_desc'         => 'Settled in a single payment',
    'payment_installment'       => 'Installment',
    'payment_installment_desc'  => 'Paid periodically over time',
    'installment_amount'        => 'Amount per Installment',
    'installment_freq'          => 'Installment Frequency',
    'freq_weekly'               => 'Weekly',
    'freq_monthly'              => 'Monthly',
    'freq_yearly'               => 'Yearly',
    'next_installment'          => 'Next installment',
    'payment'                   => 'Payment',
    'payment_updated'           => 'Payment updated successfully',
    'payment_deleted'           => 'Payment deleted successfully',
    'delete_payment_confirm'    => 'Delete this payment record?',
    'edit_payment'              => 'Edit Payment',
```

- [ ] **Step 3: Commit**

```bash
git add lang/id/hutang.php lang/en/hutang.php
git commit -m "feat: tambah i18n keys untuk mode cicilan dan pembayaran"
```

---

## Task 9 — CHANGELOG & Version Bump

**Files:**
- Modify: `CHANGELOG.md`
- Modify: `VERSION`

- [ ] **Step 1: Update CHANGELOG.md**

Tambahkan entry baru di atas `## [1.2.1]`:

```markdown
## [1.3.0] — 2026-05-27

### Fixed
- **Pembayaran hutang/piutang gagal disimpan**: tabel `hutang_piutang_pembayaran` tidak memiliki kolom `sumber_transaksi_id` (ada di service tapi tidak di migration) — ditambahkan via migration baru
- **Error `user_id` NOT NULL**: kolom `user_id` di tabel `hutang_piutang_pembayaran` tidak di-set oleh `HutangPiutangService::bayar()` — kini di-set dari `auth()->id()` dan kolom dijadikan nullable
- **Relasi `sumberTransaksi` tidak ada**: `HutangPiutangPembayaran` model tidak punya relasi `sumberTransaksi()` meski di-eager-load di service — relasi ditambahkan
- **step="1000" pada input amount** memblokir semua nilai yang bukan kelipatan (n×1000)+1 — diganti `step="any"` (fix ini sudah di v1.2.2 minor)

### Added
- **Edit pembayaran individu**: setiap record di riwayat pembayaran kini bisa diedit (nominal, sumber dana, tanggal, catatan) dengan reversal saldo otomatis untuk sumber dana lama dan aplikasi ke sumber dana baru
- **Hapus pembayaran individu**: record pembayaran bisa dihapus dengan reversal saldo dan status hutang/piutang dikembalikan ke aktif jika lunas sebelumnya
- **Mode pembayaran Cicilan**: saat membuat hutang/piutang baru, user bisa memilih mode *Sekali Bayar* atau *Cicilan* beserta nominal per cicilan dan frekuensi (mingguan/bulanan/tahunan)
- **Jadwal cicilan berikutnya**: halaman detail menampilkan tanggal perkiraan cicilan berikutnya berdasarkan pembayaran terakhir
- **Nama sumber dana di riwayat**: setiap row riwayat pembayaran menampilkan nama sumber dana yang digunakan
- **Multilanguage**: semua teks UI baru tersedia di `lang/id/hutang.php` dan `lang/en/hutang.php`
```

- [ ] **Step 2: Update VERSION**

Ganti isi file:
```
1.3.0
```

- [ ] **Step 3: Commit final**

```bash
git add CHANGELOG.md VERSION
git commit -m "chore: bump version ke 1.3.0 — pembayaran hutang/piutang fix & cicilan"
```

---

## Verification Checklist

Setelah semua task selesai, verifikasi manual:

- [ ] Submit form tambah hutang/piutang dengan nilai **Rp 75.000** → tidak ada error "enter valid value"
- [ ] Submit pembayaran dari halaman show → pembayaran tersimpan, saldo sumber berkurang/bertambah, progress bar update
- [ ] Klik **Edit** di riwayat → form edit terbuka, ubah nilai, simpan → progress & saldo terkoreksi
- [ ] Klik **Hapus** di riwayat → konfirmasi muncul, jika ya: record terhapus, saldo di-reversal, status kembali aktif
- [ ] Buat hutang baru dengan mode **Cicilan** (misal Rp 500.000/bulan) → detail halaman tampilkan jadwal cicilan berikutnya
- [ ] `php artisan route:list | grep pembayaran` → tampil 3 routes: GET edit, PUT update, DELETE destroy
