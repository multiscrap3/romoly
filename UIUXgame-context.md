---
id: uiuxgame-context
title: "UIUXgame-context.md — Gamification UIUX Design Decisions"
type: decision
status: active
scope: gamification
priority: medium
tags: [gamification, ui, ux, rarity, achievement, progress-ring, momentum-glow, css, animation, implemented]
version: "1.0.0"
updated: 2026-05-31
depends_on: [UIUXgame.md, gamifikasi-context.md]
referenced_by: [CLAUDE.md]
superseded_by: null
implementation_status: complete
phases_done: [5]
---
# UIUXgame Visual System — Context & Design Decisions

Dokumen ini merangkum keputusan desain yang disepakati dari review [[UIUXgame]].
Baca bersama [[UIUXgame]] (filosofi & visual guidelines) dan [[gamifikasi-context]] (backend decisions).

---

## Keputusan Desain

### 1. Rarity System — Mapping ke Achievement yang Ada

**Gap:** `UIUXgame.md` mengusulkan Bronze/Silver/Gold/Platinum, tapi schema awal hanya punya `tier_type` (awareness/financial).

**Keputusan:** Tambah kolom `rarity` enum ke tabel `achievements`.

| Rarity   | Kriteria                             | Visual                            |
|----------|--------------------------------------|-----------------------------------|
| bronze   | Entry-level, XP < 50                 | Matte brown border `#cd7f32`      |
| silver   | Consistency milestone, XP 50–100     | Metallic gray border `#9e9e9e`    |
| gold     | Financial behavior, XP 100–250       | Amber border + subtle glow        |
| platinum | Long-term mastery, XP > 250          | Purple border + subtle glow       |

**Assignment per achievement:**

| Slug                | Rarity   | is_major | is_hidden |
|---------------------|----------|----------|-----------|
| 7-days-tracking     | bronze   | false    | false     |
| 30-days-awareness   | silver   | false    | false     |
| weekly-reviewer     | silver   | false    | false     |
| controlled-spending | silver   | false    | false     |
| no-impulse-week     | silver   | false    | false     |
| budget-guardian     | gold     | true     | false     |
| first-saving        | gold     | true     | false     |
| debt-controller     | gold     | true     | false     |
| emergency-starter   | gold     | true     | false     |
| debt-reducer        | gold     | true     | false     |
| stable-saver        | platinum | true     | false     |
| debt-free           | platinum | true     | **true**  |

---

### 2. Hidden Achievement — `is_hidden` Flag

**Keputusan:** Tambah kolom `is_hidden boolean default false`. Achievement yang hidden tampil sebagai slot `???` di collection grid sampai unlock.

Saat ini hanya `debt-free` yang hidden — ultimate surprise reveal untuk user yang melunasi semua hutang.

**Di collection grid:** Hidden + locked = tampil nama `???`, deskripsi kosong. Setelah earned = tampil normal dengan rarity platinum.

---

### 3. Major Achievement Modal — `is_major` Flag + 30-Minute Window

**Keputusan:** Tambah kolom `is_major boolean default false`. Gold dan platinum achievement trigger elegant modal.

**Implementation:**
- `GamificationController::index()` mengambil `UserAchievement` dengan `earned_at >= now()->subMinutes(30)` yang `is_major = true`
- View render modal Bootstrap yang trigger via JS saat `DOMContentLoaded`
- Jika ada beberapa major achievement baru → tampil berurutan (modal chain)

**Minor achievements (bronze/silver):** Silent — hanya update collection, tidak ada modal.

---

### 4. Progress Ring — SVG `<circle>` Animated

**Keputusan:** Ganti horizontal `<div class="progress">` di `_level_card.blade.php` dengan SVG circular ring.

**Spec teknis:**
- ViewBox `0 0 100 100`, `cx=50 cy=50 r=40`, `stroke-width=6`
- Circumference = `2π × 40 ≈ 251.33`
- Animasi: initial `stroke-dasharray="0 251.33"` → animate ke target via `requestAnimationFrame` + `setTimeout(100ms)`
- CSS transition: `stroke-dasharray 800ms cubic-bezier(0.4, 0, 0.2, 1)`
- Tidak butuh library external — pure SVG + vanilla JS

---

### 5. Momentum Visual States — CSS Glow

**Keputusan:** Tambah CSS glow class ke card container berdasarkan `$momentumStatus` dari `MomentumService::getStatus()`.

| Status          | CSS Class            | Box Shadow                                      |
|-----------------|----------------------|-------------------------------------------------|
| Strong Momentum | `momentum-strong`    | `0 0 0 2px rgba(16,185,129,.3), 0 4px 16px rgba(16,185,129,.08)` |
| Stable          | `momentum-stable`    | `0 0 0 2px rgba(67,94,190,.2), 0 4px 12px rgba(67,94,190,.06)`   |
| Weakening       | `momentum-weakening` | `0 0 0 2px rgba(245,158,11,.25), 0 4px 12px rgba(245,158,11,.08)`|
| Lost Focus      | `momentum-lost`      | Default card shadow, no glow                    |

---

### 6. Motion Spec — Nilai Konkret

**Keputusan:** Standardize CSS transitions untuk konsistensi:

```css
/* Standard UI interaction */
transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);

/* Progress ring fill */
transition: stroke-dasharray 800ms cubic-bezier(0.4, 0, 0.2, 1);

/* Glow pulse — momentum-strong only */
@keyframes glow-pulse {
  0%, 100% { box-shadow: 0 0 0 2px rgba(16,185,129,.3), 0 4px 16px rgba(16,185,129,.08); }
  50%       { box-shadow: 0 0 0 3px rgba(16,185,129,.45), 0 4px 20px rgba(16,185,129,.15); }
}
animation: glow-pulse 3s ease-in-out infinite;
```

**Forbidden:** bounce, shake, flash, slot-machine, explosive animation.

---

### 7. Achievement Collection — All Achievements Grid

**Keputusan:** `index.blade.php` menampilkan **semua** achievements (earned + locked), bukan hanya yang sudah didapat.

- **Earned:** full color, rarity border + glow
- **Locked:** `filter: grayscale(1)`, `opacity: 0.45`
- **Locked + is_hidden:** tampil sebagai `???` tanpa nama/deskripsi asli

Header collection menampilkan counter `earned / total` (contoh: `3 / 12`).

---

### 8. Financial Health Score — Deferred

**Keputusan:** Tidak diimplementasikan di Phase 5. Butuh definisi formula terpisah. Masuk ke Phase 6 saat roadmap sudah jelas.

---

## Status Implementasi Phase 5

| Task                                    | Status       |
|-----------------------------------------|--------------|
| UIUXgame-context.md                     | ✅ Selesai   |
| Migration rarity + is_hidden + is_major | ✅ Selesai   |
| Achievement model + seeder update       | ✅ Selesai   |
| GamificationController update           | ✅ Selesai   |
| SVG Progress Ring (_level_card)         | ✅ Selesai   |
| Momentum Glow States (_momentum_card)   | ✅ Selesai   |
| Achievement Card partial + rarity CSS   | ✅ Selesai   |
| Major Achievement Modal                 | ✅ Selesai   |
| index.blade.php full collection grid    | ✅ Selesai   |

---

## Referensi File

| File                    | Keterangan                              |
|-------------------------|-----------------------------------------|
| `UIUXgame.md`           | Filosofi visual & UX guidelines         |
| `UIUXgame-context.md`   | File ini — keputusan desain UIUX Phase 5|
| `gamifikasi-context.md` | Backend design decisions gamifikasi     |
