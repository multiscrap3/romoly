---
id: userguide-spec
title: "userguide.md — Interactive Product Tour / In-App User Guide"
type: spec
status: implemented
scope: onboarding, ux, user-education
priority: medium
tags: [user-guide, product-tour, walkthrough, onboarding, driverjs, ux, first-run]
version: "1.0.0"
updated: 2026-06-01
git_hash: 960a3f09
depends_on: [UIUXgame, onboarding]
referenced_by: [CLAUDE, context-index]
superseded_by: null
implementation_status: complete
note: "Guided tour interaktif — SUDAH DIIMPLEMENTASIKAN (v1.5.0). Penyimpanan = DB per-user (Bagian 5); i18n = ID + EN (Bagian 9). Status & catatan implementasi di Bagian 15."
---

# userguide.md — Interactive Product Tour (In-App User Guide)

> **Tujuan dokumen:** Prompt/spec matang untuk fitur *guided tour* interaktif bagi user baru
> Romoly (FinanKu). Dokumen ini dipakai sebagai brief implementasi — bukan kode final.
> Bahasa UI: **Bahasa Indonesia**. Stack layout aktual: **Bootstrap 5 + jQuery + metisMenu + template Dompet** (lihat `layouts/app.blade.php`), BUKAN Tailwind/Alpine.

---

## 1. MASALAH & TUJUAN

### Masalah
User baru selesai `onboarding` wizard (buat household, rekening, anggaran) lalu mendarat
di Dashboard dalam keadaan **kosong** dan **tidak tahu harus mulai dari mana**:
tombol mana untuk tambah transaksi, kotak mana yang menampilkan saldo, menu mana untuk setting, dll.

### Tujuan
Sediakan **guided tour interaktif** yang:
1. Otomatis jalan saat user **pertama kali** membuka sebuah halaman.
2. Menyorot (*highlight + spotlight*) elemen UI satu per satu dengan tooltip penjelas
   ("Tombol ini untuk menambah transaksi", "Kotak ini menampilkan total saldo kamu", dst).
3. Berjalan **step-by-step** (Lanjut / Kembali / Lewati) dalam satu halaman.
4. **Tidak mengulang** tour halaman yang sudah pernah dilihat user.
5. Bisa **diputar ulang** kapan saja (tombol "Panduan" / dari Settings).

### Non-tujuan (Out of Scope versi pertama)
- Bukan pengganti `onboarding` wizard (itu tetap untuk setup data awal).
- Tidak ada A/B testing, analytics funnel, atau personalisasi tour berbasis AI.
- Tidak ada video / GIF embed — cukup teks + highlight elemen.

---

## 2. FILOSOFI UX (selaras dengan [[UIUXgame]])

Tour harus terasa: **calm, premium, professional, membantu** — bukan mengganggu.

- **Tidak memaksa:** selalu ada tombol "Lewati" + tutup (Esc / klik luar opsional).
- **Singkat:** maksimal **4–7 langkah per halaman**. Kalimat pendek, jelas, actionable.
- **Progresif:** tour muncul *per halaman saat dikunjungi*, bukan satu tour raksasa di awal.
- **Sekali jalan:** setelah dilihat/diselesaikan/dilewati → tidak muncul lagi otomatis.
- **Hormati konteks:** jangan jalan saat ada modal/flash error terbuka, atau di tengah form yang sedang diisi.
- **Bahasa ramah keluarga:** sapaan "kamu", nada hangat tapi ringkas.

---

## 3. PERILAKU / FLOW

### 3.1 Trigger otomatis (first-run per halaman)
```
User buka halaman X
  └─ Cek: apakah tour halaman X sudah pernah dilihat?
        ├─ Sudah  → tidak ada apa-apa
        └─ Belum  → tunggu DOM ready + render selesai (~400ms)
                     └─ Jalankan tour X
                            └─ Saat selesai / dilewati → tandai "X seen"
```

### 3.2 Trigger manual (replay)
- Tombol **"Panduan"** (ikon `bi-question-circle`) di topbar / pojok halaman.
- Menu **Settings → "Putar ulang panduan"** (reset semua flag → tour muncul lagi).
- Klik tombol Panduan saat di halaman X → langsung jalankan tour X (abaikan flag).

### 3.3 Tour global "Selamat datang" (sekali seumur akun)
Saat **pertama kali** masuk Dashboard setelah onboarding:
1. Tour global elemen layout: sidebar menu, FAB tambah transaksi, ikon notifikasi, profil/setting, toggle tema.
2. Dilanjutkan tour spesifik Dashboard.
> Setelah ini, tiap halaman lain punya tour-nya sendiri saat pertama dikunjungi.

### 3.4 State per langkah
- Tombol: **« Kembali**, **Lanjut »**, **Selesai** (langkah terakhir), **Lewati panduan** (selalu ada).
- Indikator progres: "Langkah 2 dari 5".
- Highlight: spotlight gelap di sekeliling, popover menunjuk elemen target.

---

## 4. STACK TEKNIS

### Library: **Driver.js** (rekomendasi)
- Vanilla JS, ~5KB gzipped, **tanpa dependency**, cocok dengan Bootstrap + jQuery.
- CDN (sesuai aturan shared hosting — no build process):
  ```html
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
  <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
  ```
- Fitur yang dipakai: `driver()`, `steps[]`, `popover`, `onHighlightStarted`, `onDestroyed`,
  `showProgress`, label tombol custom (Bahasa Indonesia).

> Alternatif dipertimbangkan: **Shepherd.js** (lebih berat, butuh Floating UI), **Intro.js**
> (lisensi komersial untuk produk berbayar — **hindari** karena Romoly SaaS). → **Pilih Driver.js**.

