# Tag Enhancement (Phase 1 + 2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aktifkan filter tag di UI transaksi, tampilkan summary per tag, buat laporan per tag dengan chart, dan tambah widget "Top Tags" di dashboard.

**Architecture:** Phase 1 melengkapi gap yang sudah ada di backend (TransaksiService sudah support filter tag). Phase 2 menambah LaporanService::getByTag() dan DashboardService::getTopTags() sebagai dimensi analisis baru. Semua perubahan non-breaking — tidak ada migrasi, tidak ada perubahan relasi.

**Tech Stack:** Laravel 13.7 · PHP 8.3 · Blade + Bootstrap 5 · jQuery · Chart.js — tanpa build process, tanpa Tailwind/Alpine.

---

## Baseline yang Sudah Ada (Jangan Diubah)

Sebelum mulai, pahami yang SUDAH berfungsi:
- `App\Models\Tag` — `BelongsToHousehold` trait (global scope `household_id`), relasi `transaksi()` BelongsToMany via pivot `transaksi_tags`
- `App\Models\Transaksi` — relasi `tags()` BelongsToMany
- `App\Http\Controllers\TagController` — CRUD + `search()` AJAX
- `App\Services\TransaksiService::getTransaksi()` — sudah support `$filters['tags']` via `whereHas`
- `App\Http\Controllers\TransaksiController::index()` — sudah pass `$tags` ke view, sudah read `$request->tags` ke filter
- Route `tags.index`, `tags.store`, `tags.update`, `tags.destroy` sudah ada

---

## File Map

### Phase 1 — UI Filter + Summary
| Aksi | File |
|---|---|
| Modify | `app/Services/TransaksiService.php` — tambah `getSummaryByTag()` |
| Modify | `app/Http/Controllers/TagController.php` — inject TransaksiService, update `index()` |
| Modify | `resources/views/tags/index.blade.php` — tambah summary table + link laporan |
| Modify | `resources/views/transaksi/index.blade.php` — tambah filter tag pills |
| Create | `tests/Unit/Services/TransaksiServiceTagTest.php` |

### Phase 2 — Laporan & Dashboard per Tag
| Aksi | File |
|---|---|
| Modify | `app/Services/LaporanService.php` — tambah `getByTag()` |
| Modify | `app/Http/Controllers/LaporanController.php` — tambah `byTag()`, inject Tag |
| Modify | `routes/web.php` — tambah route `/laporan/tag/{tag}` |
| Create | `resources/views/laporan/tag.blade.php` |
| Modify | `resources/views/laporan/index.blade.php` — tambah card Laporan per Tag |
| Modify | `app/Services/DashboardService.php` — tambah `getTopTags()` |
| Modify | `app/Http/Controllers/DashboardController.php` — tambah widget `top_tags`, pass data |
| Modify | `resources/views/dashboard.blade.php` — tambah render widget `top_tags` |
| Create | `tests/Unit/Services/LaporanServiceTagTest.php` |

---

## Task 1 — `TransaksiService::getSummaryByTag()` + Unit Test

**Files:**
- Modify: `app/Services/TransaksiService.php`
- Create: `tests/Unit/Services/TransaksiServiceTagTest.php`

- [ ] **Step 1.1 — Buat file test**

```php
<?php
// tests/Unit/Services/TransaksiServiceTagTest.php

namespace Tests\Unit\Services;

use App\Models\Household;
use App\Models\Plan;
use App\Models\Tag;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MomentumService;
use App\Services\TransaksiService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiServiceTagTest extends TestCase
{
    use RefreshDatabase;

    private TransaksiService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $xp       = $this->mock(XpService::class);
        $momentum = $this->mock(MomentumService::class);
        $xp->shouldReceive('award')->andReturn(null)->byDefault();
        $momentum->shouldReceive('recordActivity')->andReturn(null)->byDefault();
        $this->service = new TransaksiService($xp, $momentum);

        $plan = Plan::factory()->create();
        $household = Household::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['household_id' => $household->id]);
        $this->actingAs($this->user);
    }

    public function test_getSummaryByTag_returns_only_tags_with_transaksi(): void
    {
        $tagA = Tag::factory()->create(['household_id' => $this->user->household_id]);
        Tag::factory()->create(['household_id' => $this->user->household_id]); // tagB, tanpa transaksi

        $transaksi = Transaksi::factory()->create([
            'household_id' => $this->user->household_id,
            'jenis'        => 'pengeluaran',
            'jumlah'       => 100000,
        ]);
        $transaksi->tags()->attach($tagA->id);

        $result = $this->service->getSummaryByTag();

        $this->assertCount(1, $result);
        $this->assertEquals($tagA->id, $result[0]['tag']->id);
        $this->assertEquals(100000, $result[0]['total_pengeluaran']);
        $this->assertEquals(0, $result[0]['total_pemasukan']);
        $this->assertEquals(1, $result[0]['jumlah_transaksi']);
    }

    public function test_getSummaryByTag_sums_pemasukan_and_pengeluaran_separately(): void
    {
        $tag = Tag::factory()->create(['household_id' => $this->user->household_id]);

        $t1 = Transaksi::factory()->create([
            'household_id' => $this->user->household_id,
            'jenis'        => 'pengeluaran',
            'jumlah'       => 200000,
        ]);
        $t2 = Transaksi::factory()->create([
            'household_id' => $this->user->household_id,
            'jenis'        => 'pemasukan',
            'jumlah'       => 500000,
        ]);
        $t1->tags()->attach($tag->id);
        $t2->tags()->attach($tag->id);

        $result = $this->service->getSummaryByTag();

        $this->assertCount(1, $result);
        $this->assertEquals(500000, $result[0]['total_pemasukan']);
        $this->assertEquals(200000, $result[0]['total_pengeluaran']);
        $this->assertEquals(2, $result[0]['jumlah_transaksi']);
    }

    public function test_getSummaryByTag_sorted_by_pengeluaran_desc(): void
    {
        $tagA = Tag::factory()->create(['household_id' => $this->user->household_id]);
        $tagB = Tag::factory()->create(['household_id' => $this->user->household_id]);

        $t1 = Transaksi::factory()->create([
            'household_id' => $this->user->household_id, 'jenis' => 'pengeluaran', 'jumlah' => 100000,
        ]);
        $t2 = Transaksi::factory()->create([
            'household_id' => $this->user->household_id, 'jenis' => 'pengeluaran', 'jumlah' => 500000,
        ]);
        $t1->tags()->attach($tagA->id);
        $t2->tags()->attach($tagB->id);

        $result = $this->service->getSummaryByTag();

        $this->assertEquals($tagB->id, $result[0]['tag']->id); // tagB lebih besar
        $this->assertEquals($tagA->id, $result[1]['tag']->id);
    }
}
```

