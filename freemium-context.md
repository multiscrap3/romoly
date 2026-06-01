---
id: freemium-context
title: "freemium-context.md — Freemium System Design & Roadmap"
type: roadmap
status: active
scope: freemium
priority: medium
tags: [freemium, plan, subscription, payment, midtrans, middleware, quota, billing, pending]
version: "1.0.0"
updated: 2026-05-31
depends_on: [CLAUDE.md]
referenced_by: [CLAUDE.md]
superseded_by: null
implementation_status: partial
phases_done: []
phases_pending: [1, 2, 3, 4, 5, 6]
bypass_active: true
---
# Freemium System — Context & Design Decisions

Dokumen ini merangkum semua keputusan desain sistem freemium romoly.
Baca sebelum menyentuh `Plan`, `PlanLimitService`, payment, atau middleware apapun.
Lihat [[CLAUDE]] Bagian 8 untuk status implementasi saat ini.

> **Status:** Belum diimplementasikan. Fase saat ini masih `internalBypass = true`.
> Dokumen ini adalah panduan lengkap untuk implementasi penuh.

---

## 1. Kondisi Kode yang Sudah Ada (Baseline)

### Yang sudah tersedia dan bisa langsung dipakai:
- `App\Models\Plan` — kolom: `nama`, `slug`, `harga`, `max_anggota`, `max_transaksi`, `max_ocr`, `fitur` (JSON), `is_active`
- `App\Models\Household` — kolom: `plan_id`, `subscription_start`, `subscription_end`, `status`, method `isSubscriptionActive()`
- `App\Services\PlanLimitService` — method `canAddTransaksi()`, `canUseOCR()`, `canAddAnggota()`, `hasFeature()`, `getUsage()`, `getLimits()`; saat ini **bypass semua** via `$internalBypass = true`
- `App\Http\Middleware\CheckPlanLimitMiddleware` — sudah ada, match via `$feature` string; **belum dipasang di route manapun**
- `App\Models\PaymentHistory` + `App\Models\AiUsageLog` — tabel sudah ada
- `database/seeders/PlanSeeder.php` — 3 paket: Free (Rp 0), Basic (Rp 29.000), Premium (Rp 79.000)

### Bug yang harus diperbaiki sebelum enforcement:
1. **Kontradiksi Free plan:** `max_ocr = 10` tapi flag `ocr => false`. Pilih salah satu; keputusan: set `max_ocr = 0` dan flag `ocr => false` (Free tidak dapat OCR sama sekali)
2. **Tidak ada kuota angka untuk AI non-OCR:** `generate_insight`, `detect_anomaly`, `suggest_detail` hanya dijaga flag boolean, tidak ada counter numerik. Ini rawan abuse dan mahal
3. **`CheckPlanLimitMiddleware` belum terpasang** di route transaksi, ocr, import-bank, atau AI
4. **Schema `plans` terlalu sempit:** Belum ada kolom untuk limit rekening, anggaran, tabungan, recurring, history retensi, atau AI insight quota

---

## 2. Filosofi Batasan Freemium

### Prinsip utama: Jangan matikan kebiasaan mencatat

Romoly adalah aplikasi pembentuk kebiasaan. User harus merasa produktif dulu sebelum ditawarkan upgrade. Batasan yang terlalu pelit di pencatatan dasar akan membuat user kabur sebelum merasakan nilai produk.

**Batasi biaya nyata dan fitur "power", bukan pencatatan dasar.**

| Kategori | Strategi Batasan | Alasan |
|---|---|---|
| Pencatatan transaksi | Unlimited input, batasi **retensi** (bukan volume) | Jangan ganggu kebiasaan harian |
| Fitur AI (OCR, Insight, Anomali) | Hard quota per bulan | Biaya nyata keluar ke Gemini/OpenRouter |
| Fitur keluarga (anggota) | Batasi jumlah — ini upsell terkuat | Solo gratis, keluarga bayar |
| Fitur otomatis (Recurring, Import bank) | On/off per tier | Power feature, bukan kebutuhan dasar |
| Laporan historis | Batasi window waktu di Free | Mendorong upgrade tanpa matikan fungsi dasar |