### Penargetan elemen: atribut `data-tour`
Jangan andalkan class Bootstrap (rapuh). Tambahkan atribut stabil di elemen target:
```html
<button data-tour="fab-add" ...>          {{-- FAB tambah transaksi --}}
<div data-tour="dash-hero-saldo" ...>      {{-- kotak total saldo --}}
<a data-tour="nav-gamifikasi" ...>         {{-- menu gamifikasi --}}
```
Selektor di step: `element: '[data-tour="fab-add"]'`.

---

## 5. PENYIMPANAN PROGRESS — ✅ **KEPUTUSAN FINAL: DB per-user**

Disimpan di kolom JSON `tour_progress` pada tabel `users`. Mengikuti **precedent yang sudah ada**:
kolom `dashboard_cards` (JSON, nullable, cast `array`) di migration
`2024_01_01_000024_add_dashboard_cards_to_users_table.php`. Tour memakai pola identik.

**Migration baru** (`...add_tour_progress_to_users_table.php`):
```php
public function up(): void {
    Schema::table('users', function (Blueprint $table) {
        $table->json('tour_progress')->nullable()->after('dashboard_cards');
    });
}
public function down(): void {
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('tour_progress');
    });
}
```

**Perubahan `app/Models/User.php`:**
- Tambah `'tour_progress'` ke `$fillable` (sejajar `dashboard_cards`).
- Tambah `'tour_progress' => 'array'` ke `casts()`.
- Helper: `hasSeenTour(string $key): bool`, `markTourSeen(string $key): void`, `resetTour(): void`.

**Struktur isi kolom:**
```json
{
  "welcome_completed": true,
  "seen": ["dashboard", "transaksi.index", "anggaran.index"],
  "updated_at": "2026-06-01T10:00:00Z"
}
```
> `null` (user lama / baru) diperlakukan sebagai "belum lihat apa pun" → tour tetap muncul. Ini disengaja.

**Endpoint AJAX** — taruh di `routes/web.php` grup `prefix('api')->middleware('auth')` (sesuai konvensi
Romoly: API di `web.php`, BUKAN `api.php`), controller `TourController`:
```
POST   /api/tour/seen      body: { key: "transaksi.index" }   → markTourSeen(key)
POST   /api/tour/welcome                                       → welcome_completed = true
DELETE /api/tour/reset                                         → resetTour() (untuk "putar ulang panduan")
```
Semua request AJAX wajib kirim header `X-CSRF-TOKEN` (dari `<meta name="csrf-token">` yang sudah ada di layout).

> **Catatan deploy:** migration ini harus dijalankan saat deploy (lihat `DEPLOYMENT_CHECKLIST`).
> Karena shared hosting tanpa SSH, ikuti cara yang sama seperti migration `dashboard_cards` terdahulu.

---

## 6. ARSITEKTUR IMPLEMENTASI

```
resources/views/layouts/app.blade.php          (layout aplikasi utama)
resources/views/layouts/superadmin.blade.php    (HANYA jika tour superadmin diaktifkan — Fase 6)
  ├─ @stack('styles') → driver.css (di-push dari partials.tour)
  ├─ <body ... data-tour-route="{{ Route::currentRouteName() }}"
  │         data-tour-seen='@json(auth()->user()->tour_progress["seen"] ?? [])'
  │         data-tour-welcome='@json(auth()->user()->tour_progress["welcome_completed"] ?? false)'
  │         data-user-roles='@json(auth()->user()->getRoleNames())'>
  ├─ @include('partials.tour')   {{-- tombol "Panduan" + i18n window var + bootstrapping --}}
  └─ @stack('scripts') → driver.js.iife + tour-core.js

public/js/tour/
  ├─ tour-core.js        → engine: baca body data-*, pilih steps route aktif, filter, jalankan, mark-seen
  └─ tour-steps.js       → registry KEY-only: { 'dashboard': ['dash-hero-saldo', ...], ... }
                            (teks diambil dari window.TOUR_I18N, target dari data-tour)

app/Http/Controllers/TourController.php
  → seen(Request), welcome(), reset()

app/Models/User.php
  → fillable + cast 'tour_progress' => 'array'
  → hasSeenTour($key), markTourSeen($key), resetTour()

lang/id/tour.php  &  lang/en/tour.php   → semua copy tooltip + label tombol
```

### Alur runtime
1. Layout menulis ke `<body>`: `data-tour-route`, `data-tour-seen`, `data-tour-welcome`, `data-user-roles`.
   `partials.tour` menyuntik `window.TOUR_I18N = @json(__('tour'))` (atau hanya subset route aktif).
2. `tour-core.js` baca route aktif → ambil daftar langkah dari `tour-steps.js`.
3. **Filter langkah** (urutan penting):
   a. Buang langkah yang `roles`-nya tidak cocok dengan `data-user-roles` (RBAC `viewer`).
   b. Buang langkah yang **elemen target-nya tidak ada di DOM** (`document.querySelector` → null).
      → mencegah Driver.js menampilkan popover melayang di tengah (perilaku default jika elemen hilang).
4. Jika route belum ada di `seen` (atau dipicu manual) **dan** masih ada langkah tersisa → jalankan
   setelah DOM siap + delay ~400ms. Skip bila ada `.modal.show` / alert error terbuka (tunda).
5. Khusus Dashboard: jika `welcome_completed=false` → jalankan tour `welcome` dulu, lalu `dashboard`.
6. Saat `onDestroyed` (Selesai / Lewati / Esc) → `fetch` POST `/api/tour/seen` (header `X-CSRF-TOKEN`)
   → server `markTourSeen(route)`. Untuk welcome → POST `/api/tour/welcome`.

### Konvensi key tour
Pakai **nama route Laravel** sebagai key: `dashboard`, `transaksi.index`, `transaksi.create`,
`anggaran.index`, `gamifikasi.index`, dst. Tour global welcome pakai key khusus `welcome`.

---

## 7. INVENTARIS LENGKAP — SEMUA HALAMAN & FITUR

