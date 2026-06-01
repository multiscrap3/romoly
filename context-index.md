---
id: context-index
type: index
status: active
scope: global
priority: high
tags: [index, hierarchy, navigation, knowledge-map]
version: "2.0.0"
updated: 2026-05-31
depends_on: []
referenced_by: []
superseded_by: null
---

# Context Knowledge Map — Romoly (FinanKu)

Peta semua context file: hierarchy, dependency, dan status sekilas.
Update file ini setiap kali ada context file baru atau status berubah.

---

## Hierarki Dokumen

```text
[ROOT]
└── CLAUDE.md                   ← master-ref | critical | global
    ├── CONTEXT.md              ← convention  | high     | global
    ├── CHANGELOG.md            ← ops         | medium   | global
    ├── DEPLOYMENT_CHECKLIST.md ← ops         | medium   | deploy
    ├── PDP_CHECKLIST.md        ← compliance  | high     | global
    ├── VERSIONING.md           ← ops         | low      | global
    ├── freemium-context.md     ← roadmap     | medium   | freemium (pending)
    ├── userguide.md            ← spec        | medium   | onboarding/ux ✅ implemented (v1.5.0)
    └── gamification/
        ├── gamifikasi.md          ← spec     | medium | gamification
        ├── gamifikasi-context.md  ← decision | high   | gamification ✅ done
        ├── UIUXgame.md            ← spec     | low    | gamification
        └── UIUXgame-context.md    ← decision | medium | gamification ✅ done

[PLANS — docs/superpowers/plans/]
├── 2026-05-27-hutang-piutang-pembayaran.md
├── 2026-05-28-rbac-multi-role.md
└── 2026-05-28-gamification-system.md   ← linked from [[gamifikasi-context]]

[ARCHIVE — docs/archive/]
├── PROMPT.md                    ← superseded by CLAUDE.md
├── PROGRESS.md                  ← superseded by CLAUDE.md §8
├── PROGRESS_LATEST.md           ← snapshot 2026-05-12
├── PROGRESS_COMPLETE.md         ← snapshot 2026-05-12
├── PROGRESS_FINAL.md            ← snapshot 2026-05-12
├── PROGRESS_UPDATE.md           ← snapshot 2026-05-12
├── BACKEND_COMPLETE.md          ← snapshot 2026-05-12
├── SERVICES_COMPLETE.md         ← snapshot 2026-05-12
├── FORM_REQUESTS_COMPLETE.md    ← snapshot 2026-05-12
├── AI_OCR_INTEGRATION_PROGRESS.md ← snapshot
├── BANK_IMPORT_PROGRESS.md      ← snapshot
├── FINAL_BACKEND_PROGRESS.md    ← snapshot
├── README_SETUP.md              ← superseded
└── MIGRATION.md                 ← UI migration selesai, arsip
```

---

## Tabel Lengkap

| File | Type | Status | Scope | Priority | Impl. Status |
| --- | --- | --- | --- | --- | --- |
| [[CLAUDE]] | master-ref | active | global | critical | — |
| [[CONTEXT]] | convention | active | global | high | — |
| [[CHANGELOG]] | ops | active | global | medium | — |
| [[DEPLOYMENT_CHECKLIST]] | ops | active | deploy | medium | — |
| [[PDP_CHECKLIST]] | compliance | active | global | high | — |
| [[VERSIONING]] | ops | active | global | low | — |
| [[freemium-context]] | roadmap | active | freemium | medium | partial — bypass aktif |
| [[userguide]] | spec | implemented | onboarding/ux | medium | ✅ complete (v1.5.0) |
| [[gamifikasi]] | spec | active | gamification | medium | — (filosofi) |
| [[gamifikasi-context]] | decision | active | gamification | high | ✅ complete (Phase 1-5) |
| [[UIUXgame]] | spec | active | gamification | low | — (filosofi) |
| [[UIUXgame-context]] | decision | active | gamification | medium | ✅ complete (Phase 5) |
| [[PROMPT]] | archive | archived | global | none | superseded |

---

## Dependency Graph

```text
gamifikasi.md ──────────────────► gamifikasi-context.md ──► UIUXgame-context.md
UIUXgame.md ────────────────────────────────────────────►/
UIUXgame.md ────────────────────► userguide.md
onboarding (wizard) ────────────► userguide.md
CLAUDE.md ──────────────────────► freemium-context.md
CLAUDE.md ──────────────────────► userguide.md
CLAUDE.md ──────────────────────► CHANGELOG, DEPLOYMENT_CHECKLIST, PDP_CHECKLIST, VERSIONING
CLAUDE.md ◄── CONTEXT.md
CLAUDE.md ◄── gamifikasi-context.md
CLAUDE.md ◄── freemium-context.md
CLAUDE.md ◄── UIUXgame-context.md
context-index.md ◄── CLAUDE.md (bidirectional)
```

---

## Quick Reference: Tag → File

| Tag | Files |
| --- | --- |
| `master`, `architecture` | [[CLAUDE]] |
| `coding-convention`, `naming`, `patterns` | [[CONTEXT]] |
| `deploy`, `cpanel`, `shared-hosting` | [[DEPLOYMENT_CHECKLIST]] |
| `changelog`, `versioning` | [[CHANGELOG]], [[VERSIONING]] |
| `compliance`, `pdp`, `privacy` | [[PDP_CHECKLIST]] |
| `gamification`, `xp`, `level`, `momentum` | [[gamifikasi]], [[gamifikasi-context]] |
| `gamification`, `ui`, `ux`, `rarity` | [[UIUXgame]], [[UIUXgame-context]] |
| `freemium`, `plan`, `subscription` | [[freemium-context]] |
| `user-guide`, `product-tour`, `walkthrough`, `tour`, `onboarding` | [[userguide]] |
| `archive`, `outdated` | docs/archive/ |

---

## Aturan Update

1. Setiap kali membuat context file baru → tambahkan ke tabel dan hierarki di sini
2. Setiap kali `implementation_status` berubah → update kolom "Impl. Status"
3. Setiap kali file diarsipkan → pindahkan ke section `[ARCHIVE]`
4. Update `git_hash` di CLAUDE.md frontmatter setiap kali feature baru selesai
5. Frontmatter wajib ada di setiap context file baru (lihat schema di bawah)

---

## Schema Frontmatter Standar

```yaml
---
id: slug-unik-kebab-case
title: "Nama File — Deskripsi Singkat"
type: master-ref | convention | spec | decision | roadmap | index | archive
status: active | draft | archived | deprecated
scope: global | gamification | freemium | auth | transaksi | laporan
priority: critical | high | medium | low | none
tags: [tag1, tag2]
version: "x.y.z"
updated: YYYY-MM-DD
depends_on: [file1.md, file2.md]
referenced_by: [file3.md]
superseded_by: null | nama-file.md
# Field opsional untuk roadmap/decision:
implementation_status: complete | partial | pending
phases_done: [1, 2]
phases_pending: [3, 4]
---
```