- [ ] **Step 1.2 — Jalankan test, pastikan FAIL karena method belum ada**

```bash
php artisan test tests/Unit/Services/TransaksiServiceTagTest.php
```

Expected: `Error: Call to undefined method App\Services\TransaksiService::getSummaryByTag()`

- [ ] **Step 1.3 — Tambah method `getSummaryByTag()` ke TransaksiService**

Buka `app/Services/TransaksiService.php`. Tambah `use App\Models\Tag;` di bagian imports jika belum ada. Tambah method berikut setelah method `getSummary()` (sekitar baris 301):

```php
/**
 * Get ringkasan transaksi per tag untuk household saat ini.
 * Return sorted by total_pengeluaran desc, hanya tag yang punya transaksi.
 */
public function getSummaryByTag(): array
{
    $householdId = auth()->user()->household_id;

    $tags = Tag::where('household_id', $householdId)->get();

    return $tags->map(function (Tag $tag) {
        $aggregate = $tag->transaksi()
            ->selectRaw("
                SUM(CASE WHEN jenis = 'pemasukan'   THEN jumlah ELSE 0 END) as total_pemasukan,
                SUM(CASE WHEN jenis = 'pengeluaran' THEN jumlah ELSE 0 END) as total_pengeluaran,
                COUNT(*) as jumlah_transaksi
            ")
            ->first();

        return [
            'tag'               => $tag,
            'total_pemasukan'   => (float) ($aggregate->total_pemasukan   ?? 0),
            'total_pengeluaran' => (float) ($aggregate->total_pengeluaran ?? 0),
            'jumlah_transaksi'  => (int)   ($aggregate->jumlah_transaksi  ?? 0),
        ];
    })
    ->filter(fn ($item) => $item['jumlah_transaksi'] > 0)
    ->sortByDesc('total_pengeluaran')
    ->values()
    ->toArray();
}
```

- [ ] **Step 1.4 — Jalankan test lagi, pastikan PASS**

```bash
php artisan test tests/Unit/Services/TransaksiServiceTagTest.php
```

Expected: `PASS` (3 tests, 3 assertions minimum)

- [ ] **Step 1.5 — Commit**

```bash
git add app/Services/TransaksiService.php tests/Unit/Services/TransaksiServiceTagTest.php
git commit -m "feat(tag): add getSummaryByTag() to TransaksiService with unit tests"
```

---

## Task 2 — Update TagController + tags/index.blade.php

**Files:**
- Modify: `app/Http/Controllers/TagController.php`
- Modify: `resources/views/tags/index.blade.php`

- [ ] **Step 2.1 — Update TagController::index() untuk inject service dan pass summary**

Ganti seluruh `index()` method dan tambah constructor injection di `TagController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\TransaksiService;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(private readonly TransaksiService $transaksiService) {}

    public function index()
    {
        $tags       = Tag::withCount('transaksi')->orderBy('nama')->get();
        $summaryByTag = $this->transaksiService->getSummaryByTag();

        return view('tags.index', compact('tags', 'summaryByTag'));
    }

    // ... sisa method store, update, destroy, search tetap sama
```

> **Catatan:** Hanya ganti bagian class header + constructor + `index()`. Method `store`, `update`, `destroy`, `search` tidak diubah.

- [ ] **Step 2.2 — Update tags/index.blade.php: tambah summary table**

Cari blok `{{-- Daftar tags --}}` (sekitar baris 32). Tambah section summary DI ATAS card daftar tags:

```blade
{{-- Summary ringkasan per tag --}}
@if(count($summaryByTag) > 0)
<div class="card border-0 shadow-sm" style="border-radius:.75rem;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
        <h6 class="fw-semibold mb-0">Ringkasan Penggunaan Tag</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-2" style="font-size:.78rem;">Tag</th>
                        <th class="text-end py-2" style="font-size:.78rem;">Transaksi</th>
                        <th class="text-end py-2" style="font-size:.78rem;">Pengeluaran</th>
                        <th class="text-end py-2" style="font-size:.78rem;">Pemasukan</th>
                        <th class="text-end px-4 py-2" style="font-size:.78rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaryByTag as $item)
                    <tr>
                        <td class="px-4 py-2">
                            <span class="rounded-circle d-inline-block me-2" style="width:10px;height:10px;background:{{ $item['tag']->warna }};"></span>
                            <span class="small fw-medium">{{ $item['tag']->nama }}</span>
                        </td>
                        <td class="text-end py-2">
                            <span class="text-muted" style="font-size:.78rem;">{{ $item['jumlah_transaksi'] }}</span>
                        </td>
                        <td class="text-end py-2">
                            <span class="small text-danger fw-medium">Rp {{ number_format($item['total_pengeluaran'], 0, ',', '.') }}</span>
                        </td>
                        <td class="text-end py-2">
                            <span class="small text-success fw-medium">Rp {{ number_format($item['total_pemasukan'], 0, ',', '.') }}</span>
                        </td>
                        <td class="text-end px-4 py-2">
                            <a href="{{ route('laporan.tag', $item['tag']) }}"
                               class="small text-primary text-decoration-none" style="font-size:.75rem;">
                                Lihat Laporan →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
```