> Tiap langkah ditulis sebagai **Judul — teks tooltip final** (copy yang dibaca user, Bahasa
> Indonesia). Target selektor (`data-tour="..."`) ditambahkan ke Blade saat implementasi.
> Hormati RBAC: langkah aksi-tulis (tambah/edit/hapus) di-skip untuk role `viewer`.
> Teks final ini menjadi sumber isi `resources/lang/id/tour.php` (lihat Bagian 9).

### 7.0 GLOBAL / LAYOUT (key: `welcome`) — sekali seumur akun
| # | Elemen (`data-tour`) | Pesan tooltip |
|---|---|---|
| 1 | `nav-sidebar` | "Ini menu utama. Semua fitur Romoly ada di sini." |
| 2 | `fab-add` | "Tombol ini untuk **menambah transaksi** cepat: pemasukan, pengeluaran, atau transfer." |
| 3 | `topbar-notif` | "Notifikasi anggaran, tagihan, dan aktivitas keluarga muncul di sini." |
| 4 | `topbar-theme` | "Ganti tampilan terang/gelap sesuai selera." |
| 5 | `topbar-profile` | "Akun, pengaturan, dan keluar dari aplikasi." |
| 6 | `nav-gamifikasi` | "Lihat progres, level, dan pencapaian keuangan kamu di sini." |

### 7.1 DASHBOARD (key: `dashboard`)
| # | Elemen | Pesan |
|---|---|---|
| 1 | `dash-hero-saldo` | "Total saldo seluruh sumber dana kamu ada di kotak ini." |
| 2 | `dash-card-transaksi` | "Ringkasan pemasukan & pengeluaran bulan ini." |
| 3 | `dash-card-anggaran` | "Pantau anggaran — hijau aman, merah lewat batas." |
| 4 | `dash-gamification-insight` | "Kotak progres: XP, momentum, dan misi keuangan kamu." |
| 5 | `dash-chart-kategori` | "Pengeluaran terbesar per kategori divisualkan di sini." |
| 6 | `dash-edit-layout` | "Susun ulang widget dashboard sesuai kebutuhanmu." |
| 7 | `fab-add` | "Siap mulai? Catat transaksi pertamamu dari tombol ini." |

> Catatan empty-state: user baru → kartu kosong. Tooltip tetap menjelaskan *fungsi* kotak,
> tambahkan kalimat "akan terisi setelah kamu mencatat transaksi".

### 7.2 TRANSAKSI

**`transaksi.index`**

1. **Tambah Transaksi** — "Tombol ini untuk mencatat pemasukan, pengeluaran, atau transfer baru."
2. **Filter** — "Saring transaksi berdasarkan jenis, rentang tanggal, atau sumber dana untuk menemukan yang kamu cari."
3. **Pencarian** — "Ketik kata kunci di sini untuk mencari transaksi dari keterangannya."
4. **Export** — "Unduh daftar transaksi ke Excel, CSV, atau PDF untuk arsip atau laporan."
5. **Baris transaksi** — "Klik salah satu baris untuk melihat detail lengkap atau mengubahnya."
6. **Hapus & pulihkan** — "Transaksi yang terhapus tidak langsung hilang permanen — bisa dipulihkan kembali bila salah hapus."

**`transaksi.create`**

1. **Jenis transaksi** — "Pilih dulu: pemasukan (uang masuk), pengeluaran (uang keluar), atau transfer antar sumber dana."
2. **Jumlah** — "Masukkan nominal di sini. Angka otomatis diformat ke Rupiah, jadi cukup ketik angkanya."
3. **Kategori** — "Kelompokkan transaksi (mis. Makan, Transportasi) supaya laporan & anggaran kamu rapi."
4. **Sumber dana** — "Pilih dari rekening/dompet mana uang ini berasal atau masuk."
5. **Scan Struk (OCR)** — "Punya struk belanja? Foto saja di sini, biar AI mengisi jumlah dan rinciannya otomatis."
6. **Keterangan & tag** — "Tambahkan catatan dan tag agar transaksi lebih mudah dicari nanti."
7. **Simpan** — "Selesai? Simpan transaksi. Saldo sumber dana langsung ikut terupdate."

**`transaksi.edit`**

1. **Ubah data** — "Perbarui jumlah, kategori, atau detail lain di sini."
2. **Simpan / Batal** — "Simpan perubahan, atau batalkan jika tidak jadi mengubah."

**`transaksi.show`**

1. **Detail transaksi** — "Semua informasi transaksi ini ditampilkan di sini."
2. **Rincian struk (OCR)** — "Jika transaksi dibuat dari scan struk, daftar item belanja muncul di sini."
3. **Edit / Hapus** — "Ubah atau hapus transaksi langsung dari halaman ini."

### 7.3 IMPORT BANK

**`import-bank.web.index`**

1. **Tujuan fitur** — "Daripada catat manual satu per satu, impor mutasi rekening dari file bank kamu sekaligus."
2. **Riwayat impor** — "Semua impor sebelumnya tercatat di sini, lengkap dengan statusnya."
3. **Mulai impor** — "Klik di sini untuk memulai proses impor mutasi baru."
4. **Unduh template** — "Belum punya format yang pas? Unduh template ini sebagai acuan."

**`import-bank.web.form`**

1. **Pilih bank** — "Pilih bank kamu (BCA, Mandiri, BNI, BSI) agar mutasi dibaca dengan benar — atau Generic untuk format umum."
2. **Upload file** — "Unggah file mutasi (CSV/Excel) yang kamu unduh dari internet/mobile banking."
3. **Preview** — "Cek dulu hasil pembacaan di sini sebelum disimpan — pastikan jumlah & tanggalnya benar."
4. **Konfirmasi & simpan** — "Sudah sesuai? Simpan, dan semua transaksi langsung masuk ke catatanmu."