### Unit pembatasan: Household, bukan User

Semua limit mengacu pada **household**, bukan per-user. Ini sesuai dengan desain `PlanLimitService` yang existing dan selaras dengan model bisnis (satu keluarga = satu langganan).

---

## 3. Desain Tier Plan (Definitif)

### Tiga tier: Free · Basic · Premium

Harga dalam Rupiah, billing **bulanan**. Billing tahunan mendapat diskon setara 2 bulan gratis.

```
Free       → Rp 0
Basic      → Rp 29.000/bulan  |  Rp 290.000/tahun
Premium    → Rp 79.000/bulan  |  Rp 790.000/tahun
```

### Tabel limit numerik

| Limit | Free | Basic | Premium |
|---|---|---|---|
| `max_anggota` | 2 | 5 | -1 (∞) |
| `max_transaksi` | -1 (∞) | -1 (∞) | -1 (∞) |
| `max_ocr` (per bulan) | 0 | 30 | -1 (∞) tunduk global |
| `max_rekening` | 3 | 10 | -1 (∞) |
| `max_anggaran` | 3 | -1 (∞) | -1 (∞) |
| `max_tabungan` | 1 | 5 | -1 (∞) |
| `max_recurring` | 0 | 10 | -1 (∞) |
| `max_ai_insight` (per bulan) | 0 | 5 | 30 |
| `retensi_bulan` | 6 | 24 | -1 (∞) |
| `max_import_bank` (per bulan) | 0 | 0 | 20 |

> **Catatan retensi:** Bukan menghapus data lama — data tetap tersimpan di DB. Hanya dibatasi **tampilan/akses** lewat query filter. Jika user upgrade, semua history kembali terlihat.

> **Catatan `max_transaksi`:** Sengaja diset unlimited di semua tier — prioritas membangun kebiasaan. Kalau mau ditambah batasan, gunakan `retensi_bulan`, bukan cap input.

### Tabel fitur boolean (JSON `fitur`)

| Flag | Free | Basic | Premium | Keterangan |
|---|---|---|---|---|
| `transaksi_dasar` | ✅ | ✅ | ✅ | |
| `kategori` | ✅ | ✅ | ✅ | |
| `anggaran` | ✅ | ✅ | ✅ | Dibatasi `max_anggaran` |
| `laporan_dasar` | ✅ | ✅ | ✅ | Harian, mingguan, bulanan |
| `laporan_advanced` | ❌ | ❌ | ✅ | Tahunan, perbandingan |
| `multi_sumber` | ✅ | ✅ | ✅ | Dibatasi `max_rekening` |
| `recurring` | ❌ | ✅ | ✅ | Dibatasi `max_recurring` |
| `tabungan` | ✅ | ✅ | ✅ | Free hanya 1, dibatasi `max_tabungan` |
| `hutang_piutang` | ❌ | ✅ | ✅ | |
| `import_bank` | ❌ | ❌ | ✅ | Dibatasi `max_import_bank` |
| `ocr` | ❌ | ✅ | ✅ | Dibatasi `max_ocr` |
| `ai_insights` | ❌ | ✅ | ✅ | Dibatasi `max_ai_insight` |
| `ai_anomaly` | ❌ | ❌ | ✅ | Deteksi anomali + scan periode |
| `export_excel` | ❌ | ✅ | ✅ | |
| `export_pdf` | ✅ | ✅ | ✅ | Free: with watermark |
| `export_pdf_watermark` | ✅ | ❌ | ❌ | Free dapat watermark "Romoly Free" |
| `backup` | ❌ | ✅ | ✅ | |
| `gamifikasi` | ✅ | ✅ | ✅ | Selalu aktif — engagement hook |
| `priority_support` | ❌ | ❌ | ✅ | |

---

## 4. Perubahan Database yang Dibutuhkan

### 4a. Migration: tambah kolom di tabel `plans`

File: `database/migrations/{timestamp}_add_quota_columns_to_plans_table.php`

