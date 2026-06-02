---
id: tag-context
title: "tag-context.md — Tag System: State Aktual & Enhancement Roadmap"
type: roadmap
status: active
scope: transaksi
priority: medium
tags: [tag, label, filter, laporan, summary, household, transaksi, roadmap]
version: "1.0.0"
updated: 2026-06-01
depends_on: [CLAUDE.md]
referenced_by: [CLAUDE.md, context-index.md]
superseded_by: null
implementation_status: partial
phases_done: [0, 1, 2]
phases_pending: [3]
---

# Tag System — State Aktual & Enhancement Roadmap

Dokumen ini menjelaskan kondisi fitur Tag saat ini, gap yang ditemukan, dan roadmap pengembangan yang disepakati.
Baca sebelum menyentuh `Tag`, `TagController`, `TransaksiService`, `LaporanService`, atau tabel `tags` / `transaksi_tags`.

> **Status:** Fondasi sudah ada (CRUD + attach ke transaksi + filter backend). UI filter dan laporan per tag **belum diimplementasikan**.

---

## 1. Kondisi Kode yang Sudah Ada (Phase 0 — Baseline)

### Skema Database

```sql
-- tags
id, household_id (FK), nama VARCHAR(100), slug VARCHAR(100), warna CHAR(7), timestamps
UNIQUE (household_id, slug)

-- transaksi_tags (pivot)
id, transaksi_id (FK), tag_id (FK), timestamps
UNIQUE (transaksi_id, tag_id)
```

### Kode yang Sudah Ada

| Layer | File | Apa yang Ada |
|---|---|---|
| Model | `app/Models/Tag.php` | `BelongsToHousehold` trait, relasi `transaksi()` BelongsToMany |
| Model | `app/Models/Transaksi.php` | relasi `tags()` BelongsToMany |
| Controller | `app/Http/Controllers/TagController.php` | index, store, update, destroy, **search (AJAX)** |
| Service | `app/Services/TransaksiService.php` | `getTransaksi()` sudah support `filters['tags']` via `whereHas` |
| View | `resources/views/transaksi/create.blade.php` | tag pills (checkbox) saat input transaksi |
| View | `resources/views/transaksi/edit.blade.php` | tag pills dengan pre-select dari DB |
| View | `resources/views/transaksi/show.blade.php` | tampil badge tag |
| View | `resources/views/tags/index.blade.php` | halaman CRUD tag |

### Gap yang Ditemukan

1. **Filter tag belum ada di UI** — `transaksi/index.blade.php` form filter tidak punya input tag, padahal `TransaksiService::getTransaksi()` sudah support parameter `tags[]`
2. **`hasAny()` badge tidak include tag** — indikator "filter aktif" tidak deteksi filter tag
3. **Tidak ada summary per tag** — `getSummary()` di `TransaksiService` tidak menghitung total berdasarkan tag
4. **`LaporanService` tidak aware tag** — tidak ada breakdown laporan per tag
5. **Tidak ada dashboard widget per tag** — `DashboardService` tidak include data tag
6. **Tidak ada batas kuota tag** — `PlanLimitService` tidak enforce jumlah tag per household

---

## 2. Filosofi & Use Case

### Tag sebagai Label Bebas

Tag dirancang sebagai **label fleksibel lintas kategori** — berbeda dari `Kategori` (hierarki, wajib) dan `SumberTransaksi` (sumber dana).

**Use case yang disepakati:**

| Tipe Tag | Contoh | Keterangan |
|---|---|---|
| **Per anggota keluarga** | `#suami`, `#istri`, `#anak-a`, `#anak-b` | Lacak biaya per orang, manual attach saat input |
| **Per proyek/tujuan** | `#renovasi-2026`, `#liburan-bali` | Kumpulkan semua biaya 1 proyek |
| **Per sifat** | `#reimbursable`, `#darurat`, `#investasi` | Label khusus akuntansi keluarga |
| **Per event** | `#lebaran`, `#ultah-anak`, `#pernikahan` | Biaya musiman/event tertentu |

### Bukan Pengganti, Pelengkap

