---
id: gamifikasi-spec
title: "gamifikasi.md — Gamification System Spec & Philosophy"
type: spec
status: active
scope: gamification
priority: medium
tags: [gamification, xp, level, momentum, achievement, challenge, weekly-review, philosophy]
version: "1.1.0"
updated: 2026-05-31
depends_on: []
referenced_by: [gamifikasi-context.md, UIUXgame-context.md, CLAUDE.md]
superseded_by: null
note: "Filosofi & spec dasar. Untuk keputusan implementasi aktual → lihat [[gamifikasi-context]]"
---
# Money Tracking Gamification System — Spec & Philosophy

## Core Philosophy

Gamification pada sistem money tracking bukan bertujuan membuat aplikasi terasa seperti game.

Tujuan utama:

* meningkatkan consistency,
* meningkatkan financial awareness,
* memberi rasa progress,
* membantu user membangun habit finansial sehat.

Gamification harus:

* subtle,
* profesional,
* tidak childish,
* tidak manipulatif.

Sistem harus terasa seperti:

> "financial operating system"

bukan mobile game.

---

# Core Principles

## DO

* Reward meaningful behavior
* Prioritize consistency over perfection
* Use soft progression
* Show visible progress
* Use emotional milestone
* Encourage reflection
* Make user feel more in control

---

## DON'T

* Hard punishment
* XP farming
* Spam badge
* Childish animation
* Toxic leaderboard
* Daily pressure addiction
* Forced login streak

---

# Architecture Overview

Gamification system terdiri dari beberapa modul terpisah:

| Module             | Purpose                       |
| ------------------ | ----------------------------- |
| XP Engine          | Progression calculation       |
| Level Engine       | Level determination           |
| Momentum Engine    | Activity consistency          |
| Achievement Engine | Milestone system              |
| Challenge Engine   | Weekly/monthly goals          |
| Insight Engine     | Rule-based financial analysis |
| Review Engine      | Weekly/monthly summary        |

---

# Financial Identity Progression

Level merepresentasikan financial maturity.

Bukan lama penggunaan aplikasi.

---

# Level Structure

> **Catatan implementasi:** Tabel XP threshold di bawah adalah spec awal (referensi saja).
> Implementasi aktual menggunakan **exponential formula (base 50, power 1.8)** di `LevelService` — bukan hardcode, bukan quadratic.
> Formula: `floor(50 * n^1.8)` per level, kumulatif.
> Lihat `gamifikasi-context.md` Bagian 1 untuk alasan pemilihan formula.
> Level titles dan urutan tetap berlaku.

| Level | Title                | XP Threshold (referensi) |
| ----- | -------------------- | -------- |
| 1     | Financial Observer   | 0        |
| 2     | Expense Recorder     | ~100     |
| 3     | Spending Aware       | ~250     |
| 4     | Budget Keeper        | ~500     |
| 5     | Cashflow Builder     | ~900     |
| 6     | Discipline Maker     | ~1500    |
| 7     | Financial Defender   | ~2500    |
| 8     | Wealth Planner       | ~4000    |
| 9     | Financial Strategist | ~6500    |
| 10    | Financial Architect  | ~10000   |

---

# XP System

## XP Philosophy

XP diberikan berdasarkan:

* consistency,
* discipline,
* improvement,
* milestone.

Bukan jumlah klik.

---

# XP Rules

## Small XP

| Action                               | XP  |
| ------------------------------------ | --- |
| Add transaction                      | +2  |
| Daily review                         | +5  |
| Categorize uncategorized transaction | +5  |
| Open weekly summary                  | +10 |

---

## Medium XP

| Action                       | XP  |
| ---------------------------- | --- |
| Complete weekly tracking     | +20 |
| Budget within limit (daily)  | +10 |
| Budget within limit (weekly) | +30 |
| 7-day consistency            | +30 |

---

## High XP

| Action                              | XP   |
| ----------------------------------- | ---- |
| Monthly saving target reached       | +100 |
| No overspending 14 days             | +120 |
| Expense reduced from previous month | +150 |
| Emergency fund milestone            | +300 |
| Debt fully paid                     | +500 |

---

# XP Anti Abuse Rules

## Prevent XP farming

### Rules

* Max transaction XP per day:

  * configurable
  * recommended: 20 XP/day

* Duplicate transaction detection:

  * amount + timestamp similarity

* Repeated micro-input cooldown:

  * ignore repeated spam entries

* XP only counted for meaningful transactions

---

# Momentum System

## Replace hard streak system

Do NOT reset streak to zero.

Use momentum scoring instead.