```php
Schema::table('plans', function (Blueprint $table) {
    $table->integer('max_rekening')->default(-1)->after('max_ocr')->comment('-1 = unlimited');
    $table->integer('max_anggaran')->default(-1)->after('max_rekening')->comment('-1 = unlimited');
    $table->integer('max_tabungan')->default(-1)->after('max_anggaran')->comment('-1 = unlimited');
    $table->integer('max_recurring')->default(-1)->after('max_tabungan')->comment('-1 = unlimited');
    $table->integer('max_ai_insight')->default(-1)->after('max_recurring')->comment('-1 = unlimited, per bulan');
    $table->integer('max_import_bank')->default(-1)->after('max_ai_insight')->comment('-1 = unlimited, per bulan');
    $table->integer('retensi_bulan')->default(-1)->after('max_import_bank')->comment('-1 = unlimited');
    $table->string('billing_period')->default('monthly')->after('harga')->comment('monthly | yearly | lifetime');
});
```

### 4b. Update `PlanSeeder`

Harus di-update penuh setelah migration di atas dijalankan. Lihat bagian Implementasi untuk nilai lengkapnya.

### 4c. Tidak perlu tabel baru untuk tracking kuota

Gunakan query COUNT ke tabel yang ada:
- `ocr_history` (filter `household_id` + `created_at` bulan ini) → kuota OCR
- `ai_usage_logs` (filter `action = 'generate_insight'` + bulan ini) → kuota AI insight
- `import_bank` (filter bulan ini) → kuota import bank
- `sumber_transaksi` (count aktif) → kuota rekening
- `anggaran` (count aktif) → kuota anggaran
- `tabungan` (count aktif) → kuota tabungan
- `recurring_transaksi` (count aktif) → kuota recurring

`AiUsageLog` sudah mencatat `action`, `user_id`, `household_id`, `created_at` — cukup untuk metering.

---

## 5. Perluasan `PlanLimitService`

### Method yang harus ditambahkan

```php
// Check limit rekening (sumber_transaksi aktif)
public function canAddRekening(int $householdId): bool

// Check limit anggaran aktif
public function canAddAnggaran(int $householdId): bool

// Check limit tabungan aktif
public function canAddTabungan(int $householdId): bool

// Check limit recurring aktif
public function canAddRecurring(int $householdId): bool

// Check limit AI insight per bulan
public function canGenerateInsight(int $householdId): bool

// Check limit import bank per bulan
public function canImportBank(int $householdId): bool

// Ambil query transaksi dengan filter retensi (untuk LaporanService dan TransaksiController)
public function applyRetensiFilter(Builder $query, int $householdId): Builder

// Info UI: berapa persen kuota terpakai (untuk badge/progress bar di UI)
public function getQuotaStatus(int $householdId): array
```

### Cara mematikan bypass

Di `PlanLimitService`, ubah:
```php
// Sebelum:
private bool $internalBypass = true;

// Sesudah (enforcement aktif):
private bool $internalBypass = false;
```

**JANGAN matikan bypass sebelum:**
1. Migration kolom baru sudah dijalankan
2. PlanSeeder sudah diperbarui dan di-reseed
3. Semua household yang ada sudah punya `plan_id` valid (tidak null)
4. Route middleware sudah terpasang dengan benar
5. Halaman upgrade/paywall sudah ada

---

## 6. Middleware Enforcement — Route Mapping

### Alias middleware yang sudah ada
Alias sudah terdaftar di `bootstrap/app.php` (Laravel 13 tidak pakai Kernel.php):
```php
// bootstrap/app.php — alias aktual:
'check.plan' => \App\Http\Middleware\CheckPlanLimitMiddleware::class,
```

### Route yang perlu middleware