### 7.4 LAPORAN

**`laporan.index`**

1. **Jenis laporan** — "Pilih sudut pandang: harian, mingguan, bulanan, tahunan, atau bandingkan antar periode."
2. **Rentang tanggal** — "Atur periode yang ingin kamu tinjau di sini."
3. **Export** — "Unduh laporan sebagai PDF atau Excel untuk dibagikan atau diarsipkan."

**`laporan.harian` / `mingguan` / `bulanan` / `tahunan`**

1. **Grafik tren** — "Grafik ini menunjukkan naik-turun pemasukan & pengeluaran kamu sepanjang periode."
2. **Tabel rincian** — "Angka detail di balik grafik ada di tabel ini."
3. **Filter periode** — "Geser periode untuk membandingkan kondisi keuanganmu dari waktu ke waktu."

**`laporan.perbandingan`**

1. **Pilih dua periode** — "Pilih dua rentang waktu yang ingin kamu bandingkan (mis. bulan ini vs bulan lalu)."
2. **Baca selisih** — "Tanda naik/turun menunjukkan di mana pengeluaranmu membengkak atau berhasil ditekan."

### 7.5 GAMIFIKASI

**`gamifikasi.index`**

1. **Level & XP** — "Setiap kali kamu disiplin mencatat keuangan, kamu dapat XP dan naik level. Ini progresmu."
2. **Momentum** — "Momentum menjaga kebiasaan harianmu — makin rajin, makin tinggi. Jangan sampai putus!"
3. **Achievement** — "Lencana pencapaian yang kamu kumpulkan dari kebiasaan finansial yang baik."
4. **Challenge aktif** — "Misi mingguan untuk memacu kebiasaan sehat — selesaikan untuk XP tambahan."
5. **Weekly Review** — "Ringkasan mingguan keuanganmu, dirangkum di sini setiap minggu."

**`gamifikasi.review.show`**

1. **Ringkasan minggu** — "Sorotan keuanganmu selama seminggu terakhir."
2. **Pencapaian** — "Hal-hal baik yang kamu capai minggu ini ditonjolkan di sini."
3. **Saran minggu depan** — "Rekomendasi langkah kecil untuk minggu berikutnya."

### 7.6 ANGGARAN

**`anggaran.index`**

1. **Tujuan anggaran** — "Tetapkan batas pengeluaran per kategori supaya belanja tetap terkendali."
2. **Progress bar** — "Bar ini menunjukkan berapa yang sudah terpakai dari batas — hijau aman, merah berarti lewat."
3. **Tambah Anggaran** — "Buat anggaran baru untuk kategori tertentu di sini."
4. **Salin bulan lalu** — "Malas atur ulang? Salin anggaran bulan lalu sekali klik."

**`anggaran.create`**

1. **Kategori** — "Pilih kategori pengeluaran yang ingin kamu batasi."
2. **Jumlah batas** — "Tentukan maksimal pengeluaran untuk kategori ini dalam satu periode."
3. **Periode** — "Atur jangka waktu anggaran (biasanya bulanan)."
4. **Notifikasi & threshold** — "Aktifkan agar kamu diingatkan saat pemakaian mendekati batas (mis. 80%)."

**`anggaran.edit`**

1. **Ubah batas/threshold** — "Perbarui batas atau ambang peringatan di sini."
2. **Simpan** — "Simpan perubahan anggaran."

**`anggaran.show`**

1. **Detail pemakaian** — "Lihat seberapa jauh anggaran ini sudah terpakai."
2. **Transaksi terkait** — "Semua transaksi yang masuk ke anggaran ini terdaftar di sini."

### 7.7 TABUNGAN

**`tabungan.index`**

1. **Tujuan tabungan** — "Buat target nabung (mis. Dana Darurat, Liburan) dan pantau perkembangannya."
2. **Progress** — "Bar ini menunjukkan berapa yang sudah terkumpul dari targetmu."
3. **Tambah Target** — "Mulai target tabungan baru di sini."
4. **Setor / Tarik** — "Catat setiap kali kamu menambah atau mengambil dana dari tabungan ini."

**`tabungan.create`**

1. **Nama target** — "Beri nama tujuan menabungmu supaya mudah dikenali."
2. **Target jumlah** — "Berapa total yang ingin kamu kumpulkan?"
3. **Tenggat** — "Kapan target ini ingin tercapai? (opsional, membantu hitung setoran ideal)."
4. **Sumber dana** — "Pilih dari mana setoran tabungan ini diambil."

**`tabungan.edit`**

1. **Ubah target** — "Perbarui nama, jumlah, atau tenggat target."
2. **Simpan** — "Simpan perubahan."

**`tabungan.show`**

1. **Progress** — "Sejauh mana kamu menuju target tabungan ini."
2. **Riwayat setor/tarik** — "Semua aktivitas setor & tarik tabungan ini tercatat di sini."
3. **Setor cepat** — "Tambah dana ke tabungan ini langsung dari sini."

### 7.8 HUTANG & PIUTANG

**`hutang-piutang.index`**

1. **Hutang vs Piutang** — "Hutang = uang yang kamu pinjam; Piutang = uang yang dipinjam orang dari kamu. Keduanya dipantau di sini."
2. **Ringkasan total** — "Lihat sekilas total hutang dan piutang kamu."
3. **Tambah** — "Catat hutang atau piutang baru di sini."
4. **Status** — "Tandai mana yang sudah lunas dan mana yang masih dicicil."

**`hutang-piutang.create`**

1. **Jenis** — "Pilih: ini hutang kamu, atau piutang (orang berhutang ke kamu)?"
2. **Nominal & pihak** — "Masukkan jumlah dan nama pihak yang terkait."
3. **Jatuh tempo** — "Kapan harus dilunasi? Kamu bisa diingatkan menjelang tanggal ini."
4. **Cicilan** — "Akan dibayar bertahap? Aktifkan opsi cicilan di sini."