- [ ] **Step 2.3 — Verifikasi di browser: buka `/tags`**

Pastikan:
- Summary table muncul jika ada tag yang sudah dipakai di transaksi
- Kolom pengeluaran/pemasukan menampilkan angka Rupiah yang benar
- Link "Lihat Laporan →" ada (akan 404 dulu sampai Task 5 selesai)

- [ ] **Step 2.4 — Commit**

```bash
git add app/Http/Controllers/TagController.php resources/views/tags/index.blade.php
git commit -m "feat(tag): show summary table per tag on tags index page"
```

---

## Task 3 — Update transaksi/index.blade.php: Filter Tag Pills

**Files:**
- Modify: `resources/views/transaksi/index.blade.php`

- [ ] **Step 3.1 — Update badge "filter aktif" dan collapse state untuk include tag**

Cari baris 49 (kondisi `@if(request()->hasAny(...))`). Ada 3 tempat yang perlu diupdate — semua mengecek filter aktif:

**Baris ~49** (badge di tombol filter):
```blade
{{-- GANTI --}}
@if(request()->hasAny(['jenis', 'kategori_id', 'tanggal_dari', 'tanggal_sampai', 'search']))
{{-- DENGAN --}}
@if(request()->hasAny(['jenis', 'kategori_id', 'tanggal_dari', 'tanggal_sampai', 'search']) || request('tags'))
```

**Baris ~53** (link reset filter):
```blade
{{-- GANTI --}}
@if(request()->hasAny(['jenis', 'kategori_id', 'tanggal_dari', 'tanggal_sampai', 'search']))
{{-- DENGAN --}}
@if(request()->hasAny(['jenis', 'kategori_id', 'tanggal_dari', 'tanggal_sampai', 'search']) || request('tags'))
```

**Baris ~61** (collapse show state):
```blade
{{-- GANTI --}}
<div class="collapse {{ request()->hasAny(['jenis','kategori_id','tanggal_dari','tanggal_sampai','search']) ? 'show' : '' }} mt-3"
{{-- DENGAN --}}
<div class="collapse {{ request()->hasAny(['jenis','kategori_id','tanggal_dari','tanggal_sampai','search']) || request('tags') ? 'show' : '' }} mt-3"
```

- [ ] **Step 3.2 — Tambah filter tag setelah filter tanggal_sampai (sebelum tombol Apply)**

Cari blok `col-6 col-lg-2` untuk tanggal_sampai (sekitar baris 94-98). Tambah blok tag filter SETELAH div tanggal_sampai dan SEBELUM div tombol apply:

```blade
{{-- Filter Tags --}}
@if($tags->count())
<div class="col-12">
    <label class="form-label small fw-medium">Tag</label>
    <div class="d-flex flex-wrap gap-2">
        @foreach($tags as $tag)
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input" type="checkbox"
                       name="tags[]"
                       value="{{ $tag->id }}"
                       id="filter-tag-{{ $tag->id }}"
                       {{ in_array($tag->id, (array) request('tags', [])) ? 'checked' : '' }}>
                <label class="form-check-label" for="filter-tag-{{ $tag->id }}"
                       style="font-size:.78rem;">
                    <span class="rounded-circle d-inline-block me-1"
                          style="width:8px;height:8px;background:{{ $tag->warna }};"></span>
                    {{ $tag->nama }}
                </label>
            </div>
        @endforeach
    </div>
</div>
@endif
```

- [ ] **Step 3.3 — Verifikasi di browser: buka `/transaksi`**

Pastikan:
1. Tag filter muncul di panel filter (jika ada tag yang sudah dibuat)
2. Pilih 1-2 tag → klik Apply → URL berubah menjadi `?tags[]=1&tags[]=2`
3. Daftar transaksi ter-filter hanya menampilkan yang punya tag tersebut (OR logic)
4. Badge "● aktif" muncul di tombol filter saat tag dipilih
5. Link "Reset Filter" muncul saat tag dipilih
6. Panel filter tetap terbuka setelah apply

- [ ] **Step 3.4 — Commit**

```bash
git add resources/views/transaksi/index.blade.php
git commit -m "feat(tag): add tag filter pills to transaksi index filter panel"
```

---

## Task 4 — `LaporanService::getByTag()` + Unit Test

**Files:**
- Modify: `app/Services/LaporanService.php`
- Create: `tests/Unit/Services/LaporanServiceTagTest.php`

- [ ] **Step 4.1 — Buat file test**