Tag **tidak menggantikan** `Kategori` maupun filter `user_id`. Untuk kebutuhan "laporan per anggota keluarga":
- Tag (`#suami`) = lebih fleksibel, bisa multi-tag, tapi manual
- `user_id` filter = otomatis (siapa yang input), tapi bukan "biaya untuk siapa"

Keduanya saling melengkapi; tag lebih cocok untuk "biaya untuk siapa/apa" sedangkan `user_id` untuk "siapa yang input".

---

## 3. Roadmap Enhancement

### Phase 1 — UI Filter + Summary (Quick Win) 🎯

**Goal:** Aktivasi filter tag yang sudah ada di backend, tambah ringkasan total.

**Perubahan yang diperlukan:**

```
resources/views/transaksi/index.blade.php
  + Tambah input filter tag (multi-select atau checkboxes)
  + Update hasAny() check untuk include 'tags'
  + Update badge "filter aktif" untuk include count tag

app/Services/TransaksiService.php
  + Tambah method getSummaryByTag(array $filters): array
    → return: [['tag' => Tag, 'total_pemasukan' => int, 'total_pengeluaran' => int, 'jumlah_transaksi' => int]]

app/Http/Controllers/TagController.php
  + Tambah method summary(Request $request)
    → return JSON atau view partial summary per tag

resources/views/tags/index.blade.php
  + Tambah tabel ringkasan: tag | jumlah transaksi | total pengeluaran | total pemasukan
```

**Migration:** Tidak perlu (tidak ada perubahan skema).

---

### Phase 2 — Laporan & Dashboard per Tag 📊

**Goal:** Tag menjadi dimensi analisis di laporan dan dashboard.

**Perubahan yang diperlukan:**

```
app/Services/LaporanService.php
  + Tambah method getByTag(Tag $tag, Carbon $dari, Carbon $sampai): array
    → return: ringkasan + list transaksi + chart data per tag

app/Http/Controllers/LaporanController.php
  + Tambah route & action: GET /laporan/tag/{tag}
  + Parameter: tanggal_dari, tanggal_sampai

resources/views/laporan/tag.blade.php (view baru)
  + Summary card: total pemasukan, pengeluaran, transaksi
  + Chart: tren bulanan tag ini
  + Tabel transaksi dengan tag ini

app/Services/DashboardService.php
  + Tambah method getTopTags(int $limit = 5): array
    → return: 5 tag dengan pengeluaran terbesar bulan ini

resources/views/dashboard/
  + Widget "Tag Terbanyak Bulan Ini" (opsional, collapsible)
```

---

### Phase 3 — Tag Bertipe / Rich Tag System ⚙️ (Opsional, Long-term)

**Goal:** Tag bisa dikategorikan berdasarkan tipe untuk filter multi-dimensi.

**Keputusan desain yang perlu disepakati sebelum implementasi:**
- Apakah tipe tag di-enforce (user wajib pilih tipe) atau opsional?
- Apakah UI filter berubah jadi "filter by person tag + event tag" secara terpisah?

**Perubahan skema (migration baru):**

```php
// migration: add_tipe_to_tags_table
$table->enum('tipe', ['person', 'project', 'purpose', 'event', 'other'])
      ->default('other')
      ->after('warna');
```

**Tipe yang disepakati:**

| Tipe | Keterangan | Contoh |
|---|---|---|
| `person` | Anggota keluarga spesifik | `#suami`, `#istri`, `#anak-a` |
| `project` | Proyek / tujuan pengeluaran | `#renovasi`, `#liburan-bali` |
| `purpose` | Sifat transaksi | `#reimbursable`, `#darurat` |
| `event` | Event musiman/insidental | `#lebaran`, `#ultah` |
| `other` | Default, bebas | apapun |

**Catatan:** Phase 3 hanya perlu dilakukan jika Phase 1 + 2 terbukti dipakai aktif dan user membutuhkan filter yang lebih granular. Jangan implementasi prematur.

---

## 4. Keputusan Desain yang Sudah Disepakati

### D1 — Tag untuk Anggota Keluarga: Manual, Bukan Otomatis

