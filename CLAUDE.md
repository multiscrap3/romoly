---
id: claude-master
title: "CLAUDE.md — Master Context Romoly"
type: master-ref
status: active
scope: global
priority: critical
tags: [master, architecture, models, controllers, services, middleware, status]
version: "2.2.0"
updated: 2026-05-31
git_hash: 960a3f09
depends_on: []
referenced_by: [CONTEXT.md, gamifikasi-context.md, freemium-context.md, UIUXgame-context.md, context-index.md]
superseded_by: null
---
# CLAUDE.md — Master Context Romoly (FinanKu)
# Dibaca otomatis oleh Claude Code di setiap sesi baru
# SELALU baca file ini sebelum menulis kode apapun

> **SESSION START:** Bandingkan `git_hash` di frontmatter dengan `git log --oneline -1`.
> Jika berbeda → ada perubahan sejak CLAUDE.md terakhir di-update → flag ke user sebelum mulai kerja.

---

## 1. IDENTITAS PROYEK

- **Nama Aplikasi:** Romoly (FinanKu)
- **Deskripsi:** Aplikasi web manajemen keuangan keluarga berbasis AI, multi-tenant, SaaS-ready
- **Stack:** Laravel 13.7 · PHP 8.3 · MySQL 8.0 · Blade + Bootstrap 5 (template "Dompet") · jQuery · metisMenu · Chart.js — semua via CDN/aset lokal, tanpa build process
- **Hosting Target:** Shared hosting RumahWeb (tanpa SSH, tanpa Queue, tanpa Redis, tanpa cron native)
- **Deploy:** Manual via cPanel — `vendor/` di-commit, aset CSS/JS via CDN + lokal (tanpa build step)

---

## 2. ARSITEKTUR

### Multi-Tenancy
- Setiap **household** = 1 tenant terisolasi penuh
- Semua model keuangan pakai Trait `BelongsToHousehold` → global scope `household_id`
- User TIDAK BISA akses data household lain dalam kondisi apapun
- Query tanpa household scope HANYA di SuperadminController

### Layer Akses
```
PUBLIK     : /login, /register, /privacy/*
AUTH       : /onboarding → /dashboard
APLIKASI   : Semua fitur household (middleware: auth)
SUPERADMIN : /superadmin/* (middleware: auth + superadmin.global)
API/AJAX   : /api/* (auth, dalam web.php — BUKAN routes/api.php)
CRON       : /cron/* (middleware: cron.secret)
```

### RBAC (Spatie Permissions)
- Roles: `admin`, `member`, `viewer`
- `admin` = pemilik household, full access
- `member` = bisa input transaksi
- `viewer` = read-only

### Middleware yang Terdaftar di bootstrap/app.php