**`hutang-piutang.show`**

1. **Detail** — "Informasi lengkap hutang/piutang ini."
2. **Bayar / Catat pembayaran** — "Setiap kali ada pembayaran, catat di sini agar sisanya terupdate."
3. **Riwayat cicilan** — "Semua pembayaran yang sudah dilakukan terdaftar di sini."

**`hutang-piutang.edit` / `pembayaran.edit`**

1. **Ubah data** — "Perbarui detail hutang/piutang atau catatan pembayaran."
2. **Simpan** — "Simpan perubahan."

### 7.9 TRANSAKSI RUTIN (RECURRING)

**`recurring.index`**

1. **Tujuan fitur** — "Untuk transaksi yang berulang (gaji, langganan, cicilan), atur sekali dan biarkan tercatat otomatis."
2. **Aktif/nonaktif** — "Hidupkan atau jeda transaksi rutin kapan saja tanpa menghapusnya."
3. **Jalan berikutnya** — "Kolom ini menunjukkan kapan transaksi rutin akan dicatat lagi."
4. **Tambah** — "Buat aturan transaksi rutin baru di sini."

**`recurring.create`**

1. **Detail transaksi** — "Tentukan jenis, jumlah, kategori, dan sumber dana seperti transaksi biasa."
2. **Frekuensi** — "Seberapa sering diulang? Harian, mingguan, atau bulanan."
3. **Tanggal mulai** — "Kapan transaksi rutin ini mulai berlaku."

**`recurring.edit` / `recurring.show`**

1. **Ubah / lihat jadwal** — "Tinjau atau perbarui aturan transaksi rutin ini."

### 7.10 KATEGORI

**`kategori.index`**

1. **Pemasukan vs pengeluaran** — "Kategori dibagi dua jenis. Ini fondasi laporan & anggaran kamu."
2. **Hierarki** — "Kategori bisa punya sub-kategori (mis. Makan → Makan di luar) supaya lebih rinci."
3. **Tambah** — "Buat kategori baru sesuai kebutuhan keluargamu."
4. **Cek sebelum hapus** — "Sebelum menghapus, sistem memberitahu jika kategori masih dipakai transaksi."

**`kategori.create` / `kategori.edit`**

1. **Nama** — "Beri nama kategori yang jelas."
2. **Jenis** — "Tentukan apakah ini kategori pemasukan atau pengeluaran."
3. **Induk** — "Jadikan sub-kategori dengan memilih kategori induk (opsional)."
4. **Ikon/warna** — "Beri warna atau ikon agar mudah dikenali di laporan."

### 7.11 SUMBER DANA (SUMBER TRANSAKSI)

**`sumber-transaksi.index`**

1. **Daftar sumber dana** — "Semua rekening, dompet digital, dan kas tunai kamu beserta saldonya ada di sini."
2. **Tambah** — "Daftarkan rekening atau dompet baru di sini."
3. **Aktif/nonaktif** — "Sumber dana lama bisa dinonaktifkan tanpa menghilangkan riwayatnya."
4. **Saldo otomatis** — "Saldo dihitung otomatis dari transaksi — kamu tidak perlu mengubahnya manual."

**`sumber-transaksi.create` / `edit`**

1. **Nama** — "Beri nama sumber dana (mis. BCA, GoPay, Dompet Tunai)."
2. **Jenis** — "Pilih tipe: rekening bank, e-wallet, atau tunai."
3. **Saldo awal** — "Masukkan saldo saat ini sebagai titik mulai perhitungan."

### 7.12 TAGS

**`tags.index`**

1. **Fungsi tag** — "Tag memberi label lintas kategori (mis. #liburan, #anak) supaya transaksi terkait mudah dikumpulkan."
2. **Tambah/edit inline** — "Buat atau ubah tag langsung di sini tanpa pindah halaman."
3. **Cari tag** — "Temukan tag dengan cepat lewat kotak pencarian."

### 7.13 HOUSEHOLD (KELUARGA)

**`household.index`**

1. **Konsep keluarga** — "Household adalah ruang data keuangan keluargamu — terpisah penuh dari pengguna lain."
2. **Undang anggota** — "Ajak pasangan atau anggota keluarga lewat undangan agar bisa mengelola keuangan bersama."
3. **Gabung** — "Punya kode undangan? Masukkan di sini untuk bergabung ke household lain."

**`household.members`**

1. **Anggota & peran** — "Lihat siapa saja di keluarga ini dan perannya: admin, member, atau viewer."
2. **Ubah peran** — "Atur hak akses tiap anggota — admin penuh, member bisa input, viewer hanya melihat."
3. **Keluarkan anggota** — "Hapus anggota dari household bila perlu."
4. **Batalkan undangan** — "Tarik kembali undangan yang belum diterima."

### 7.14 SETTINGS / PROFIL

**`settings.index`**

1. **Profil & foto** — "Atur nama dan foto profilmu di sini."
2. **Ganti password** — "Jaga keamanan akun dengan memperbarui kata sandi secara berkala."
3. **Data household** — "Ubah nama dan detail keluargamu."
4. **Preferensi** — "Sesuaikan bahasa, tema terang/gelap, dan format mata uang."
5. **Putar ulang panduan** — "Ingin melihat tur fitur lagi dari awal? Reset panduan di sini."
6. **Reset data transaksi** — "Hapus semua transaksi untuk mulai dari nol. Hati-hati — tindakan ini tidak bisa dibatalkan."

### 7.15 NOTIFIKASI

**`notifikasi.index`**

1. **Jenis notifikasi** — "Peringatan anggaran lewat batas, tabungan tercapai, hutang jatuh tempo, aktivitas keluarga, dan transaksi rutin muncul di sini."
2. **Tandai dibaca** — "Klik notifikasi untuk menandainya sudah dibaca."
3. **Tandai semua** — "Bersihkan semua notifikasi belum dibaca sekali klik."