```php
<?php
// tests/Unit/Services/LaporanServiceTagTest.php

namespace Tests\Unit\Services;

use App\Models\Household;
use App\Models\Plan;
use App\Models\Tag;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanServiceTagTest extends TestCase
{
    use RefreshDatabase;

    private LaporanService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LaporanService();

        $plan      = Plan::factory()->create();
        $household = Household::factory()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['household_id' => $household->id]);
        $this->actingAs($this->user);
    }

    public function test_getByTag_returns_correct_summary(): void
    {
        $tag = Tag::factory()->create(['household_id' => $this->user->household_id]);

        $dari   = Carbon::now()->startOfMonth()->format('Y-m-d');
        $sampai = Carbon::now()->endOfMonth()->format('Y-m-d');

        $t1 = Transaksi::factory()->create([
            'household_id' => $this->user->household_id,
            'jenis'        => 'pengeluaran',
            'jumlah'       => 300000,
            'tanggal'      => Carbon::now()->format('Y-m-d'),
        ]);
        $t2 = Transaksi::factory()->create([
            'household_id' => $this->user->household_id,
            'jenis'        => 'pemasukan',
            'jumlah'       => 1000000,
            'tanggal'      => Carbon::now()->format('Y-m-d'),
        ]);
        $t1->tags()->attach($tag->id);
        $t2->tags()->attach($tag->id);

        $result = $this->service->getByTag($tag, $dari, $sampai);

        $this->assertEquals($tag->id, $result['tag']->id);
        $this->assertEquals(300000, $result['total_pengeluaran']);
        $this->assertEquals(1000000, $result['total_pemasukan']);
        $this->assertEquals(700000, $result['cashflow']);
        $this->assertEquals(2, $result['summary']['total_transaksi']);
    }

    public function test_getByTag_excludes_transaksi_outside_date_range(): void
    {
        $tag = Tag::factory()->create(['household_id' => $this->user->household_id]);

        $dari   = Carbon::now()->startOfMonth()->format('Y-m-d');
        $sampai = Carbon::now()->endOfMonth()->format('Y-m-d');

        $dalam = Transaksi::factory()->create([
            'household_id' => $this->user->household_id,
            'jenis'        => 'pengeluaran',
            'jumlah'       => 100000,
            'tanggal'      => Carbon::now()->format('Y-m-d'),
        ]);
        $luar = Transaksi::factory()->create([
            'household_id' => $this->user->household_id,
            'jenis'        => 'pengeluaran',
            'jumlah'       => 999000,
            'tanggal'      => Carbon::now()->subMonths(3)->format('Y-m-d'),
        ]);
        $dalam->tags()->attach($tag->id);
        $luar->tags()->attach($tag->id);

        $result = $this->service->getByTag($tag, $dari, $sampai);

        $this->assertEquals(100000, $result['total_pengeluaran']);
        $this->assertEquals(1, $result['summary']['total_transaksi']);
    }
}
```

- [ ] **Step 4.2 — Jalankan test, pastikan FAIL**

```bash
php artisan test tests/Unit/Services/LaporanServiceTagTest.php
```

Expected: `Error: Call to undefined method App\Services\LaporanService::getByTag()`

- [ ] **Step 4.3 — Tambah method `getByTag()` ke LaporanService**

Buka `app/Services/LaporanService.php`. Tambah `use App\Models\Tag;` di imports. Tambah method berikut setelah `perbandinganBulan()`:

```php
/**
 * Laporan semua transaksi dengan tag tertentu dalam rentang tanggal.
 */
public function getByTag(Tag $tag, string $dari, string $sampai): array
{
    $start = Carbon::parse($dari)->startOfDay();
    $end   = Carbon::parse($sampai)->endOfDay();

    $transaksi = Transaksi::with(['kategori', 'sumberTransaksi', 'user'])
        ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
        ->whereBetween('tanggal', [$start, $end])
        ->orderBy('tanggal', 'desc')
        ->get();

    $pemasukan   = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
    $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');

    // Tren 6 bulan terakhir (untuk chart)
    $perBulan = [];
    for ($i = 5; $i >= 0; $i--) {
        $date   = Carbon::now()->subMonths($i);
        $bulanT = $transaksi->filter(
            fn ($t) => $t->tanggal->year == $date->year && $t->tanggal->month == $date->month
        );
        $perBulan[] = [
            'bulan'        => $date->translatedFormat('M Y'),
            'pemasukan'    => $bulanT->where('jenis', 'pemasukan')->sum('jumlah'),
            'pengeluaran'  => $bulanT->where('jenis', 'pengeluaran')->sum('jumlah'),
        ];
    }

    $perKategori = $this->groupByKategori($transaksi->where('jenis', 'pengeluaran'));

    return [
        'tag'               => $tag,
        'periode'           => $start->translatedFormat('d M Y') . ' s/d ' . $end->translatedFormat('d M Y'),
        'dari'              => $dari,
        'sampai'            => $sampai,
        'transaksi'         => $transaksi,
        'total_pemasukan'   => $pemasukan,
        'total_pengeluaran' => $pengeluaran,
        'cashflow'          => $pemasukan - $pengeluaran,
        'per_bulan'         => $perBulan,
        'per_kategori'      => $perKategori,
        'summary' => [
            'pemasukan'       => $pemasukan,
            'pengeluaran'     => $pengeluaran,
            'selisih'         => $pemasukan - $pengeluaran,
            'total_transaksi' => $transaksi->count(),
        ],
    ];
}
```

- [ ] **Step 4.4 — Jalankan test lagi, pastikan PASS**

```bash
php artisan test tests/Unit/Services/LaporanServiceTagTest.php
```

Expected: `PASS` (2 tests)

- [ ] **Step 4.5 — Commit**

```bash
git add app/Services/LaporanService.php tests/Unit/Services/LaporanServiceTagTest.php
git commit -m "feat(tag): add getByTag() to LaporanService with unit tests"
```

---