---

# Momentum Score

Range:
0 - 100

---

# Momentum Status

| Score    | Status          |
| -------- | --------------- |
| 90 - 100 | Strong Momentum |
| 70 - 89  | Stable          |
| 40 - 69  | Weakening       |
| 0 - 39   | Lost Focus      |

---

# Momentum Increase Rules

| Action                    | Momentum |
| ------------------------- | -------- |
| Daily transaction logging | +2       |
| Weekly review completed   | +5       |
| Budget compliance         | +5       |
| Saving activity           | +5       |

---

# Momentum Decay Rules

Decay slowly.

Recommended:

* -2 per inactive day
* minimum decay threshold

Never drop instantly to zero.

---

# Grace System

Allow:

* 1 inactive day without penalty every 7 days

Purpose:

* reduce psychological pressure
* reduce uninstall probability

---

# Achievement System

## Achievement Philosophy

Achievements must:

* feel meaningful,
* emotionally relevant,
* tied to financial improvement.

---

# Achievement Categories

## Consistency

| Achievement       | Requirement               |
| ----------------- | ------------------------- |
| 7 Days Tracking   | Track 7 consecutive days  |
| 30 Days Awareness | Open app 30 days in month |
| Weekly Reviewer   | Complete 4 weekly reviews |

---

## Budget Control

| Achievement         | Requirement                 |
| ------------------- | --------------------------- |
| Controlled Spending | Stay within weekly budget   |
| Budget Guardian     | Stay within monthly budget  |
| No Impulse Week     | No unplanned expense 7 days |

---

## Saving

| Achievement       | Requirement                |
| ----------------- | -------------------------- |
| First Saving      | Reach first saving target  |
| Emergency Starter | Build emergency fund       |
| Stable Saver      | Save consistently 3 months |

---

## Debt

| Achievement     | Requirement          |
| --------------- | -------------------- |
| Debt Reducer    | Reduce debt 25%      |
| Debt Controller | No new debt 3 months |
| Debt Free       | Pay all tracked debt |

---

# Achievement Tier System

Each achievement can have tier progression.

Example:

| Tier     | Requirement |
| -------- | ----------- |
| Bronze   | Save 100k   |
| Silver   | Save 1M     |
| Gold     | Save 5M     |
| Platinum | Save 20M    |

---

# Weekly Review System

## Core Retention Feature

Weekly review adalah retention engine utama.

NOT daily login.

---

# Weekly Review Content

Display:

* spending comparison
* budget status
* saving progress
* top spending category
* unusual spending detection
* momentum trend
* XP progression
* achievement progress

---

# Rule-Based Insight Engine

No AI required.

Use rule engine + threshold analysis.

---

# Insight Rules Example

## Overspending Detection

IF:
current_week_expense > previous_week_expense * 1.2

THEN:
"Pengeluaran meningkat lebih dari 20% dibanding minggu lalu."

---

## Subscription Warning

IF:
same_amount recurring > 3 times/month

THEN:
"Langganan rutin terdeteksi."

---

## Food Delivery Pattern

IF:
food_delivery_expense > 30% discretionary_expense

THEN:
"Food delivery menjadi pengeluaran dominan."

---

## Night Spending Pattern

IF:
expense_after_21 > threshold

THEN:
"Pengeluaran malam meningkat."

---

# Challenge System

## Challenge Philosophy

Challenges harus:

* realistic,
* short-term,
* achievable,
* behavior-oriented.

---

# Challenge Examples

## Weekly

* No food delivery 3 days
* Reduce coffee spending
* Max entertainment budget
* Record all expenses 5 days

---

## Monthly

* Save 10% income
* Reduce transport expense
* No overspending month
* Emergency fund contribution

---

# UI/UX Guidelines

## Interface Feel

Must feel:

* clean,
* calm,
* trustworthy,
* professional.

Avoid:

* excessive gamification visuals,
* childish badge effects,
* too many animations.

---

# Recommended UI Style

## Good

* subtle progress bars
* muted achievement display
* clean cards
* financial dashboard feel

---

## Bad

* arcade-style visuals
* excessive confetti
* animated XP explosion
* aggressive popup reward

---

# Retention Philosophy

Users should return because:

* they feel more aware,
* they feel more in control,
* they see progress,
* they understand spending patterns.

NOT because:

* addictive tricks,
* artificial urgency,
* punishment loops.

---

# Long-Term Goal

Final objective:

* help user build sustainable financial awareness,
* improve decision quality,
* reduce financial anxiety,
* create long-term financial discipline.

Gamification is support system.

Not the main product.