| Alias | Class | Status |
|---|---|---|
| *(global web)* | `LocaleMiddleware` | ✅ Aktif — auto-apply semua request |
| `auth` | Laravel built-in | ✅ Aktif — dipakai di routes |
| `cron.secret` | `CronSecretMiddleware` | ✅ Aktif — dipakai di /cron/* |
| `superadmin.global` | `SuperadminGlobalMiddleware` | ✅ Aktif — dipakai di /superadmin/* |
| `check.plan` | `CheckPlanLimitMiddleware` | ⏳ Terdaftar, belum dipasang ke routes (bypass aktif) |
| `household` | `HouseholdMiddleware` | ⚠️ Terdaftar, belum dipasang ke routes — guards redirect ke onboarding |
| `role` | `RoleMiddleware` | ⚠️ Terdaftar, belum dipasang — digantikan `$user->hasRole()` di controller |
| `log.activity` | `LogActivityMiddleware` | ⚠️ Terdaftar, belum dipasang — digantikan `HasAuditLog` trait di model |

> `HouseholdMiddleware` dan `RoleMiddleware` sudah berfungsi penuh — tinggal dipasang ke route group jika diperlukan.

---

## 3. MODEL AKTUAL (31 Model)

> PENTING: Nama di bawah ini adalah nama AKTUAL di codebase. Jangan gunakan nama lama dari PROMPT.md.

### Core Finance
| Model | Catatan |
|---|---|
| `User` | HasRoles (Spatie), household_id, avatar, consent_given_at |
| `Household` | plan_id, subscription_start/end, status, isSubscriptionActive() |
| `Plan` | max_anggota, max_transaksi, max_ocr, fitur (JSON), hasFeature() |
| `Transaksi` | jenis (pemasukan/pengeluaran/transfer), jumlah, sumber_transaksi_id |
| `Kategori` | parent_id (hierarki), jenis (pemasukan/pengeluaran) |
| `SumberTransaksi` | (**BUKAN** Rekening) — nama bank/rekening, saldo_saat_ini |
| `Anggaran` | (**BUKAN** Budget) — terpakai, notifikasi_aktif, threshold |
| `Tabungan` | (**BUKAN** SavingsGoal) — target_jumlah, terkumpul, status |
| `TabunganTransaksi` | deposit/tarik history |
| `HutangPiutang` | jenis (hutang/piutang), cicilan support |
| `HutangPiutangPembayaran` | bayar cicilan |
| `RecurringTransaksi` | (**BUKAN** RecurringTransaction) — frekuensi, next_run |
| `Tag` | pivot ke transaksi |

### Logging & Audit
| Model | Catatan |
|---|---|
| `AuditLog` | (**BUKAN** ActivityLog) — morphTo model, old/new values |
| `Notifikasi` | (**BUKAN** Notification) — jenis, is_read |
| `OcrHistory` | history scan struk |
| `AiUsageLog` | log setiap panggilan AI — action, tokens, success |
| `ConsentLog` | PDP consent tracking |
| `SecurityLog` | security events |
| `Laporan` | generated report files |
| `ImportBank` | status import mutasi bank |

### SaaS & Household
| Model | Catatan |
|---|---|
| `PaymentHistory` | riwayat pembayaran subscription |
| `HouseholdInvitation` | undangan anggota via token |
| `Setting` | key-value per household |

### Gamification (semua ✅ selesai)
| Model | Catatan |
|---|---|
| `UserGamification` | total_xp, level, momentum_score, grace_days_used |
| `Achievement` | slug, tier_type, rarity (bronze/silver/gold/platinum), is_hidden, is_major |
| `UserAchievement` | earned_at |
| `Challenge` | type, difficulty, expires_at di user_challenges |
| `UserChallenge` | progress, completed_at |
| `XpLog` | source, xp_amount |
| `WeeklyReview` | week_start/end, data JSON, viewed_at |

### Model yang TIDAK ADA (jangan dipakai):
- ~~`Rekening`~~ → `SumberTransaksi`
- ~~`Budget`~~ → `Anggaran`
- ~~`SavingsGoal`~~ → `Tabungan`
- ~~`HouseholdMember`~~ → User + Spatie roles
- ~~`Subscription`~~ → logika ada di `Household`
- ~~`TransaksiItem`~~ → field JSON `ocr_items` di `Transaksi`
- ~~`Toko`~~ / ~~`TokoPola`~~ → hanya ada `TokoPolaService`
- ~~`ActivityLog`~~ → `AuditLog`
- ~~`Notification`~~ → `Notifikasi`

---

## 4. CONTROLLER AKTUAL (28 Controller)

### Resource Controllers
| Controller | Route | Catatan |
|---|---|---|
| `TransaksiController` | `/transaksi` | +restore, summary, export, suggest |
| `AnggaranController` | `/anggaran` | (**BUKAN** BudgetController) |
| `TabunganController` | `/tabungan` | (**BUKAN** SavingsGoalController) setor/tarik |
| `HutangPiutangController` | `/hutang-piutang` | bayar, summary |
| `RecurringTransaksiController` | `/recurring` | toggle active |
| `KategoriController` | `/kategori` | checkDelete, search |
| `SumberTransaksiController` | `/sumber-transaksi` | (**BUKAN** RekenungController) saldo, adjust |
| `TagController` | `/tags` | search |

### Feature Controllers
| Controller | Route | Catatan |
|---|---|---|
| `LaporanController` | `/laporan` | harian/mingguan/bulanan/tahunan/perbandingan |
| `DashboardController` | `/dashboard` | chartData, saveLayout |
| `GamificationController` | `/gamifikasi` | weeklyReview |
| `AIController` | `/api/ai/*` | checkDuplicate, detectAnomaly, generateInsights |
| `OCRController` | `/api/ocr/*` | extract, history |
| `ImportBankController` | `/import-bank` | (**BUKAN** ImportController) preview, template |
| `NotifikasiController` | `/notifikasi` | (**BUKAN** NotificationController) |
| `SettingController` | `/settings` | (**BUKAN** SettingsController) |
| `HouseholdController` | `/household` | invite, join, roles |
| `ProfileController` | `/settings` | update, photo |
| `PrivacyController` | `/privacy` | policy, terms, dataExport |
| `OnboardingController` | `/onboarding` | wizard steps |
| `CronController` | `/cron` | HTTP cron endpoints (via cron-job.org) |
| `SuperadminController` | `/superadmin` | households, users, logs, health |
| `SuperadminAiMonitorController` | `/superadmin/ai-monitor` | AI usage monitoring |

### Controllers yang TIDAK ADA:
- ~~`BudgetController`~~ → `AnggaranController`
- ~~`SavingsGoalController`~~ → `TabunganController`
- ~~`LoginController`~~ → `AuthenticatedSessionController`
- ~~`RekenungController`~~ → `SumberTransaksiController`
- ~~`SettingsController`~~ → `SettingController`
- ~~`NotificationController`~~ → `NotifikasiController`
- ~~`ImportController`~~ → `ImportBankController`
- ~~`Controllers/Api/V1/`~~ → TIDAK ADA, API routes di `web.php` prefix `/api/`

---

## 5. SERVICE AKTUAL (35 Service)

### Core Business
- `TransaksiService` — CRUD + summary
- `AnggaranService` — budget + alerts + copy bulan lalu
- `TabunganService` — savings + setor/tarik
- `HutangPiutangService` — debt + cicilan + summary
- `RecurringService` — proses + schedule next run

### AI & Analysis
- `GeminiService` — OCR, suggest, insight, anomaly (Gemini 2.5 Flash)
- `OCRService` — file handling (validate, compress, base64)
- `AnomalyDetectionService` — deteksi anomali transaksi
- `InsightService` — generate + manage insights bulanan
- `DedupService` — duplicate detection + scoring

### Data & Import
- `BankImportService` — (**BUKAN** BankMutasiImportService) CSV/Excel import
- `BankParser/`: `BCAParser`, `MandiriParser`, `BNIParser`, `BSIParser`, `GenericParser`
- `MandiriExcelParser` — Excel Mandiri khusus
- `ExcelDecryptService` — handle Excel terenkripsi
- `ExportService` — CSV/Excel/PDF export

### Dashboard & Reports
- `LaporanService` — harian/mingguan/bulanan/tahunan
- `DashboardService` — summary, pengeluaran per kategori, saldo per sumber
- `TokoPolaService` — suggest keterangan dari history (BUKAN model Toko)

### Notifications & Settings
- `NotifikasiService` — (**BUKAN** NotificationService) send/read/delete, tipe: budget, savings, debt, household, recurring
- `ImageService` — compress + thumbnail
- `PlanLimitService` — enforce plan limits (saat ini `internalBypass = true`)

### Gamification
- `AchievementService` — evaluate() pencapaian user
- `ChallengeService` — assignForUser(), evaluateActive()
- `XpService` — award() XP dengan daily cap
- `MomentumService` — recordActivity(), applyDailyDecay(), getStatus()
- `LevelService` — level calculation (quadratic formula)
- `GamificationDashboardService` — data untuk view gamifikasi
- `GamificationInsightService` — generate insights
- `WeeklyReviewService` — generateForUser(), markViewed()

### Services yang TIDAK ADA:
- ~~`BankMutasiImportService`~~ → `BankImportService`
- ~~`NotificationService`~~ → `NotifikasiService`
- ~~`ReferralService`~~ → tidak diimplementasikan
- ~~`MarketplaceParser/`~~ (Shopee, TiktokShop) → tidak diimplementasikan

---

## 6. FIELD TRANSAKSI — YANG BENAR

```php
// BENAR
$transaksi->jenis           // 'pemasukan' | 'pengeluaran' | 'transfer'
$transaksi->jumlah          // decimal:2
$transaksi->sumber_transaksi_id  // FK ke sumber_transaksi
$transaksi->user_id         // siapa yang input
$transaksi->keterangan      // catatan/deskripsi
$transaksi->ocr_items       // JSON array items dari OCR (nullable)

// SALAH — jangan pakai ini:
// $transaksi->tipe          ← SALAH, gunakan ->jenis
// $transaksi->total         ← SALAH, gunakan ->jumlah
// $transaksi->toko_id       ← TIDAK ADA
// $transaksi->rekening_id   ← SALAH, gunakan ->sumber_transaksi_id
// $transaksi->input_by      ← SALAH, gunakan ->user_id
// $transaksi->catatan       ← SALAH, gunakan ->keterangan
// $transaksi->items()       ← TIDAK ADA, gunakan ->ocr_items (JSON)
```

---

## 7. ARTISAN COMMANDS

```bash
php artisan gamification:daily-decay          # Momentum decay (cron 00:05)
php artisan gamification:generate-challenges  # Assign challenges (cron Senin 00:10)
php artisan gamification:generate-weekly-reviews  # Weekly review (cron Minggu 23:00)
php artisan sumber:recalculate-saldo {--household=}  # Recalculate saldo sumber transaksi

# Standard development
php artisan migrate:fresh --seed
php artisan route:list
php artisan config:clear && php artisan cache:clear
```

---

## 8. STATUS FITUR

### ✅ Selesai & Aktif
- Transaksi CRUD (manual + OCR)
- Anggaran (budget) + alert
- Tabungan (savings goals)
- Hutang/Piutang + cicilan
- Recurring transactions
- Bank import (BCA, Mandiri, BNI, BSI, Generic)
- Laporan (harian/mingguan/bulanan/tahunan/perbandingan)
- Export (CSV/Excel/PDF)
- Gamification (XP, Level, Momentum, Achievements, Challenges, Weekly Review) — Phases 1-5
- RBAC via Spatie permissions (admin/member/viewer)
- OCR struk via Gemini API
- AI insights + anomaly detection
- Household management + undangan
- Notifikasi sistem
- PDP compliance (ConsentLog, data export)
- Superadmin dashboard + AI usage monitor
- Onboarding wizard
- Guided Tour / User Guide interaktif (Driver.js) — first-run per halaman, DB per-user, ID+EN. Detail: lihat [[userguide]]

### 🔄 Ada Strukturnya, Belum Di-enforce
- **Freemium/PlanLimit** — `PlanLimitService` ada tapi `internalBypass = true`, middleware belum dipasang ke routes, kolom quota baru di tabel `plans` belum dimigrasikan. Detail: lihat `freemium-context.md`

### ❌ Tidak Diimplementasikan
- Payment gateway (Midtrans)
- Backup/restore
- Referral system
- Marketplace parser (Shopee, TiktokShop)
- Mobile app
- Email notifikasi (welcome, renewal reminder)

---

## 9. KONVENSI KODE

Lihat [[CONTEXT]] untuk detail lengkap. Ringkasan:

- **Naming PHP:** PascalCase class, camelCase method/variable, UPPER_SNAKE_CASE constant
- **Naming DB:** snake_case plural table, is_{sesuatu} boolean
- **Controller pattern:** validasi via Form Request → panggil Service → return view/JSON
- **Service return:** `['success' => bool, 'data' => mixed, 'message' => string]`
- **Format angka:** `Rp ` + `number_format($val, 0, ',', '.')`
- **Format tanggal:** `$date->translatedFormat('d F Y')` (Carbon locale `id`)
- **Flash messages:** `->with('success'|'error'|'warning'|'info', 'Pesan Bahasa Indonesia.')`
- **UI:** Blade + **Bootstrap 5** (template "Dompet", ter-bundle di `public/dompet/css/style.css`) + **jQuery** + **metisMenu** + Chart.js — via CDN/aset lokal, tidak ada build process. ⚠️ **Tidak memakai TailwindCSS maupun Alpine.js** (klaim lama sudah dikoreksi — verifikasi `layouts/app.blade.php`).
- **API routes:** `/api/*` prefix di `routes/web.php` (BUKAN `routes/api.php`)

---

## 10. FILE CONTEXT LAINNYA

| File | Isi | Status |
|---|---|---|
| [[context-index]] | Hierarchy map + foam graph semua context file | ✅ Akurat |
| [[CONTEXT]] | Konvensi coding detail, patterns, checklist | ✅ Akurat |
| [[CHANGELOG]] | Riwayat perubahan versi aplikasi | ✅ Aktif |
| [[DEPLOYMENT_CHECKLIST]] | Langkah deploy ke shared hosting cPanel | ✅ Aktif |
| [[PDP_CHECKLIST]] | Checklist kepatuhan UU PDP No. 27/2022 | ✅ Aktif |
| [[VERSIONING]] | Panduan sistem versioning aplikasi | ✅ Aktif |
| [[gamifikasi-context]] | Design decisions gamification system | ✅ Akurat |
| [[gamifikasi]] | Filosofi + spec lengkap gamifikasi | ✅ Akurat |
| [[UIUXgame-context]] | Design decisions UIUX gamifikasi Phase 5 | ✅ Akurat |
| [[UIUXgame]] | Visual guidelines gamifikasi | ✅ Akurat |
| [[freemium-context]] | Roadmap freemium + payment (belum diimplementasikan) | ✅ Akurat sebagai roadmap |
| [[userguide]] | Guided tour / user guide interaktif (Driver.js) — DB per-user, ID+EN | ✅ Implemented (v1.5.0) |
| [[PROMPT]] | Master prompt awal — **OUTDATED**, banyak nama salah | ⚠️ Jangan dijadikan referensi teknis |
| `docs/superpowers/plans/` | Implementation plans per feature | Arsip — lihat [[context-index]] |

**Knowledge Graph (shared tool):**
Query AI ke context files via `c:\laragon\www\context-ai\` — lihat README di sana.
```bash
python c:/laragon/www/context-ai/query.py --project romoly "pertanyaan"
```

---

## 11. CHECKLIST SEBELUM MENULIS KODE

- [ ] Pakai nama model yang benar (lihat Bagian 3)
- [ ] Pakai nama controller/service yang benar (lihat Bagian 4-5)
- [ ] Pakai `jenis` bukan `tipe`, `jumlah` bukan `total`, `keterangan` bukan `catatan`
- [ ] Pakai `sumber_transaksi_id` bukan `rekening_id`
- [ ] Cek RBAC: pakai `$user->hasRole('admin')` bukan check field `role`
- [ ] Gamification hooks: setelah save transaksi → `XpService::award()` + `MomentumService::recordActivity()`
- [ ] Format angka: Rupiah, format tanggal: Bahasa Indonesia
- [ ] Semua teks UI dalam Bahasa Indonesia