## Task 5 — Route + LaporanController::byTag() + View laporan/tag.blade.php

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/LaporanController.php`
- Create: `resources/views/laporan/tag.blade.php`

- [ ] **Step 5.1 — Tambah route di web.php**

Buka `routes/web.php`. Di dalam group `Route::prefix('laporan')->name('laporan.')->group(...)` (sekitar baris 83-91), tambah route setelah `perbandingan`:

```php
Route::get('/tag/{tag}', [LaporanController::class, 'byTag'])->name('tag');
```

Hasil akhir group laporan:
```php
Route::prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', [LaporanController::class, 'index'])->name('index');
    Route::get('/harian', [LaporanController::class, 'harian'])->name('harian');
    Route::get('/mingguan', [LaporanController::class, 'mingguan'])->name('mingguan');
    Route::get('/bulanan', [LaporanController::class, 'bulanan'])->name('bulanan');
    Route::get('/tahunan', [LaporanController::class, 'tahunan'])->name('tahunan');
    Route::get('/perbandingan', [LaporanController::class, 'perbandingan'])->name('perbandingan');
    Route::post('/export', [LaporanController::class, 'export'])->name('export');
    Route::get('/tag/{tag}', [LaporanController::class, 'byTag'])->name('tag');
});
```

- [ ] **Step 5.2 — Tambah method `byTag()` ke LaporanController**

Buka `app/Http/Controllers/LaporanController.php`. Tambah `use App\Models\Tag;` di imports. Tambah method berikut setelah `perbandingan()`:

```php
/**
 * Laporan per tag
 */
public function byTag(Request $request, Tag $tag)
{
    $dari   = $request->dari   ?? Carbon::now()->startOfMonth()->format('Y-m-d');
    $sampai = $request->sampai ?? Carbon::now()->endOfMonth()->format('Y-m-d');

    $data = $this->laporanService->getByTag($tag, $dari, $sampai);

    return view('laporan.tag', compact('data', 'tag', 'dari', 'sampai'));
}
```

- [ ] **Step 5.3 — Verifikasi route terdaftar**

```bash
php artisan route:list --name=laporan.tag
```

Expected: `GET|HEAD  laporan/tag/{tag}  laporan.tag`

- [ ] **Step 5.4 — Buat view `resources/views/laporan/tag.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'Laporan Tag: ' . $tag->nama)
@section('page-title', 'Laporan Tag')