```php
// transaksi — tidak perlu limit input, tapi batasi retensi via service
Route::resource('transaksi', TransaksiController::class);

// OCR — butuh limit
Route::post('/api/ocr/extract', [OCRController::class, 'extract'])
    ->middleware('check.plan:ocr');

// AI Insights — butuh limit
Route::post('/api/ai/insights/generate', [AIController::class, 'generateInsights'])
    ->middleware('check.plan:ai_insights');

// Anomaly detect — butuh limit (Premium only via flag)
Route::post('/api/ai/anomaly-detect', [AIController::class, 'detectAnomaly'])
    ->middleware('check.plan:ai_anomaly');

// Import bank — butuh limit (Premium only via flag)
Route::get('/import-bank/form', [ImportBankController::class, 'webForm'])
    ->middleware('check.plan:import_bank');
Route::post('/api/import-bank', [ImportBankController::class, 'store'])
    ->middleware('check.plan:import_bank');

// Recurring — butuh limit saat create
Route::post('/recurring', [RecurringTransaksiController::class, 'store'])
    ->middleware('check.plan:recurring');

// Tabungan — butuh limit saat create
Route::post('/tabungan', [TabunganController::class, 'store'])
    ->middleware('check.plan:tabungan');

// Household members — sudah tercakup di PlanLimitService::canAddAnggota
Route::post('/household/invite', [HouseholdController::class, 'invite'])
    ->middleware('check.plan:anggota');

// Sumber Transaksi — butuh limit
Route::post('/sumber-transaksi', [SumberTransaksiController::class, 'store'])
    ->middleware('check.plan:rekening');

// Anggaran — butuh limit
Route::post('/anggaran', [AnggaranController::class, 'store'])
    ->middleware('check.plan:anggaran');

// Laporan advanced — batasi per fitur
Route::get('/laporan/tahunan', [LaporanController::class, 'tahunan'])
    ->middleware('check.plan:laporan_advanced');
Route::get('/laporan/perbandingan', [LaporanController::class, 'perbandingan'])
    ->middleware('check.plan:laporan_advanced');

// Export
Route::post('/transaksi/export', [TransaksiController::class, 'export'])
    ->middleware('check.plan:export_excel');
Route::post('/laporan/export', [LaporanController::class, 'export'])
    ->middleware('check.plan:export_excel');
```

### Perluasan `CheckPlanLimitMiddleware`

Switch statement perlu diperluas untuk feature keys baru:
```php
$allowed = match ($feature) {
    'transaksi'             => $this->planLimitService->canAddTransaksi($householdId),
    'ocr'                   => $this->planLimitService->canUseOCR($householdId),
    'anggota', 'household_members'
                            => $this->planLimitService->canAddAnggota($householdId),
    'rekening'              => $this->planLimitService->canAddRekening($householdId),
    'anggaran'              => $this->planLimitService->canAddAnggaran($householdId),
    'tabungan'              => $this->planLimitService->canAddTabungan($householdId),
    'recurring'             => $this->planLimitService->canAddRecurring($householdId),
    'ai_insights'           => $this->planLimitService->canGenerateInsight($householdId),
    'ai_anomaly'            => $this->planLimitService->hasFeature($householdId, 'ai_anomaly'),
    'import_bank'           => $this->planLimitService->canImportBank($householdId),
    'laporan_advanced'      => $this->planLimitService->hasFeature($householdId, 'laporan_advanced'),
    'export_excel'          => $this->planLimitService->hasFeature($householdId, 'export_excel'),
    default                 => $this->planLimitService->hasFeature($householdId, $feature),
};
```

---

## 7. Paywall UX — Pola yang Dipakai

### Prinsip paywall: Jangan kejutkan, beri konteks

Saat user kena limit, jangan cuma "error 403". Tunjukkan:
1. Apa yang sedang dibatasi dan kenapa
2. Apa yang mereka dapat kalau upgrade
3. CTA yang jelas

### Respons middleware saat limit tercapai

Untuk request HTML (web):
```php
return redirect()->back()
    ->with('upgrade_required', [
        'feature'    => $feature,
        'message'    => $this->getMessage($feature),
        'plan_needed' => $this->getPlanNeeded($feature),
    ]);
```

Untuk request AJAX (JSON):
```php
return response()->json([
    'success'      => false,
    'upgrade'      => true,
    'feature'      => $feature,
    'message'      => $this->getMessage($feature),
    'plan_needed'  => $this->getPlanNeeded($feature),
], 402); // Payment Required
```

### Komponen Blade yang perlu dibuat

**`resources/views/components/paywall-banner.blade.php`**
Banner inline yang muncul di atas halaman saat `session('upgrade_required')` ada.
```blade
@if(session('upgrade_required'))
<div class="paywall-banner">
    <span>{{ session('upgrade_required.message') }}</span>
    <a href="{{ route('billing.index') }}">Upgrade ke {{ session('upgrade_required.plan_needed') }}</a>
</div>
@endif
```