### 7.16 PRIVASI / PDP

**`privacy.export`**

1. **Hak atas data** — "Sesuai UU PDP, kamu berhak mengunduh seluruh data pribadimu kapan saja."
2. **Unduh data** — "Klik di sini untuk mengunduh salinan datamu."
3. **Format & isi** — "Penjelasan apa saja yang termasuk dalam berkas unduhan."

### 7.17 ONBOARDING (sudah ada wizard — tour ringan opsional)

**`onboarding.index`**

1. **Indikator langkah** — "Lingkaran di atas menunjukkan progres setup awalmu."
2. **Bisa dilewati** — "Tidak sempat sekarang? Lewati saja — kamu bisa lengkapi nanti."
3. **Bisa diubah nanti** — "Semua data yang diisi di sini bisa kamu ubah kapan saja lewat menu masing-masing."

### 7.18 SUPERADMIN (role superadmin saja — prioritas rendah)

**`superadmin.dashboard`**

1. **Metrik global** — "Ringkasan seluruh platform: jumlah household, user, dan aktivitas."
2. **Navigasi panel** — "Akses panel pengelolaan household, user, log, kesehatan sistem, dan monitor AI."

**`superadmin.households` / `users` / `logs` / `health` / `ai-monitor`**

1. **Fungsi panel** — "Penjelasan singkat (1–2 langkah) fungsi tiap panel monitoring & pengelolaan."

---

## 8. EDGE CASES & ATURAN

1. **Elemen tidak ada di DOM** (mis. FAB disembunyikan, widget di-hide user) → **skip langkah itu** otomatis (`element` null-safe), jangan error.
2. **RBAC `viewer`** → skip semua langkah aksi-tulis (Tambah/Edit/Hapus). Sediakan varian step beranotasi `roles: ['admin','member']`.
3. **Empty state user baru** → tooltip jelaskan fungsi + "akan terisi setelah ada data".
4. **Mobile (≤767px)** → sidebar tersembunyi di balik hamburger. Untuk langkah `nav-*`: buka dulu sidebar (trigger klik `#mobileNavToggle`) via `onHighlightStarted`, atau ganti target ke tombol hamburger.
5. **Dark mode** → pastikan kontras popover Driver.js oke di `data-theme-version="dark"` (override CSS bila perlu).
6. **Flash error / modal terbuka** → tunda tour sampai modal/alert ditutup (cek `document.querySelector('.modal.show')`).
7. **i18n** → semua teks step lewat `lang/{id,en}/tour.php` (catatan: project ini pakai `lang/` di root, BUKAN `resources/lang/`). Jangan hardcode di JS — inject via global `window.TOUR_I18N = @json(__('tour'))`.
8. **Form sedang diisi** di halaman `create/edit` → tour jalan sekali di awal sebelum user mengetik; jangan jalan ulang saat validasi gagal reload.
9. **Reduced motion** → hormati `prefers-reduced-motion` (matikan animasi spotlight).

---

## 9. INTERNASIONALISASI (i18n) — ✅ **KEPUTUSAN FINAL: ID + EN**

Kedua locale dibuat sekaligus. Lokasi: **`lang/id/tour.php`** & **`lang/en/tour.php`**
(project memakai `lang/` di root — sejajar `lang/id/dashboard.php`, `lang/en/transaksi.php`, dst.
**BUKAN** `resources/lang/`). LocaleMiddleware sudah aktif secara global, jadi `__('tour...')`
otomatis ikut bahasa user.

**Struktur** — `common` untuk label tombol, lalu satu array per key route. Tiap langkah punya
`tour` (nilai atribut `data-tour` sbg target) + `title` + `body`. Field `roles` opsional (default semua role):
```php
// lang/id/tour.php
return [
  'common' => [
    'next' => 'Lanjut', 'prev' => 'Kembali', 'done' => 'Selesai',
    'skip' => 'Lewati panduan', 'progress' => 'Langkah :current dari :total',
    'replay_button' => 'Panduan',
  ],
  'welcome' => [
    ['tour' => 'fab-add', 'title' => 'Tambah Transaksi', 'body' => 'Tombol ini untuk menambah transaksi cepat: pemasukan, pengeluaran, atau transfer.', 'roles' => ['admin','member']],
    // ...
  ],
  'dashboard' => [
    ['tour' => 'dash-hero-saldo', 'title' => 'Total Saldo', 'body' => 'Total saldo seluruh sumber dana kamu ada di kotak ini.'],
    // ... (sumber teks = Bagian 7)
  ],
  // ... semua key route lain
];
```
```php
// lang/en/tour.php — padanan Inggris
return [
  'common' => [
    'next' => 'Next', 'prev' => 'Back', 'done' => 'Done',
    'skip' => 'Skip guide', 'progress' => 'Step :current of :total',
    'replay_button' => 'Guide',
  ],
  'dashboard' => [
    ['tour' => 'dash-hero-saldo', 'title' => 'Total Balance', 'body' => 'Your total balance across all fund sources appears in this box.'],
    // ...
  ],
];
```
> **TODO implementasi:** seluruh copy ID di Bagian 7 dipindah ke `lang/id/tour.php`, lalu dibuat
> padanan EN di `lang/en/tour.php`. Jaga jumlah & urutan langkah identik antar locale.

---

## 10. RENCANA IMPLEMENTASI (FASE)