@section('content')
<div class="row g-4">

    {{-- Header Tag --}}
    <div class="col-12">
        <div class="d-flex align-items-center gap-3">
            <span class="rounded-circle flex-shrink-0"
                  style="width:18px;height:18px;background:{{ $tag->warna }};display:inline-block;"></span>
            <h5 class="mb-0 fw-bold">{{ $tag->nama }}</h5>
            <a href="{{ route('laporan.index') }}" class="btn btn-outline-secondary btn-sm ms-auto">
                <i class="bi bi-arrow-left me-1"></i> Laporan
            </a>
        </div>
    </div>

    {{-- Filter Tanggal --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-4">
                <form method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-sm-auto">
                            <label class="form-label small fw-medium text-muted mb-1">Dari</label>
                            <input type="date" name="dari" value="{{ $dari }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-sm-auto">
                            <label class="form-label small fw-medium text-muted mb-1">Sampai</label>
                            <input type="date" name="sampai" value="{{ $sampai }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                        </div>
                        <div class="col-auto ms-auto align-self-end">
                            <span class="text-muted small">{{ $data['periode'] }}</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #10b981;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Pemasukan</div>
                <div class="fw-bold fs-6 text-success">Rp {{ number_format($data['total_pemasukan'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #ef4444;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Pengeluaran</div>
                <div class="fw-bold fs-6 text-danger">Rp {{ number_format($data['total_pengeluaran'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #3b82f6;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Cashflow</div>
                <div class="fw-bold fs-6 {{ $data['cashflow'] >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($data['cashflow'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;border-top:3px solid #8b5cf6;">
            <div class="card-body p-3">
                <div class="small text-muted mb-1">Jumlah Transaksi</div>
                <div class="fw-bold fs-6" style="color:#7c3aed;">{{ $data['summary']['total_transaksi'] }}</div>
            </div>
        </div>
    </div>

    {{-- Chart Tren 6 Bulan --}}
    @if(count($data['per_bulan']) > 0)
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">Tren 6 Bulan Terakhir</h6>
            </div>
            <div class="card-body p-4">
                <canvas id="chartTren" height="200"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- Pengeluaran per Kategori --}}
    @if(count($data['per_kategori']) > 0)
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">Pengeluaran per Kategori</h6>
            </div>
            <div class="card-body p-0">
                @foreach($data['per_kategori'] as $kat)
                <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom">
                    <div>
                        <div class="small fw-medium">{{ $kat['nama'] }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $kat['count'] }} transaksi · {{ $kat['persentase'] }}%</div>
                    </div>
                    <div class="small fw-bold text-danger">Rp {{ number_format($kat['total'], 0, ',', '.') }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Daftar Transaksi --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
                <h6 class="fw-semibold mb-0">Daftar Transaksi ({{ $data['summary']['total_transaksi'] }})</h6>
            </div>
            <div class="card-body p-0">
                @forelse($data['transaksi'] as $t)
                <a href="{{ route('transaksi.show', $t) }}"
                   class="d-flex align-items-center gap-3 px-4 py-3 border-bottom text-decoration-none">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                         style="width:36px;height:36px;background:{{ $t->jenis === 'pemasukan' ? 'rgba(16,185,129,.12)' : ($t->jenis === 'pengeluaran' ? 'rgba(239,68,68,.12)' : 'rgba(59,130,246,.12)') }}">
                        @if($t->jenis === 'pemasukan')
                            <i class="bi bi-arrow-up-circle text-success"></i>
                        @elseif($t->jenis === 'pengeluaran')
                            <i class="bi bi-arrow-down-circle text-danger"></i>
                        @else
                            <i class="bi bi-arrow-left-right text-primary"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-medium text-dark text-truncate">
                            {{ $t->keterangan ?: 'Tanpa keterangan' }}
                        </div>
                        <div class="text-muted d-flex align-items-center gap-1" style="font-size:.72rem;">
                            <span>{{ $t->tanggal->translatedFormat('d M Y') }}</span>
                            @if($t->kategori)<span>&bull;</span><span>{{ $t->kategori->nama }}</span>@endif
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="small fw-bold {{ $t->jenis === 'pemasukan' ? 'text-success' : ($t->jenis === 'pengeluaran' ? 'text-danger' : 'text-primary') }}">
                            {{ $t->jenis === 'pemasukan' ? '+' : ($t->jenis === 'pengeluaran' ? '-' : '') }}Rp {{ number_format($t->jumlah, 0, ',', '.') }}
                        </div>
                    </div>
                </a>
                @empty
                <div class="py-5 text-center">
                    <i class="bi bi-tags fs-1 d-block mb-2 text-muted opacity-25"></i>
                    <p class="text-muted small">Tidak ada transaksi dengan tag ini pada periode ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
@if(count($data['per_bulan']) > 0)
(function () {
    const labels = @json(collect($data['per_bulan'])->pluck('bulan'));
    const pemasukan = @json(collect($data['per_bulan'])->pluck('pemasukan'));
    const pengeluaran = @json(collect($data['per_bulan'])->pluck('pengeluaran'));

    new Chart(document.getElementById('chartTren'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: pemasukan,
                    backgroundColor: 'rgba(16,185,129,.7)',
                    borderRadius: 4,
                },
                {
                    label: 'Pengeluaran',
                    data: pengeluaran,
                    backgroundColor: 'rgba(239,68,68,.7)',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: {
                    ticks: {
                        callback: val => 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
                    }
                }
            }
        }
    });
})();
@endif
</script>
@endpush
```

- [ ] **Step 5.5 — Verifikasi di browser: buka `/laporan/tag/{id_tag}`**

Pastikan:
1. Header tag muncul dengan warna dot yang benar
2. Filter tanggal berfungsi, URL update ke `?dari=...&sampai=...`
3. Summary cards menampilkan angka yang benar
4. Chart tren 6 bulan tampil (atau kosong jika tidak ada data)
5. Daftar transaksi tampil, link ke detail transaksi berfungsi
6. Halaman "Lihat Laporan →" dari tags/index sekarang tidak 404

- [ ] **Step 5.6 — Commit**

```bash
git add routes/web.php app/Http/Controllers/LaporanController.php resources/views/laporan/tag.blade.php
git commit -m "feat(tag): add laporan per tag with chart, category breakdown, and transaction list"
```

---

## Task 6 — `DashboardService::getTopTags()` + Widget Dashboard

**Files:**
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/views/dashboard.blade.php`

- [ ] **Step 6.1 — Tambah `getTopTags()` ke DashboardService**

Buka `app/Services/DashboardService.php`. Tambah `use App\Models\Tag;` di imports. Tambah method berikut setelah `getSaldoPerSumber()` (atau method terakhir sebelum penutup class):

```php
/**
 * Return top N tag berdasarkan pengeluaran bulan ini.
 */
public function getTopTags(int $limit = 5): array
{
    $householdId = $this->householdId();
    $bulanIni    = Carbon::now();

    $tags = Tag::where('household_id', $householdId)->get();

    return $tags->map(function (Tag $tag) use ($bulanIni) {
        $pengeluaran = $tag->transaksi()
            ->where('jenis', 'pengeluaran')
            ->whereYear('tanggal', $bulanIni->year)
            ->whereMonth('tanggal', $bulanIni->month)
            ->sum('jumlah');

        return ['tag' => $tag, 'pengeluaran' => (float) $pengeluaran];
    })
    ->filter(fn ($item) => $item['pengeluaran'] > 0)
    ->sortByDesc('pengeluaran')
    ->take($limit)
    ->values()
    ->toArray();
}
```

- [ ] **Step 6.2 — Tambah widget `top_tags` ke DashboardController::WIDGETS**

Buka `app/Http/Controllers/DashboardController.php`. Di array `WIDGETS` (sekitar baris 23-35), tambah entry baru:

```php
'top_tags' => [
    'label'          => 'Tag Bulan Ini',
    'icon'           => 'bi-tags',
    'desc'           => 'Tag dengan pengeluaran terbesar bulan ini',
    'default_width'  => 'small',
    'default_height' => 'auto',
],
```

- [ ] **Step 6.3 — Tambah `top_tags` ke DEFAULT_LAYOUT**

Di array `DEFAULT_LAYOUT` (sekitar baris 37-48), tambah entry setelah `card_transaksi`:

```php
['id' => 'top_tags', 'visible' => true, 'width' => 'small', 'height' => 'auto'],
```

- [ ] **Step 6.4 — Pass `$topTags` dari DashboardController::index()**

Di method `index()` (sekitar baris 62), tambah:

```php
$topTags = $this->dashboardService->getTopTags(5);
```

Dan tambah `'topTags'` ke array `compact(...)`:

```php
return view('dashboard', compact(
    'summary',
    'pengeluaranPerKategori',
    'saldoPerSumber',
    'gamificationSummary',
    'gamificationInsights',
    'topTags',          // <-- tambah ini
    'widgetLayout',
    'widgetDefs',
    'widthOptions',
    'defaultLayout'
));
```

- [ ] **Step 6.5 — Tambah render widget `top_tags` di dashboard.blade.php**

Buka `resources/views/dashboard.blade.php`. Cari blok `@case('transaksi_terbaru')` dalam switch/case widget rendering. Tambah case baru `top_tags` setelah `transaksi_terbaru` (atau setelah `quick_actions`):

```blade
@case('top_tags')
    <div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
        <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-tags me-2 text-primary"></i>Tag Bulan Ini
                </h6>
                <a href="{{ route('tags.index') }}" class="small text-primary text-decoration-none">Kelola →</a>
            </div>
        </div>
        <div class="card-body p-0">
            @forelse($topTags as $item)
                <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle flex-shrink-0"
                              style="width:10px;height:10px;background:{{ $item['tag']->warna }};display:inline-block;"></span>
                        <a href="{{ route('laporan.tag', $item['tag']) }}"
                           class="small fw-medium text-dark text-decoration-none">{{ $item['tag']->nama }}</a>
                    </div>
                    <span class="small text-danger fw-semibold">
                        Rp {{ number_format($item['pengeluaran'], 0, ',', '.') }}
                    </span>
                </div>
            @empty
                <div class="py-4 text-center">
                    <p class="text-muted small mb-0">Belum ada tag dipakai bulan ini.</p>
                    <a href="{{ route('tags.index') }}" class="small text-primary text-decoration-none">Buat tag →</a>
                </div>
            @endforelse
        </div>
    </div>
@break
```

- [ ] **Step 6.6 — Verifikasi di browser: buka `/dashboard`**

Pastikan:
1. Widget "Tag Bulan Ini" muncul di dashboard (jika ada transaksi bertagged bulan ini)
2. Nama tag bisa diklik → redirect ke `/laporan/tag/{id}` yang benar
3. "Kelola →" link ke `/tags`
4. Widget bisa di-hide/show via edit mode dashboard
5. Widget bisa di-drag dan dipindah posisinya

- [ ] **Step 6.7 — Commit**

```bash
git add app/Services/DashboardService.php app/Http/Controllers/DashboardController.php resources/views/dashboard.blade.php
git commit -m "feat(tag): add top tags widget to dashboard with link to per-tag report"
```

---

## Task 7 — Update laporan/index.blade.php

**Files:**
- Modify: `resources/views/laporan/index.blade.php`

- [ ] **Step 7.1 — Tambah card "Laporan per Tag" di quick links**

Buka `resources/views/laporan/index.blade.php`. Cari array `@foreach([...] as $item)` yang merender kartu laporan harian/mingguan/bulanan/tahunan (sekitar baris 12-18). Tambah item ke-5 dengan cara wrap dalam partial tag display:

Ganti:
```blade
@foreach([
    ['route' => 'laporan.harian',   'label' => __('laporan.daily'),   'icon' => 'bi-calendar-day',   'color' => '#3b82f6'],
    ['route' => 'laporan.mingguan',  'label' => __('laporan.weekly'), 'icon' => 'bi-calendar-week',  'color' => '#6366f1'],
    ['route' => 'laporan.bulanan',   'label' => __('laporan.monthly'), 'icon' => 'bi-bar-chart-line', 'color' => '#8b5cf6'],
    ['route' => 'laporan.tahunan',   'label' => __('laporan.yearly'),  'icon' => 'bi-pie-chart',      'color' => '#ec4899'],
] as $item)
    <div class="col-6 col-md-3">
        <a href="{{ route($item['route']) }}"
           class="card border-0 shadow-sm text-decoration-none h-100"
           style="border-radius:.75rem;transition:.15s;">
            <div class="card-body p-4 d-flex flex-column align-items-center gap-3 text-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle"
                     style="width:52px;height:52px;background:{{ $item['color'] }}20;">
                    <i class="bi {{ $item['icon'] }} fs-4" style="color:{{ $item['color'] }};"></i>
                </div>
                <span class="fw-semibold small text-dark">{{ $item['label'] }}</span>
            </div>
        </a>
    </div>
@endforeach
```

Dengan:
```blade
@foreach([
    ['route' => 'laporan.harian',   'label' => __('laporan.daily'),   'icon' => 'bi-calendar-day',   'color' => '#3b82f6'],
    ['route' => 'laporan.mingguan',  'label' => __('laporan.weekly'), 'icon' => 'bi-calendar-week',  'color' => '#6366f1'],
    ['route' => 'laporan.bulanan',   'label' => __('laporan.monthly'), 'icon' => 'bi-bar-chart-line', 'color' => '#8b5cf6'],
    ['route' => 'laporan.tahunan',   'label' => __('laporan.yearly'),  'icon' => 'bi-pie-chart',      'color' => '#ec4899'],
] as $item)
    <div class="col-6 col-md-3">
        <a href="{{ route($item['route']) }}"
           class="card border-0 shadow-sm text-decoration-none h-100"
           style="border-radius:.75rem;transition:.15s;">
            <div class="card-body p-4 d-flex flex-column align-items-center gap-3 text-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle"
                     style="width:52px;height:52px;background:{{ $item['color'] }}20;">
                    <i class="bi {{ $item['icon'] }} fs-4" style="color:{{ $item['color'] }};"></i>
                </div>
                <span class="fw-semibold small text-dark">{{ $item['label'] }}</span>
            </div>
        </a>
    </div>
@endforeach

{{-- Card Laporan per Tag --}}
<div class="col-6 col-md-3">
    <a href="{{ route('tags.index') }}"
       class="card border-0 shadow-sm text-decoration-none h-100"
       style="border-radius:.75rem;transition:.15s;">
        <div class="card-body p-4 d-flex flex-column align-items-center gap-3 text-center">
            <div class="d-flex align-items-center justify-content-center rounded-circle"
                 style="width:52px;height:52px;background:#f59e0b20;">
                <i class="bi bi-tags fs-4" style="color:#f59e0b;"></i>
            </div>
            <span class="fw-semibold small text-dark">Per Tag</span>
        </div>
    </a>
</div>
```

- [ ] **Step 7.2 — Verifikasi di browser: buka `/laporan`**

Pastikan card "Per Tag" muncul sebagai kartu ke-5, klik → redirect ke `/tags` (daftar tag dengan link "Lihat Laporan →" per tag).

- [ ] **Step 7.3 — Commit**

```bash
git add resources/views/laporan/index.blade.php
git commit -m "feat(tag): add per-tag report entry card to laporan index"
```

---

## Task 8 — Jalankan Full Test Suite + Update Context

- [ ] **Step 8.1 — Jalankan semua test**

```bash
php artisan test
```

Expected: semua PASS, tidak ada regression. Jika ada failure, fix dulu sebelum lanjut.

- [ ] **Step 8.2 — Update `tag-context.md`**

Ubah frontmatter:
```yaml
implementation_status: partial   → complete
phases_done: [0]                  → [0, 1, 2]
phases_pending: [1, 2, 3]         → [3]
```

Tambah section baru di akhir file:
```markdown
## 7. Implementasi Selesai (Phase 1 + 2)

**Tanggal:** 2026-06-02

### File yang Dibuat
- `resources/views/laporan/tag.blade.php` — halaman laporan per tag

### File yang Diubah
- `app/Services/TransaksiService.php` — tambah `getSummaryByTag()`
- `app/Services/LaporanService.php` — tambah `getByTag()`
- `app/Services/DashboardService.php` — tambah `getTopTags()`
- `app/Http/Controllers/TagController.php` — inject TransaksiService, pass summaryByTag
- `app/Http/Controllers/LaporanController.php` — tambah `byTag()`
- `app/Http/Controllers/DashboardController.php` — tambah widget top_tags, pass topTags
- `routes/web.php` — route `laporan.tag`
- `resources/views/tags/index.blade.php` — summary table + link laporan per tag
- `resources/views/transaksi/index.blade.php` — filter tag pills
- `resources/views/laporan/index.blade.php` — card Per Tag
- `resources/views/dashboard.blade.php` — widget top_tags

### Test yang Dibuat
- `tests/Unit/Services/TransaksiServiceTagTest.php`
- `tests/Unit/Services/LaporanServiceTagTest.php`
```

- [ ] **Step 8.3 — Update CLAUDE.md Section 8**

Ganti:
```
- **Tag Enhancement** — CRUD + attach/sync ke transaksi + filter backend sudah ada. UI filter di halaman transaksi belum ada, laporan/summary/dashboard per tag belum ada. Detail: lihat `tag-context.md`
```

Dengan:
```
- **Tag Enhancement** — Phase 1+2 selesai: UI filter tag di transaksi, summary per tag, laporan per tag (chart + kategori), widget Top Tags di dashboard. Phase 3 (tipe tag) belum. Detail: lihat `tag-context.md`
```

Dan pindahkan dari blok `🔄` ke blok `✅`:
```
- Tag Enhancement (Phase 1+2): filter UI, summary, laporan per tag, dashboard widget. Detail: lihat [[tag-context]]
```

- [ ] **Step 8.4 — Update context-index.md**

Ubah baris tag-context di tabel:
```
| [[tag-context]] | roadmap | active | transaksi | medium | partial — Phase 0 done, Phase 1-3 pending |
```
→
```
| [[tag-context]] | decision | active | transaksi | medium | ✅ Phase 1+2 complete (2026-06-02), Phase 3 pending |
```

- [ ] **Step 8.5 — Commit final**

```bash
git add tag-context.md CLAUDE.md context-index.md
git commit -m "docs: update context files — tag enhancement Phase 1+2 complete"
```

---

## Self-Review

### Spec Coverage Check

| Requirement dari tag-context.md | Task |
|---|---|
| UI filter tag di transaksi/index | Task 3 |
| `hasAny()` badge include tag | Task 3 Step 3.1 |
| `getSummaryByTag()` | Task 1 |
| Summary table di tags/index | Task 2 |
| Link "Lihat Laporan" per tag | Task 2 + Task 5 |
| `getByTag()` di LaporanService | Task 4 |
| Route + controller `byTag()` | Task 5 |
| View laporan/tag.blade.php | Task 5 |
| Chart tren per tag | Task 5 (dalam view) |
| Breakdown per kategori | Task 5 (dalam view) |
| `getTopTags()` di DashboardService | Task 6 |
| Widget dashboard top_tags | Task 6 |
| Card "Per Tag" di laporan/index | Task 7 |
| Update context setelah selesai | Task 8 |

### Dependency Order

```
Task 1 (service) → Task 2 (controller inject service)
Task 4 (service) → Task 5 (controller + view)
Task 5 (route laporan.tag) → Task 2 (link "Lihat Laporan" tidak 404)
Task 6 (service + widget) → independen
Task 7 → independen
Task 8 → setelah semua selesai
```

Urutan aman: 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8.