**`resources/views/components/feature-lock.blade.php`**
Bisa di-embed di dalam halaman untuk elemen yang terkunci (tombol disabled + tooltip).
```blade
<x-feature-lock feature="import_bank" plan="Premium">
    <x-slot:trigger>
        <button disabled>Import Bank</button>
    </x-slot:trigger>
    <x-slot:tooltip>
        Fitur ini tersedia di paket Premium
    </x-slot:tooltip>
</x-feature-lock>
```

**`resources/views/billing/index.blade.php`**
Halaman pricing — tampilkan 3 tier dengan perbandingan fitur.

---

## 8. Siklus Langganan (Subscription Lifecycle)

### Status Household

| `status` | Kondisi | Behavior |
|---|---|---|
| `active` | `subscription_end` null atau future | Semua fitur plan berjalan normal |
| `active` | `subscription_end` sudah lewat | Sistem otomatis downgrade ke Free setelah grace 7 hari |
| `expired` | Subscription sudah berakhir + grace habis | Downgrade ke Free |
| `suspended` | Diblokir admin | Akses sangat dibatasi |

> **⚠️ Dead Schema:** Status `'trialing'` di atas **BELUM ADA** di migration Household.
> Enum aktual di database: `['active', 'suspended', 'expired']`.
> Sebelum implementasi trial logic, jalankan migration:
> ```php
> $table->enum('status', ['active', 'trialing', 'suspended', 'expired'])->default('active')->change();
> ```

### Trial Logic *(PENDING — belum diimplementasikan)*

- User baru register → household otomatis `status = 'trialing'`, `plan_id = basic_plan_id`, `subscription_end = now + 14 hari`
- Setelah 14 hari → cron job cek, jika tidak berlangganan → turunkan ke Free
- Trial hanya sekali per email (cek via `payment_history` apakah pernah ada record)

### Grace Period setelah Expired

- 7 hari grace setelah `subscription_end` lewat
- Selama grace: semua fitur plan sebelumnya masih jalan, tapi muncul banner "Langganan Anda berakhir X hari lagi"
- Setelah grace habis: downgrade ke Free, data tidak hilang

### Cron Job yang Dibutuhkan

```php
// Tambah ke CronController atau Console/Commands

// Cek dan proses subscription yang expired
// Jadwal: setiap hari jam 00:30
php artisan subscription:process-expired

// Kirim notifikasi 7 hari sebelum expired
// Jadwal: setiap hari jam 09:00
php artisan subscription:send-renewal-reminders
```

---

## 9. Manajemen Biaya AI

### Problem: AI adalah variable cost, bukan fixed

Setiap panggilan Gemini lewat OpenRouter keluar uang. Free user yang abuse OCR atau insight bisa membuat kerugian.

### Strategi multi-layer

**Layer 1 — Per-household quota (per bulan, di PlanLimitService)**
- Free: OCR = 0, Insight = 0
- Basic: OCR = 30, Insight = 5
- Premium: OCR = tunduk global harian

**Layer 2 — Global daily limit (sudah ada di GeminiService)**
- `ocr_daily_limit` setting di database (default 500)
- `gemini_ocr_used_today` counter di settings
- `checkDailyLimit()` method sudah ada

**Layer 3 — Cache hasil AI (sudah ada di GeminiService)**
- `ocrAndExtract` → cache 24 jam berdasarkan MD5 gambar
- `generateInsight` → cache 24 jam berdasarkan MD5 data
- `suggestDetail` → cache 24 jam berdasarkan MD5 input
- Artinya: struk yang sama tidak tagih API dua kali

**Layer 4 — `suggest_detail` dibatasi per transaksi, bukan per hit**
`suggest_detail` dipanggil saat user mengetik nama toko. Perlu debounce di frontend (300ms minimum), dan backend hanya hit Gemini kalau cache miss.

### Tabel `ai_usage_logs` sebagai sumber kebenaran