| Fase | Lingkup | Output |
|---|---|---|
| **0. Keputusan** ✅ | Penyimpanan = **DB per-user**; i18n = **ID + EN**; cakupan = semua halaman & fitur | **SELESAI** (terkunci di spec ini) |
| **1. Fondasi** | Migration `tour_progress` + User (fillable/cast/helper); `TourController` + 3 route AJAX; Driver.js CDN; `tour-core.js` + `tour-steps.js`; `partials.tour` (tombol "Panduan" + inject i18n & body data-*); `lang/id/tour.php` + `lang/en/tour.php` (common + skeleton) | Infrastruktur siap |
| **2. Welcome + Dashboard** | Tour global `welcome` + `dashboard` + `data-tour` di layout & dashboard | Tour pertama jalan end-to-end |
| **3. Fitur inti** | Transaksi, Anggaran, Tabungan, Hutang-Piutang, Recurring | 5 modul utama |
| **4. Pendukung** | Laporan, Gamifikasi, Import Bank, Kategori, Sumber Dana, Tags, Household, Settings, Notifikasi, Privasi | Sisanya |
| **5. Polish** | Mobile, dark mode, RBAC skip, reduced-motion, "putar ulang", QA | Rilis |
| **6. (Opsional)** | Superadmin tours | Internal |

---

## 11. ACCEPTANCE CRITERIA

- [ ] User baru pasca-onboarding melihat tour `welcome` + `dashboard` otomatis, sekali saja.
- [ ] Tiap halaman menjalankan tour-nya saat pertama dikunjungi, lalu tidak lagi.
- [ ] Tombol "Lewati panduan" menghentikan & menandai seen.
- [ ] "Putar ulang panduan" di Settings mereset semua flag.
- [ ] Tombol "Panduan" memutar ulang tour halaman aktif kapan saja.
- [ ] Tidak ada error JS jika elemen target tidak ada (skip mulus).
- [ ] `viewer` tidak melihat langkah aksi-tulis.
- [ ] Berfungsi di mobile (sidebar) & dark mode.
- [ ] Semua teks Bahasa Indonesia (dan EN bila locale en).
- [ ] Tidak ada build step baru (CDN only).

---

## 12. KEPUTUSAN (sebelumnya pertanyaan terbuka)

| # | Pertanyaan | Keputusan |
|---|---|---|
| 1 | Penyimpanan progress | ✅ **A — DB per-user** (kolom `tour_progress`, Bagian 5) |
| 2 | Letak tombol "Panduan" | **Topbar** (default, selalu terlihat) — bisa direvisi saat implementasi |
| 3 | Pemicu tour `welcome` | **Saat pertama buka Dashboard** (lebih robust, mencakup user yang skip onboarding) |
| 4 | Tour Superadmin di rilis pertama | **Tunda** ke Fase 6 (prioritas rendah) |
| 5 | Locale | ✅ **ID + EN** dibuat sekaligus (Bagian 9) |

> Keputusan #2–#4 adalah default yang diadopsi dari rekomendasi; ubah di sini bila berubah pikiran.

---

## 13. CATATAN INTEGRASI CONTEXT

- Setelah disepakati, tambahkan baris ke tabel **Bagian 10 CLAUDE.md** dan ke `context-index.md`.
- Buat `userguide-context.md` (design decisions) bila implementasi dimulai — pola sama dgn `gamifikasi-context.md`.
- **Koreksi CLAUDE.md:** Bagian 9 menyebut UI pakai "Blade + TailwindCSS CDN + Alpine.js"; namun `layouts/app.blade.php` aktual memakai **Bootstrap 5 + jQuery + metisMenu (template Dompet)**. Perlu diluruskan agar spec UI konsisten.

---

## 14. HASIL CROSS-CHECK KELENGKAPAN IMPLEMENTASI

Verifikasi terhadap codebase aktual (2026-06-01, `git_hash 960a3f09`). Item ✅ sudah dibereskan
di spec; item ⚠️ adalah pertimbangan yang harus diingat saat koding.

### A. Koreksi fakta yang sebelumnya salah/asumtif di spec

- ✅ **Path lang**: project pakai **`lang/`** di root (terbukti: `lang/id/dashboard.php`, `lang/en/transaksi.php`, dst), BUKAN `resources/lang/`. Sudah dikoreksi di Bagian 6 & 9.
- ✅ **Precedent kolom JSON**: `dashboard_cards` (migration `2024_01_01_000024`) + cast `array` + masuk `$fillable`. Pola `tour_progress` mengikuti persis. Sudah di Bagian 5.
- ✅ **Konvensi route API**: endpoint tour diletakkan di `routes/web.php` grup `prefix('api')` (sesuai aturan Romoly), bukan `routes/api.php`.

### B. Item yang sebelumnya TERLEWAT — kini ditambahkan

- ✅ **`$fillable` User** harus menyertakan `tour_progress` (bukan cuma cast). → Bagian 5.
- ✅ **CSRF** untuk semua POST/DELETE AJAX (`X-CSRF-TOKEN` dari meta tag). → Bagian 5 & 6.
- ✅ **Injeksi role** ke `<body data-user-roles>` untuk filter langkah RBAC `viewer`. → Bagian 6.
- ✅ **Pre-filter elemen hilang**: tour-core wajib buang langkah yang `querySelector`-nya null SEBELUM init Driver.js (kalau tidak, Driver.js menampilkan popover melayang di tengah — bukan skip). → Bagian 6.
- ✅ **Perlakuan `tour_progress = null`** (user lama/baru) = "belum lihat apa pun". → Bagian 5.
- ✅ **Field `roles` per langkah** di `lang/*/tour.php` (default: semua role). → Bagian 9.
- ✅ **Layout superadmin terpisah** (`layouts/superadmin.blade.php`) — partial tour hanya disuntik ke sini bila Fase 6 jalan. → Bagian 6.
- ✅ **Catatan deploy migration** (shared hosting tanpa SSH, ikut cara `dashboard_cards`). → Bagian 5.

### C. Pertimbangan implementasi yang masih harus dieksekusi (belum ada di kode)

