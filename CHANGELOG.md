# Changelog — FinanKu

> Referenced by: [[CLAUDE]] · [[context-index]]

Semua perubahan penting pada aplikasi FinanKu didokumentasikan di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
dan project ini menggunakan [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- Middleware: HouseholdMiddleware, RoleMiddleware, LogActivityMiddleware, CheckPlanLimitMiddleware
- Controllers: KategoriController, SumberTransaksiController, HutangPiutangController, RecurringTransaksiController, TagController, HouseholdController, SettingController, NotifikasiController, AuthController, ProfileController
- Services: HutangPiutangService, NotifikasiService, ExportService, ImportService, RecurringService
- Form Requests: StoreTabunganRequest, StoreHutangPiutangRequest, UpdateProfileRequest, UpdatePasswordRequest, JoinHouseholdRequest, ImportFileRequest

---

## [1.9.0] — 2026-06-03

### Added
- **Endpoint HTTP cron gamifikasi** di `CronController` (dijaga `cron.secret`), untuk dijadwalkan via cron-job.org pada shared hosting tanpa cron native:
  - `POST /cron/gamifikasi/daily-decay` — momentum daily decay seluruh user.
  - `POST /cron/gamifikasi/generate-challenges` — assign challenge mingguan.
  - `POST /cron/gamifikasi/weekly-reviews` — generate weekly review.
  - Melengkapi route yang sudah terdaftar sebelumnya (padanan command `php artisan gamification:*`).

### Docs
- `gamifikasi-context.md`: tambah frontmatter terstruktur + tautan foam (`[[...]]`) ke `gamifikasi` & plan implementasi.

---

## [1.8.0] — 2026-06-03

### Changed
- **Penyederhanaan wizard onboarding dari 5 langkah menjadi 3**: Household → Sumber Dana → Anggaran. Langkah "Recurring" dan "Selesai" dihilangkan agar setup awal lebih cepat.
- **Sumber Dana kini wajib** pada onboarding (minimal satu) — tidak bisa dilewati, dengan pesan validasi yang jelas.

### Removed
- Route `POST /onboarding/recurring` (`onboarding.recurring`) dan `POST /onboarding/selesai` (`onboarding.selesai`) beserta method terkait di `OnboardingController`.

---

## [1.7.1] — 2026-06-03

### Fixed
- **Halaman autentikasi (login/daftar) di mobile & tablet** (`layouts/auth.blade.php`): form panjang kini dapat di-scroll. Sebelumnya rantai `min-height:100vh` + `body{height:100%}` dari template "Dompet" mem-pin dokumen setinggi layar sehingga konten bawah (mis. link "Daftar"/"Masuk") terpotong dan tak bisa dijangkau. Diperbaiki khusus viewport `< lg` tanpa mengubah tampilan desktop.

---

## [1.7.0] — 2026-06-03

### Added
- **Tag Enhancement (Phase 1 + 2)** — pengembangan sistem tag transaksi (dikembangkan 2026-06-02).
  - **Filter tag di halaman Transaksi**: filter multi-tag (checkbox berwarna) yang ikut dipertahankan pada query string bersama filter lain.
  - **Summary per tag di halaman Tags**: tabel ringkasan per tag (jumlah transaksi, total pengeluaran, total pemasukan) via `TransaksiService::getSummaryByTag()`.
  - **Laporan per tag**: halaman `laporan/tag` (chart tren 6 bulan + breakdown per kategori) via `LaporanController@byTag` + `LaporanService::getByTag()`, route `GET /laporan/tag/{tag}` (`laporan.tag`).
  - **Widget "Tag Bulan Ini" di dashboard**: Top N tag berdasarkan pengeluaran bulan berjalan via `DashboardService::getTopTags()` (widget dashboard baru, dapat di-toggle).
  - **Slug tag otomatis**: `slug` di-generate dari nama (`Str::slug`) saat membuat/memperbarui tag.
- **Tests**: `TransaksiServiceTagTest`, `LaporanServiceTagTest` (Unit/Services).
- **Dokumentasi**: `tag-context.md` (roadmap & design decision Tag System Phase 1-3); referensi ditambahkan di `CLAUDE.md` & `context-index.md`.

### Catatan
- Phase 3 (tipe/rich tag) belum diimplementasikan — lihat `tag-context.md`.

---

## [1.6.0] — 2026-06-03

### Added
- **Hapus akun pengguna dari Superadmin** (`/superadmin/users`). Superadmin kini dapat menghapus akun langsung dari daftar pengguna, dengan tombol "Hapus" berkonfirmasi di samping aksi Blokir/Aktifkan.
  - **Soft delete**: pengguna ditandai `deleted_at` (kolom sudah ada sejak migration awal) — data transaksi, audit, dan gamifikasi tetap utuh; pengguna otomatis hilang dari listing dan tidak bisa login.
  - **Bisa daftar ulang dengan email yang sama**: saat dihapus, email di-pseudonymisasi menjadi tombstone unik (`deleted_{id}_{ts}@deleted.invalid`, TLD `.invalid` per RFC 2606) sehingga alamat asli bebas dipakai registrasi baru tanpa mengubah alur `RegisterController`. Alamat asli tetap tercatat di `SecurityLog` untuk audit.
- **Endpoint**: `DELETE /superadmin/users/{user}` → `SuperadminController@destroyUser` (route `superadmin.users.destroy`).

### Security
- **Guard penghapusan**: akun ber-role `superadmin` tidak dapat dihapus; superadmin tidak dapat menghapus akunnya sendiri; route dijaga middleware `auth` + `superadmin.global`; binding model menolak pengguna yang sudah terhapus (anti double-delete).
- **Audit**: setiap penghapusan dicatat ke `SecurityLog` dengan `event_type = superadmin.user_deleted`, severity `high`.
- **Pencegahan XSS**: pesan konfirmasi hapus dibaca via `data-confirm` (string murni di handler JS) alih-alih di-inline ke atribut `onsubmit`, mencegah nama pengguna menjadi vektor stored XSS.

### Changed
- **Model `User`** kini memakai trait `SoftDeletes`.
- **VERSION** disinkronkan ke versi aplikasi aktual (sebelumnya tertinggal di `1.4.0`).

---

## [1.5.0] — 2026-06-01

### Added
- **Guided Tour / User Guide interaktif** untuk user baru (berbasis [Driver.js](https://driverjs.com) via CDN). Saat pertama kali membuka sebuah halaman, tour otomatis menyorot elemen UI satu per satu dengan tooltip penjelas ("Tombol ini untuk menambah transaksi", "Kotak ini menampilkan total saldo kamu", dll), berjalan step-by-step (Lanjut/Kembali/Lewati). Tour tidak diulang setelah dilihat.
  - **Tour global "Selamat datang"** (sekali seumur akun, di Dashboard): menu sidebar, FAB tambah transaksi, notifikasi, tema, profil, menu gamifikasi.
  - **Tour per-halaman**: Dashboard + semua halaman utama (Transaksi index & create/OCR, Anggaran, Tabungan, Hutang-Piutang, Recurring, Kategori, Sumber Dana, Tags, Laporan, Gamifikasi, Import Bank, Household, Notifikasi, Settings).
  - **Tombol "Panduan"** (ikon `?`) di topbar untuk memutar ulang tour halaman aktif; **"Putar ulang panduan"** di Settings → Profil untuk reset seluruh progress.
- **Penyimpanan progress per-user**: kolom JSON `users.tour_progress` (`welcome_completed`, `seen[]`, `updated_at`) + helper di model User (`hasSeenTour`, `markTourSeen`, `markWelcomeTourCompleted`, `resetTour`).
- **Endpoint AJAX** (di `web.php` prefix `/api`, middleware `auth`): `POST /api/tour/seen`, `POST /api/tour/welcome`, `DELETE /api/tour/reset` (`TourController`).
- **Internationalization penuh (ID + EN)**: seluruh copy tour di `lang/id/tour.php` & `lang/en/tour.php` (sumber tunggal langkah — `tour`/`title`/`body`/`roles` per langkah).
- **RBAC-aware**: langkah aksi-tulis (tambah/edit) otomatis dilewati untuk role `viewer`; superadmin melihat semua. Langkah dengan elemen yang tidak ada/tersembunyi otomatis di-skip (tanpa error). Mendukung sidebar mobile (auto-open) & `prefers-reduced-motion`.
- **Migration baru**: `2026_06_01_000000_add_tour_progress_to_users_table` (kolom JSON `tour_progress`, mengikuti pola `dashboard_cards`).

### Changed
- **Koreksi dokumentasi stack UI**: `CLAUDE.md` Bagian 1 & 9 diluruskan — UI aktual memakai **Bootstrap 5 (template "Dompet") + jQuery + metisMenu + Chart.js**, BUKAN TailwindCSS/Alpine.js (terverifikasi: 0 kemunculan Tailwind/Alpine di `resources/views`).

---

## [1.4.0] — 2026-05-27

### Added
- **Separator ribuan pada semua input angka**: semua field input nominal (jumlah, target tabungan, saldo awal, cicilan, anggaran, dll) kini menampilkan pemisah ribuan otomatis saat mengetik (contoh: `1500000` → `1.500.000`) menggunakan format lokal Indonesia. Berlaku di seluruh modul: Transaksi, Anggaran, Tabungan, Hutang/Piutang, Recurring, Sumber Dana, dan Onboarding.

---

## [1.3.0] — 2026-05-27

### Fixed
- **Pembayaran hutang/piutang gagal disimpan**: tabel `hutang_piutang_pembayaran` tidak memiliki kolom `sumber_transaksi_id` (dirujuk di service & view tapi tidak ada di migration) — ditambahkan via migration baru
- **Error `user_id` NOT NULL**: kolom `user_id` di tabel `hutang_piutang_pembayaran` tidak di-set oleh `HutangPiutangService::bayar()` — kini di-set dari `auth()->id()` dan kolom dijadikan nullable
- **Relasi `sumberTransaksi` tidak ada di model**: `HutangPiutangPembayaran` tidak punya relasi `sumberTransaksi()` meski di-eager-load di `getRiwayat()` — relasi ditambahkan
- **`sumber_transaksi_id` tidak ada di `$fillable`**: field diam-diam diabaikan Eloquent saat create — ditambahkan ke `$fillable`

### Added
- **Edit pembayaran individu**: setiap record di riwayat pembayaran kini bisa diedit (nominal, sumber dana, tanggal, catatan) dengan reversal saldo otomatis untuk sumber dana lama dan aplikasi ke sumber dana baru
- **Hapus pembayaran individu**: record pembayaran bisa dihapus dengan reversal saldo otomatis; status hutang/piutang dikembalikan ke aktif jika sebelumnya sudah lunas
- **Mode pembayaran Cicilan**: saat membuat hutang/piutang baru, user bisa memilih *Sekali Bayar* atau *Cicilan* beserta nominal per cicilan dan frekuensi (mingguan/bulanan/tahunan)
- **Jadwal cicilan berikutnya**: halaman detail menampilkan tanggal perkiraan cicilan berikutnya berdasarkan pembayaran terakhir
- **Nama sumber dana di riwayat**: setiap row riwayat pembayaran menampilkan nama sumber dana yang digunakan
- **Routes baru**: `GET /pembayaran/{id}/edit`, `PUT /pembayaran/{id}`, `DELETE /pembayaran/{id}`
- **Multilanguage penuh**: semua teks UI baru tersedia di `lang/id/hutang.php` dan `lang/en/hutang.php`

---

## [1.2.1] — 2026-05-25

### Fixed
- **Download data pribadi** (`/privacy/download`): error `Call to a member function toIso8601String() on string` karena field `last_login_at` tidak di-cast ke `datetime` pada model `User` — ditambahkan cast `'last_login_at' => 'datetime'` di `User::casts()`
- **Hapus file import bank** (`/import-bank/{id}/file`): error `SQLSTATE[23000] Column 'file_path' cannot be null` karena kolom `file_path` di tabel `import_bank` tidak nullable — ditambahkan migration `make_file_path_nullable_in_import_bank` untuk mengubah kolom menjadi `nullable`
- **Total Saldo dashboard menampilkan data household lain**: seluruh query di `DashboardService` (total saldo, transaksi bulan ini, anggaran, tabungan, hutang piutang, chart, saldo per sumber) tidak memfilter `household_id` sehingga data lintas household bercampur — semua query ditambahkan filter `WHERE household_id = ?`
- **Reset transaksi — perilaku saldo**: `resetTransaksiData()` kini secara eksplisit menol-kan `saldo_saat_ini` semua akun (`saldo_saat_ini = 0`) setelah semua transaksi dihapus, sesuai ekspektasi user bahwa reset berarti mulai dari Rp 0

---

## [1.2.0] — 2026-05-24

### Added

#### Fitur Reset Data Transaksi
- **Tombol Reset Data** baru di tab *Privasi & Data* pada halaman Pengaturan — memungkinkan user menghapus seluruh riwayat transaksi secara permanen
- **Modal konfirmasi dua langkah**: user harus mengetik kata `RESET` sebelum tombol hapus aktif, mencegah penghapusan data tidak sengaja
- **Reset saldo otomatis**: setelah reset, `saldo_saat_ini` semua akun (SumberTransaksi) dikembalikan ke `saldo_awal` masing-masing
- **Danger Zone** — section khusus dengan border merah & badge "Tidak dapat dibatalkan" untuk visibilitas risiko
- **Multilanguage penuh**: semua teks UI (narasi, modal, konfirmasi, pesan sukses/error) tersedia dalam Bahasa Indonesia (`lang/id/settings.php`) dan English (`lang/en/settings.php`)
- **Route** `DELETE /settings/reset-data` (name: `settings.reset-data`) dengan perlindungan CSRF dan validasi confirm_word sisi server
- Hard-delete (force delete) transaksi termasuk baris yang sudah soft-deleted, dikemas dalam `DB::transaction` untuk atomicity

---

## [1.1.0] — 2026-05-24

### Added

#### Fitur Multi-Bahasa (Internationalization / i18n)
- **LocaleMiddleware** baru (`app/Http/Middleware/LocaleMiddleware.php`) — otomatis menerapkan locale sesuai preferensi user dari database, fallback ke session, lalu config default
- **File translasi lengkap** untuk dua bahasa:
  - `lang/id/` — Bahasa Indonesia (bahasa utama)
  - `lang/en/` — English (US)
  - Mencakup 18 file per bahasa: `messages`, `navigation`, `auth`, `dashboard`, `transaksi`, `laporan`, `anggaran`, `tabungan`, `hutang`, `recurring`, `kategori`, `sumber`, `settings`, `household`, `notifikasi`, `onboarding`, `privacy`, `import`, `tags`, `profile`, `superadmin`
- **Dropdown bahasa di Pengaturan** — user dapat memilih antara 🇮🇩 Bahasa Indonesia dan 🇺🇸 English (US)
- **Arsitektur siap untuk bahasa tambahan** — tambahkan folder `lang/{kode}/` dan daftar kode di `SUPPORTED_LOCALES`

### Changed
- **Semua 67 Blade template** diupdate menggunakan `__()` helper — nav/sidebar, auth, dashboard, transaksi, laporan, anggaran, tabungan, hutang-piutang, recurring, kategori, sumber dana, tags, notifikasi, household, settings, profile, onboarding, privacy, import-bank, superadmin, welcome
- **`<html lang="">` dinamis** di semua layout berdasarkan `app()->getLocale()`
- **SettingController::updatePreferences()** — locale langsung diterapkan (`app()->setLocale()`) dan disimpan ke session saat user simpan preferensi
- **LocaleMiddleware** didaftarkan sebagai web middleware global di `bootstrap/app.php`
- Versi aplikasi diupdate ke `1.1.0`

---

## [1.0.1] — 2026-05-23

### Fixed
- Fitur "Ingat Saya" di halaman login tidak terasa efeknya — disebabkan `SESSION_EXPIRE_ON_CLOSE` bernilai `false` sehingga session cookie memiliki expiry eksplisit 2 jam dan tetap aktif meski browser ditutup. Diperbaiki dengan menambahkan `SESSION_EXPIRE_ON_CLOSE=true` di `.env` agar session cookie dihapus saat browser ditutup, sehingga "Ingat Saya" benar-benar membedakan pengalaman login (tanpa centang → logout saat browser ditutup; dengan centang → tetap login via remember-me cookie 5 tahun).

---

## [1.0.0] — 2026-05-18

### Rilis Pertama — Backend Core (54%)

Versi awal FinanKu dengan pondasi backend yang solid: database layer lengkap,
model-model utama, core services, dan controller pertama.

### Added

#### Database Layer (100%)
- 23 migrations: plans, households, users, sumber_transaksi, kategori, transaksi, anggaran, tabungan, tabungan_transaksi, hutang_piutang, hutang_piutang_pembayaran, notifikasi, laporan, settings, audit_log, import_bank, ocr_history, ai_insights, backup_restore, household_invitations, payment_history, tags, transaksi_tags
- Foreign key constraints dan indexes pada semua tabel
- Soft delete pada tabel kritis (transaksi, tabungan, hutang_piutang)

#### Models (100%) — 19 Models
- `User` — dengan multi-household support dan plan subscription
- `Household` — manajemen keluarga/grup keuangan
- `Plan` — plan langganan (free/premium/family)
- `Kategori` — kategori transaksi (pemasukan/pengeluaran) per household
- `SumberTransaksi` — sumber dana (rekening, dompet, e-wallet) dengan saldo
- `Transaksi` — transaksi keuangan lengkap dengan bukti & tags
- `Anggaran` — budget per kategori per bulan
- `Tabungan` — goal-based savings dengan target & deadline
- `TabunganTransaksi` — riwayat setor/tarik tabungan
- `HutangPiutang` — manajemen hutang & piutang
- `HutangPiutangPembayaran` — riwayat pembayaran hutang/piutang
- `Notifikasi` — sistem notifikasi in-app
- `Laporan` — laporan keuangan tersimpan
- `Setting` — pengaturan per household
- `AuditLog` — audit trail aktivitas
- `ImportBank` — riwayat import mutasi bank
- `OcrHistory` — riwayat OCR struk belanja
- `AiInsights` — insight keuangan dari AI
- `Tag` — label/tag fleksibel untuk transaksi

#### Traits (100%) — 3 Traits
- `BelongsToHousehold` — scope otomatis filter by household
- `HasAuditLog` — auto-logging create/update/delete
- `HasSoftDelete` — soft delete helpers

#### Seeders (100%) — 5 Seeders
- `PlanSeeder` — data plan: Free, Premium, Family
- `SumberTransaksiSeeder` — data sumber transaksi default
- `SettingSeeder` — pengaturan default per household
- `KategoriSeeder` — (via DatabaseSeeder)
- `DatabaseSeeder` — orchestrator semua seeder

#### Services (50%) — 5/10 Services
- `TransaksiService` — CRUD transaksi, upload bukti, transfer antar sumber, auto-update saldo & anggaran
- `DashboardService` — agregasi data dashboard: total saldo, chart 6 bulan, top kategori, distribusi saldo
- `LaporanService` — generate laporan harian/mingguan/bulanan/tahunan, perbandingan periode, grouping by kategori
- `AnggaranService` — create/update/delete anggaran, monitoring realisasi vs target, auto-alert 80% & 100%, copy dari bulan sebelumnya
- `TabunganService` — create/update tabungan, setor/tarik dana, progress tracking, estimasi waktu tercapai, auto-notification saat target tercapai

#### Controllers (33%) — 5/15 Controllers
- `TransaksiController` — CRUD transaksi, upload struk, filter & search, soft delete & restore
- `DashboardController` — render dashboard dengan data agregasi, AJAX chart data
- `LaporanController` — generate & tampilkan laporan, export (PDF/Excel coming soon)
- `AnggaranController` — CRUD anggaran, summary AJAX, copy dari bulan lalu
- `TabunganController` — CRUD tabungan, setor & tarik dana

#### Form Requests (40%) — 4/10 Requests
- `StoreTransaksiRequest` — validasi create transaksi
- `UpdateTransaksiRequest` — validasi update transaksi
- `StoreAnggaranRequest` — validasi create anggaran (unique per kategori/bulan/tahun)
- `UpdateAnggaranRequest` — validasi update anggaran

#### Routes (100%)
- `web.php` — 50+ routes: auth, dashboard, transaksi, laporan, anggaran, tabungan, hutang-piutang, kategori, sumber, household, notifikasi, settings

#### Konfigurasi
- `config/app.php` — APP_VERSION ditambahkan
- `CONTEXT.md` — standar coding & konvensi proyek
- `VERSIONING.md` — panduan versioning

### Technical Stack
- **Framework:** Laravel 13 (PHP 8.3+)
- **Database:** MySQL
- **Frontend Build:** Vite 8 + Tailwind CSS 4
- **Authentication:** Laravel built-in (akan menggunakan Breeze)
- **Queue:** Database driver

---

## Panduan Format Entry

Setiap versi baru menggunakan format:

```
## [X.Y.Z] — YYYY-MM-DD

### Added       — fitur baru
### Changed     — perubahan fitur yang sudah ada
### Deprecated  — fitur yang akan dihapus
### Removed     — fitur yang dihapus
### Fixed       — bug fix
### Security    — perbaikan keamanan
```

[Unreleased]: https://github.com/username/finanku/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/username/finanku/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/username/finanku/releases/tag/v1.0.0