Untuk cek kuota `max_ai_insight` per bulan:
```php
AiUsageLog::where('household_id', $householdId)
    ->where('action', 'generate_insight')
    ->where('success', true)
    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
    ->count();
```

---

## 10. Payment Gateway

### Pilihan: Midtrans (Snap) sebagai default

Alasan: paling mature di Indonesia, support QRIS, VA semua bank, kartu kredit, GoPay, OVO, ShopeePay.

### Flow pembayaran

```
User klik "Upgrade" → /billing/checkout/{plan_slug}
→ Controller buat Midtrans Snap token
→ Redirect ke Snap popup
→ Midtrans callback (webhook) ke /billing/webhook
→ Update household.plan_id + subscription_start + subscription_end
→ Insert ke payment_history
→ Redirect ke /billing/success
```

### File yang perlu dibuat

```
app/Services/PaymentService.php         # Midtrans integration
app/Http/Controllers/BillingController.php
routes/web.php (tambah billing routes)
resources/views/billing/
    index.blade.php     # Pricing page
    checkout.blade.php  # Konfirmasi sebelum bayar
    success.blade.php   # Sukses
    history.blade.php   # Riwayat pembayaran
```

### Route billing yang dibutuhkan

```php
Route::prefix('billing')->name('billing.')->middleware('auth')->group(function () {
    Route::get('/', [BillingController::class, 'index'])->name('index');
    Route::get('/checkout/{plan}', [BillingController::class, 'checkout'])->name('checkout');
    Route::post('/pay/{plan}', [BillingController::class, 'pay'])->name('pay');
    Route::get('/success', [BillingController::class, 'success'])->name('success');
    Route::get('/history', [BillingController::class, 'history'])->name('history');
    Route::post('/cancel', [BillingController::class, 'cancel'])->name('cancel');
});

// Webhook (tanpa auth, pakai signature validation)
Route::post('/billing/webhook', [BillingController::class, 'webhook'])
    ->name('billing.webhook')
    ->withoutMiddleware(['auth', 'web']);
```

### Schema `payment_history` yang sudah ada

Perlu dicek schema-nya — pastikan ada: `household_id`, `plan_id`, `amount`, `gateway`, `gateway_ref`, `status`, `billing_period`, `period_start`, `period_end`, `paid_at`.

---

## 11. Superadmin Tools

### Fitur yang dibutuhkan di panel superadmin

Panel `/superadmin` sudah ada. Tambahkan kemampuan:

1. **Override plan household** — ganti plan sebuah household tanpa payment (untuk testing, koreksi manual)
2. **Extend trial** — perpanjang trial user tertentu
3. **Lihat quota usage** — sudah setengah ada via `PlanLimitService::getUsage()`, tinggal expose ke view
4. **Blacklist / refund** — tandai payment sebagai refunded, downgrade plan

Tambah ke `SuperadminController`:
```php
Route::put('/households/{household}/plan', [SuperadminController::class, 'overridePlan'])
    ->name('household.override-plan');
Route::put('/households/{household}/trial/extend', [SuperadminController::class, 'extendTrial'])
    ->name('household.extend-trial');
```

---

## 12. Fase Implementasi (Urutan yang Direkomendasikan)

### Phase 1 — Foundation (prerequisite, tidak boleh dilewati)
- [ ] Buat migration: tambah kolom quota baru ke tabel `plans`
- [ ] **Tambah `'trialing'` ke enum `status` di tabel `households`** ← dead schema, perlu migration
- [ ] Update `PlanSeeder` dengan nilai yang benar (sesuai tabel di atas)
- [ ] Jalankan `migrate` + reseed plans
- [ ] Pastikan semua household punya `plan_id` valid (buat seeder default assign Free ke yang null)
- [ ] Fix kontradiksi Free plan (`max_ocr` vs flag `ocr`)