- ⚠️ **`data-tour="..."` belum ada di satu pun Blade.** Ini pekerjaan terbesar: tambahkan atribut ke setiap elemen target di ~40 view (lihat daftar Bagian 7). Tanpa ini tour tidak punya anchor.
- ⚠️ **Tombol "Panduan" di topbar** `layouts/app.blade.php` (header-right, dekat ikon notifikasi/tema) — tambah `<li>` + `data-tour-replay` handler.
- ⚠️ **UI "Putar ulang panduan"** di `resources/views/settings/index.blade.php` → tombol yang memanggil `DELETE /api/tour/reset` lalu redirect ke dashboard.
- ⚠️ **Mobile sidebar**: untuk langkah `nav-*`, tour-core harus buka sidebar dulu (`#mobileNavToggle`) via `onHighlightStarted`, atau alihkan target ke tombol hamburger pada viewport ≤767px.
- ⚠️ **Driver.js z-index vs Bootstrap**: sidebar mobile `z-index:1056`, modal `~1055`. Pastikan overlay/popover Driver.js berada di atasnya (override CSS bila perlu). Cek juga kontras di `data-theme-version="dark"`.
- ⚠️ **`animate: false`** saat `prefers-reduced-motion` (opsi Driver.js), dan `allowKeyboardControl`/Esc → Esc dihitung sebagai "Lewati" (tetap mark-seen).
- ⚠️ **Cache-busting** untuk `public/js/tour/*.js` (mis. `asset('js/tour/tour-core.js').'?v='.config('app.version')`) karena shared hosting sering cache agresif.
- ⚠️ **Payload i18n**: idealnya `window.TOUR_I18N` hanya memuat `common` + key route aktif (bukan seluruh `tour.php`) agar HTML tidak membengkak. Opsional di Fase 1, wajib bila copy makin besar.

### D. Verifikasi cakupan halaman

Semua route bernama di `routes/web.php` (auth, non-cron) sudah punya entri tour di Bagian 7:
Dashboard, Transaksi (index/create/edit/show), Import Bank (index/form), Laporan (index + 5 sub),
Gamifikasi (index/review), Anggaran, Tabungan, Hutang-Piutang (+pembayaran), Recurring, Kategori,
Sumber Transaksi, Tags, Household (index/members), Settings, Notifikasi, Privacy export, Onboarding,
Superadmin (7 panel). **Tidak ada halaman user-facing yang terlewat.** Route `cron.*` & endpoint
`/api/*` murni (OCR/AI/search) dikecualikan karena tanpa UI.

---

## 15. STATUS IMPLEMENTASI (v1.5.0 — 2026-06-01)

✅ **Selesai & aktif.** Lihat [[CHANGELOG]] entri 1.5.0.

### File yang dibuat / diubah
| File | Peran |
|---|---|
| `database/migrations/2026_06_01_000000_add_tour_progress_to_users_table.php` | Kolom JSON `tour_progress` (sudah di-migrate) |
| `app/Models/User.php` | `$fillable` + cast `array` + helper `hasSeenTour/markTourSeen/markWelcomeTourCompleted/resetTour` |
| `app/Http/Controllers/TourController.php` | `seen()`, `welcome()`, `reset()` |
| `routes/web.php` | Grup `/api/tour/*` (POST seen, POST welcome, DELETE reset) |
| `lang/id/tour.php`, `lang/en/tour.php` | Sumber tunggal langkah + label (ID & EN) |
| `public/js/tour/tour-core.js` | Engine: baca body data-*, filter RBAC + elemen, jalankan Driver.js, mark-seen |
| `resources/views/partials/tour.blade.php` | Push driver.js + `window.TOUR_I18N` + engine ke stack `scripts` |
| `resources/views/layouts/app.blade.php` | driver.css di `<head>`, CSS override (z-index/dark), body `data-tour-*`, tombol "Panduan" di topbar, anchor global (sidebar/FAB/topbar), include partial |
| 16 view fitur | Atribut `data-tour="..."` pada elemen target (45 anchor total) |

### Penyesuaian terhadap spec (disengaja)
- **`tour-steps.js` tidak dibuat terpisah.** Definisi langkah (target `tour`, `title`, `body`, `roles`)
  dikonsolidasikan ke `lang/{id,en}/tour.php` sebagai **sumber tunggal** — lebih DRY & otomatis
  ikut locale. `tour-core.js` membaca langsung dari `window.TOUR_I18N`. (Menggantikan rencana
  registry JS terpisah di Bagian 6.)
- Tombol "Panduan" memakai ikon `bi-question-circle` di topbar; reset tour ditaruh di
  **Settings → tab Profil** (tab default yang langsung terlihat).

### Cakupan anchor saat ini (Fase 2–4)
- ✅ Global (welcome) + Dashboard — lengkap.
- ✅ Index semua fitur + `transaksi.create` (form 7 langkah termasuk OCR) + `settings`.
- ⏳ **Belum dianchor** (engine & copy siap, tinggal tambah `data-tour` saat dibutuhkan):
  halaman `*/create|edit|show` selain `transaksi.create`, sub-halaman laporan
  (`harian/mingguan/bulanan/tahunan/perbandingan`), `gamifikasi.review.show`, `household.members`,
  `import-bank.web.form`, `privacy.export`, `onboarding`, dan tour Superadmin (Fase 6).
  Langkah tanpa anchor otomatis di-skip tanpa error.

### Cara verifikasi manual
1. `php artisan migrate` (sudah dijalankan di dev).
2. Login sebagai user baru → buka `/dashboard` → tour `welcome` lalu `dashboard` jalan otomatis.
3. Kunjungi tiap halaman pertama kali → tour halaman jalan sekali.
4. Klik ikon **?** di topbar → tour halaman aktif diputar ulang.
5. Settings → Profil → **Putar ulang panduan** → reset, semua tour muncul lagi.
6. Ganti locale ke EN → teks tour ikut Inggris.