Tag member keluarga (`#suami`, `#anak-a`) harus dipilih manual saat input transaksi.
**Tidak akan** ada auto-tag berdasarkan `user_id` login.

**Alasan:** Satu anggota bisa input transaksi atas nama anggota lain (suami bayar keperluan anak). Auto-tag by login tidak akurat untuk use case ini.

### D2 — Tag adalah Shared Resource per Household

Tag dibuat oleh siapapun di household, bisa dipakai siapapun.
**Tidak ada** tag privat per user.

**Alasan:** Konsisten dengan model multi-tenancy household sebagai unit analisis.

### D3 — Filter Tag di UI: Multi-select, OR Logic

Jika user pilih tag `#suami` + `#anak-a`, maka tampil transaksi yang punya **salah satu** tag tersebut (OR), bukan AND.

**Alasan:** AND logic terlalu restriktif untuk tag kombinasi; OR lebih intuitif untuk eksplorasi.

### D4 — Plan Limit untuk Tag

Jumlah tag per household perlu di-enforce ke `PlanLimitService` **setelah** Phase 1 diimplementasikan dan freemium aktif.
Sementara: tidak ada batas (bypass seperti fitur lain).

---

## 5. File yang Akan Dibuat/Diubah (Phase 1 + 2)

### Baru
- `resources/views/laporan/tag.blade.php`

### Diubah
- `resources/views/transaksi/index.blade.php` — tambah filter tag UI
- `resources/views/tags/index.blade.php` — tambah summary table
- `app/Services/TransaksiService.php` — tambah `getSummaryByTag()`
- `app/Services/LaporanService.php` — tambah `getByTag()`
- `app/Services/DashboardService.php` — tambah `getTopTags()`
- `app/Http/Controllers/LaporanController.php` — tambah action `byTag()`
- `app/Http/Controllers/TagController.php` — tambah `summary()`
- `routes/web.php` — tambah route `/laporan/tag/{tag}`

### Phase 3 Saja
- `database/migrations/xxxx_add_tipe_to_tags_table.php`
- `app/Models/Tag.php` — tambah cast `tipe`, enum constant

---

## 7. Implementasi Selesai (Phase 1 + 2)

**Tanggal:** 2026-06-02

### File yang Dibuat

- `resources/views/laporan/tag.blade.php` — halaman laporan per tag (summary cards, chart tren, breakdown kategori, daftar transaksi)
- `resources/views/dashboard/widgets/top-tags.blade.php` — widget dashboard top tags
- `tests/Unit/Services/TransaksiServiceTagTest.php` — 4 unit tests
- `tests/Unit/Services/LaporanServiceTagTest.php` — 3 unit tests

### File yang Diubah

- `app/Services/TransaksiService.php` — tambah `getSummaryByTag()`
- `app/Services/LaporanService.php` — tambah `getByTag()`
- `app/Services/DashboardService.php` — tambah `getTopTags()`
- `app/Http/Controllers/TagController.php` — inject TransaksiService, pass `$summaryByTag` ke view
- `app/Http/Controllers/LaporanController.php` — tambah `byTag(Tag $tag)`
- `app/Http/Controllers/DashboardController.php` — tambah widget `top_tags`, pass `$topTags`
- `routes/web.php` — route `GET /laporan/tag/{tag}` → `laporan.tag`
- `resources/views/tags/index.blade.php` — summary table + link "Lihat Laporan →"
- `resources/views/transaksi/index.blade.php` — filter tag pills + hasAny badge update
- `resources/views/laporan/index.blade.php` — card "Per Tag" ke-5 di quick links
- `resources/views/dashboard.blade.php` — widget `top-tags` via partial include

---

## 6. Dependency ke Fitur Lain

| Fitur | Dependency |
|---|---|
| Phase 1 filter UI | Tidak ada, bisa langsung |
| Phase 2 laporan | Phase 1 harus selesai (UX konsistensi) |
| Phase 2 dashboard | Independen dari Phase 2 laporan |
| Phase 3 tipe tag | Perlu sepakat desain UI dulu |
| Plan limit tag | Tunggu [[freemium-context]] Phase 1 selesai |