### Phase 2 — Enforcement Logic
- [ ] Tambah method baru ke `PlanLimitService` (`canAddRekening`, `canAddAnggaran`, dll)
- [ ] Perluasan `CheckPlanLimitMiddleware` untuk feature keys baru
- [ ] Alias `'check.plan'` sudah terdaftar di `bootstrap/app.php` ✅ — tidak perlu daftar lagi
- [ ] Pasang middleware ke route-route yang teridentifikasi (Bagian 6)
- [ ] Tambah `applyRetensiFilter()` ke service dan pasang di query laporan/transaksi Free
- [ ] **Set `internalBypass = false`** — enforcement aktif
- [ ] Testing: coba setiap limit dengan user di tiap tier

### Phase 3 — Paywall UI
- [ ] Buat `resources/views/components/paywall-banner.blade.php`
- [ ] Buat `resources/views/components/feature-lock.blade.php`
- [ ] Update semua view untuk tampilkan elemen terkunci dengan tooltip upgrade
- [ ] Update middleware response JSON untuk AJAX calls (HTTP 402)
- [ ] Buat halaman pricing `/billing` sederhana (tanpa payment dulu)

### Phase 4 — Subscription Lifecycle
- [ ] Buat `app/Console/Commands/ProcessExpiredSubscriptionsCommand.php`
- [ ] Buat `app/Console/Commands/SendRenewalRemindersCommand.php`
- [ ] Register ke `Console/Kernel.php` atau `routes/console.php`
- [ ] Tambah cron endpoint ke `CronController` jika dipakai via HTTP cron
- [ ] Logic trial untuk user baru di `RegisterController` atau observer
- [ ] Banner grace period di layout

### Phase 5 — Payment Gateway
- [ ] Pasang Midtrans SDK: `composer require midtrans/midtrans-php`
- [ ] Config: tambah `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` ke `.env`
- [ ] Buat `PaymentService`
- [ ] Buat `BillingController` + semua routes
- [ ] Buat views billing (pricing, checkout, success, history)
- [ ] Implement webhook handler + signature validation
- [ ] Update `PaymentHistory` saat payment sukses/gagal

### Phase 6 — Polish & Monitoring
- [ ] Kuota usage progress bar di settings page
- [ ] Email notifikasi: welcome (trial), reminder 7 hari sebelum expired, expired, payment sukses
- [ ] Dashboard superadmin: usage per plan, revenue, churn rate
- [ ] A/B test harga (optional)

---

## 13. Invariant & Aturan yang Tidak Boleh Dilanggar

1. **Data tidak pernah dihapus saat downgrade.** Retensi hanya memfilter tampilan. Jika upgrade kembali, semua history muncul lagi.
2. **Gamifikasi selalu aktif di semua tier.** Ini adalah engagement tool, bukan premium feature.
3. **Household owner selalu bisa ganti plan.** Admin role di Spatie permissions sudah ada.
4. **`AiUsageLog` harus selalu ditulis** meski request gagal (status code, success flag). Ini satu-satunya sumber data billing AI.
5. **Middleware tidak boleh blokir GET /billing.** User yang sudah expired harus bisa mengakses halaman upgrade.
6. **`suggest_detail` (autocomplete form transaksi) tidak boleh dibatasi per-call.** Itu UX negatif. Batasi via debounce frontend saja.
7. **Harga di database dalam Rupiah (integer, bukan desimal sentimen).** Kolom `harga decimal(15,2)` sudah ada — gunakan nilai seperti `29000.00`.
8. **Webhook Midtrans harus divalidasi signature-nya** sebelum mengubah plan apapun. Jangan percaya payload mentah.

---

## 14. File Referensi

| File | Keterangan |
|---|---|
| `app/Models/Plan.php` | Model plan, `hasFeature()` helper |
| `app/Models/Household.php` | Relasi ke Plan, `isSubscriptionActive()` |
| `app/Services/PlanLimitService.php` | Semua logic limit — titik implementasi utama |
| `app/Http/Middleware/CheckPlanLimitMiddleware.php` | Gate di route level |
| `app/Services/GeminiService.php` | `checkDailyLimit()`, `logUsage()` — AI cost control |
| `app/Models/AiUsageLog.php` | Sumber kebenaran kuota AI per bulan |
| `database/seeders/PlanSeeder.php` | Nilai plan — harus diupdate di Phase 1 |
| `database/migrations/2024_01_01_000001_create_plans_table.php` | Schema awal plans |
| `freemium-context.md` | File ini |
