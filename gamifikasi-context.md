# Gamification System — Context & Design Decisions

Dokumen ini merangkum keputusan desain yang disepakati dari diskusi review `gamifikasi.md`.
Baca bersama `gamifikasi.md` (filosofi + spec) dan plan di `docs/superpowers/plans/2026-05-28-gamification-system.md` (implementasi teknis).

---

## Keputusan Desain

### 1. Level Curve — Quadratic Formula (bukan hardcode)

**Masalah:** Spec asli hardcode 10 angka XP threshold. Gap level 1→2 terlalu mudah (100 XP), gap 9→10 terlalu besar.

**Keputusan:** Gunakan formula quadratic:

```
xpToNextLevel(n) = floor(50 * n ^ 1.8)
xpThreshold(level) = sum dari xpToNextLevel(1..level-1)
```

Constants di `LevelService`:
```php
const XP_BASE     = 50;
const XP_EXPONENT = 1.8;
```

**Alasan:** Jika XP reward di-adjust di masa depan, level curve ikut proporsional otomatis tanpa update 10 angka hardcode.

---

### 2. Achievement — Dua Tier Type Eksplisit

**Masalah:** Semua achievement diperlakukan setara padahal "7 Days Tracking" (time-based) berbeda nilainya dari "Debt Free" (behavior outcome).

**Keputusan:** Pisahkan menjadi dua tier:

| Tier Type   | Contoh Achievement           | XP Multiplier | Tujuan                        |
|-------------|------------------------------|---------------|-------------------------------|
| `awareness` | 7 Days Tracking, 30 Days Awareness | Lebih kecil | Onboarding, bangun kebiasaan  |
| `financial` | Budget Guardian, Debt Free   | Lebih besar   | Reward keputusan finansial nyata |

**Di UI:** Cukup bedakan warna badge — tidak perlu label eksplisit ke user.

---

### 3. Insight Engine — Per-User Adaptive Threshold

**Masalah:** Threshold flat global (misal "night spending > 200k") tidak akurat — user Jakarta vs kota kecil beda jauh.

**Keputusan:** Gunakan rolling 30-day baseline per user:

```php
// Bukan flat threshold:
// if ($night_expense > 200000) → trigger

// Tapi adaptive:
$baseline = Transaksi::where('user_id', $user->id)
    ->where('jenis', 'pengeluaran')
    ->where('created_at', '>=', now()->subDays(30))
    ->whereRaw('HOUR(created_at) >= 21')
    ->avg('jumlah') ?? 0;

if ($thisWeekNightAvg > $baseline * 1.3) → trigger insight
```

**Fallback:** User baru (data < 7 hari) → skip insight tersebut, jangan trigger false positive.

**Berlaku untuk semua insight rules:** overspending, food delivery dominance, night spending pattern.

---

### 4. Challenge System — Reward Structure Lengkap

**Masalah:** Spec tidak mendefinisikan apa yang terjadi saat challenge berhasil/gagal.

**Keputusan:**

```
Challenge berhasil:
  → XP reward (dari field xp_reward di tabel challenges)
  → +momentum_bonus ke momentum score
  → notifikasi "Tantangan Selesai!"

Challenge kedaluwarsa (tidak selesai):
  → TIDAK ada punishment
  → TIDAK ada notifikasi "gagal"
  → Challenge expire dengan bermartabat — user bisa dapat challenge baru periode berikutnya
```

Schema field yang wajib ada di tabel `challenges`:
```
difficulty      : enum(easy, medium, hard)
xp_reward       : int
momentum_bonus  : int
expires_at      : (di user_challenges) timestamp
completed_at    : (di user_challenges) nullable timestamp
```

---

## Catatan Implementasi

### Anti-Abuse XP
- Max transaction XP per day: **20 XP** (configurable via `XpService::DAILY_CAP_TRANSACTION`)
- Daily cap hanya berlaku untuk source `transaction` — source lain (weekly_review, achievement, dll) tidak di-cap
- Duplicate detection: cek amount + source dalam window 5 menit

### Momentum Grace System
- Grace: **1 hari per 7 hari** tanpa decay
- Tracking via: `grace_days_used` + `grace_period_start` di tabel `user_gamification`
- Reset otomatis setiap awal minggu baru
- Decay: **-2 per hari inaktif** (setelah grace habis)
- Minimum: 0, Maximum: 100

### Cron Schedule
| Command | Jadwal | Fungsi |
|---|---|---|
| `gamification:daily-decay` | Setiap hari 00:05 | Apply momentum decay user inaktif |
| `gamification:generate-weekly-reviews` | Minggu 23:00 | Pre-generate weekly review data |
| `gamification:generate-challenges` | Senin 00:10 | Assign challenge baru ke semua user |

### Hook Integrasi
- `TransaksiService::create()` → award XP `transaction` + momentum `transaction_logged`
- `WeeklyReviewService::markViewed()` → award XP `weekly_summary_viewed` + momentum `weekly_review`
- `AchievementService::award()` → award XP `achievement_earned_{tier_type}`

---

## Status Implementasi

| Phase | Status | Commit |
|---|---|---|
| Phase 1 — XP + Level + Momentum | ✅ Selesai | `7066d7ed` |
| Phase 2 — Achievement + Challenge | ✅ Selesai | `785a96a2` |
| Phase 3 — Insight + Weekly Review | ✅ Selesai | `7a42d20e` |
| Phase 4 — Controller + Cron + Views | ✅ Selesai | `b60822e8` |
| Phase 5 — UIUX Polish | ✅ Selesai | — |

**Tests:** 26/26 pass (21 unit + 5 feature)

**Phase 5 mencakup:**
- Kolom `rarity`, `is_hidden`, `is_major` di tabel `achievements`
- SVG circular progress ring di `_level_card`
- CSS glow states di `_momentum_card`
- Achievement collection grid (earned + locked + hidden) di `index`
- Major achievement modal (gold/platinum)
- Navigation menu `Progres Finansial` di sidebar

---

## Referensi File

| File | Keterangan |
|---|---|
| `gamifikasi.md` | Filosofi, spec lengkap semua modul |
| `gamifikasi-context.md` | File ini — keputusan desain & catatan implementasi |
| `UIUXgame.md` | Filosofi visual & UX guidelines |
| `UIUXgame-context.md` | Keputusan desain UIUX Phase 5 |
| `docs/superpowers/plans/2026-05-28-gamification-system.md` | Implementation plan teknis (18 tasks, TDD) |
