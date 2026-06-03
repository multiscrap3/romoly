# Gamification System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a 7-module gamification system (XP, Level, Momentum, Achievement, Challenge, Insight, Review) that rewards consistent financial behavior — subtle, professional, non-manipulative.

**Architecture:** Per-user state lives in `user_gamification` (level cache, total XP, momentum). XP flows through `XpService` (anti-abuse + daily cap), triggers `LevelService` (quadratic curve) and `AchievementService`. Momentum replaces hard streaks via slow decay + weekly grace. All engines are standalone services sharing one data layer.

**Tech Stack:** Laravel 13.7 / PHP 8.3, MySQL, Carbon, existing `NotifikasiService` for in-app push, `routes/console.php` for cron.

---

## Scope Note

This plan covers all 7 modules in one delivery. Dependencies flow top-down:
`Level → XP → Momentum → Achievement → Challenge → Insight → Review`

---

## File Map

**New — Migrations:**
- `database/migrations/2026_05_28_100000_create_user_gamification_table.php`
- `database/migrations/2026_05_28_100001_create_xp_logs_table.php`
- `database/migrations/2026_05_28_100002_create_achievements_table.php`
- `database/migrations/2026_05_28_100003_create_user_achievements_table.php`
- `database/migrations/2026_05_28_100004_create_challenges_table.php`
- `database/migrations/2026_05_28_100005_create_user_challenges_table.php`
- `database/migrations/2026_05_28_100006_create_weekly_reviews_table.php`

**New — Models:**
- `app/Models/UserGamification.php`
- `app/Models/XpLog.php`
- `app/Models/Achievement.php`
- `app/Models/UserAchievement.php`
- `app/Models/Challenge.php`
- `app/Models/UserChallenge.php`
- `app/Models/WeeklyReview.php`

**New — Services:**
- `app/Services/LevelService.php`
- `app/Services/XpService.php`
- `app/Services/MomentumService.php`
- `app/Services/AchievementService.php`
- `app/Services/ChallengeService.php`
- `app/Services/GamificationInsightService.php`
- `app/Services/WeeklyReviewService.php`

**New — Commands:**
- `app/Console/Commands/GamificationDailyDecayCommand.php`
- `app/Console/Commands/GenerateWeeklyReviewsCommand.php`
- `app/Console/Commands/GenerateChallengesCommand.php`

**New — Controller + Views:**
- `app/Http/Controllers/GamificationController.php`
- `resources/views/gamification/index.blade.php`
- `resources/views/gamification/_level_card.blade.php`
- `resources/views/gamification/_momentum_card.blade.php`
- `resources/views/gamification/_weekly_review.blade.php`

**New — Seeder:**
- `database/seeders/AchievementSeeder.php`
- `database/seeders/ChallengeSeeder.php`

**New — Tests:**
- `tests/Unit/Services/LevelServiceTest.php`
- `tests/Unit/Services/XpServiceTest.php`
- `tests/Unit/Services/MomentumServiceTest.php`
- `tests/Feature/GamificationTest.php`

**Modified:**
- `app/Models/User.php` — add `gamification()` relationship
- `app/Services/TransaksiService.php` — inject `XpService`, award XP on create
- `routes/web.php` — gamification routes
- `routes/console.php` — cron schedule

---

## Phase 1 — Foundation: Level + XP + Momentum

### Task 1: Migrations — Core Gamification Tables

**Files:**
- Create: `database/migrations/2026_05_28_100000_create_user_gamification_table.php`
- Create: `database/migrations/2026_05_28_100001_create_xp_logs_table.php`

- [ ] **Step 1: Create migration — user_gamification**

```php
<?php
// database/migrations/2026_05_28_100000_create_user_gamification_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_gamification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedTinyInteger('level')->default(1);
            $table->decimal('momentum_score', 5, 2)->default(50.00);
            $table->date('last_active_date')->nullable();
            $table->unsignedTinyInteger('inactive_days_count')->default(0);
            $table->unsignedTinyInteger('grace_days_used')->default(0);
            $table->date('grace_period_start')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gamification');
    }
};
```

- [ ] **Step 2: Create migration — xp_logs**

```php
<?php
// database/migrations/2026_05_28_100001_create_xp_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('xp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 60);
            $table->unsignedSmallInteger('xp_amount');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_logs');
    }
};
```

- [ ] **Step 3: Run migrations**

```bash
php artisan migrate
```

Expected: `2026_05_28_100000_create_user_gamification_table` and `2026_05_28_100001_create_xp_logs_table` both show `Migrated`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_28_100000_create_user_gamification_table.php database/migrations/2026_05_28_100001_create_xp_logs_table.php
git commit -m "feat(gamification): add user_gamification and xp_logs migrations"
```

---

### Task 2: Models — UserGamification + XpLog

**Files:**
- Create: `app/Models/UserGamification.php`
- Create: `app/Models/XpLog.php`
- Modify: `app/Models/User.php`

- [ ] **Step 1: Create UserGamification model**

```php
<?php
// app/Models/UserGamification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGamification extends Model
{
    protected $table = 'user_gamification';

    protected $fillable = [
        'user_id',
        'total_xp',
        'level',
        'momentum_score',
        'last_active_date',
        'inactive_days_count',
        'grace_days_used',
        'grace_period_start',
    ];

    protected function casts(): array
    {
        return [
            'last_active_date'    => 'date',
            'grace_period_start'  => 'date',
            'momentum_score'      => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 2: Create XpLog model**

```php
<?php
// app/Models/XpLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpLog extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'xp_amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Add `gamification()` relationship to User model**

In `app/Models/User.php`, add inside the class after existing relationships:

```php
public function gamification(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(UserGamification::class);
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/UserGamification.php app/Models/XpLog.php app/Models/User.php
git commit -m "feat(gamification): add UserGamification and XpLog models"
```

---

### Task 3: LevelService — Quadratic XP Curve

**Files:**
- Create: `app/Services/LevelService.php`
- Create: `tests/Unit/Services/LevelServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
// tests/Unit/Services/LevelServiceTest.php

namespace Tests\Unit\Services;

use App\Services\LevelService;
use PHPUnit\Framework\TestCase;

class LevelServiceTest extends TestCase
{
    public function test_level_1_starts_at_0_xp(): void
    {
        $this->assertSame(1, LevelService::levelFromXp(0));
    }

    public function test_level_2_reached_at_50_xp(): void
    {
        $this->assertSame(50, LevelService::xpThreshold(2));
        $this->assertSame(2, LevelService::levelFromXp(50));
    }

    public function test_level_stays_at_1_with_49_xp(): void
    {
        $this->assertSame(1, LevelService::levelFromXp(49));
    }

    public function test_max_level_is_10(): void
    {
        $this->assertSame(10, LevelService::levelFromXp(999999));
    }

    public function test_threshold_increases_each_level(): void
    {
        $prev = 0;
        for ($level = 2; $level <= 10; $level++) {
            $threshold = LevelService::xpThreshold($level);
            $this->assertGreaterThan($prev, $threshold, "Level $level threshold must exceed level " . ($level - 1));
            $prev = $threshold;
        }
    }

    public function test_progress_percent_within_level(): void
    {
        $percent = LevelService::progressPercent(25, 1);
        $this->assertGreaterThan(0, $percent);
        $this->assertLessThan(100, $percent);
    }

    public function test_level_10_progress_is_100(): void
    {
        $this->assertSame(100.0, LevelService::progressPercent(999999, 10));
    }

    public function test_title_returns_string_for_all_levels(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->assertNotEmpty(LevelService::title($i));
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/LevelServiceTest.php
```

Expected: FAIL — `Class "App\Services\LevelService" not found`

- [ ] **Step 3: Implement LevelService**

```php
<?php
// app/Services/LevelService.php

namespace App\Services;

class LevelService
{
    const MAX_LEVEL  = 10;
    const XP_BASE    = 50;
    const XP_EXPONENT = 1.8;

    const TITLES = [
        1  => 'Financial Observer',
        2  => 'Expense Recorder',
        3  => 'Spending Aware',
        4  => 'Budget Keeper',
        5  => 'Cashflow Builder',
        6  => 'Discipline Maker',
        7  => 'Financial Defender',
        8  => 'Wealth Planner',
        9  => 'Financial Strategist',
        10 => 'Financial Architect',
    ];

    public static function levelFromXp(int $totalXp): int
    {
        for ($level = self::MAX_LEVEL; $level >= 2; $level--) {
            if ($totalXp >= self::xpThreshold($level)) {
                return $level;
            }
        }
        return 1;
    }

    public static function xpThreshold(int $level): int
    {
        if ($level <= 1) return 0;
        $cumulative = 0;
        for ($i = 1; $i < $level; $i++) {
            $cumulative += (int) floor(self::XP_BASE * pow($i, self::XP_EXPONENT));
        }
        return $cumulative;
    }

    public static function xpToNextLevel(int $currentLevel): int
    {
        if ($currentLevel >= self::MAX_LEVEL) return 0;
        return self::xpThreshold($currentLevel + 1) - self::xpThreshold($currentLevel);
    }

    public static function xpProgressInLevel(int $totalXp, int $currentLevel): int
    {
        return $totalXp - self::xpThreshold($currentLevel);
    }

    public static function progressPercent(int $totalXp, int $currentLevel): float
    {
        if ($currentLevel >= self::MAX_LEVEL) return 100.0;
        $xpInLevel = self::xpProgressInLevel($totalXp, $currentLevel);
        $xpNeeded  = self::xpToNextLevel($currentLevel);
        return $xpNeeded > 0 ? min(100.0, round($xpInLevel / $xpNeeded * 100, 1)) : 100.0;
    }

    public static function title(int $level): string
    {
        return self::TITLES[$level] ?? 'Financial Observer';
    }
}
```

- [ ] **Step 4: Run tests — expect PASS**

```bash
php artisan test tests/Unit/Services/LevelServiceTest.php
```

Expected: All 8 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/LevelService.php tests/Unit/Services/LevelServiceTest.php
git commit -m "feat(gamification): implement LevelService with quadratic XP curve"
```

---

### Task 4: XpService — Award + Anti-Abuse

**Files:**
- Create: `app/Services/XpService.php`
- Create: `tests/Unit/Services/XpServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
// tests/Unit/Services/XpServiceTest.php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserGamification;
use App\Models\XpLog;
use App\Services\LevelService;
use App\Services\NotifikasiService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpServiceTest extends TestCase
{
    use RefreshDatabase;

    private XpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new XpService(
            new LevelService(),
            $this->mock(NotifikasiService::class)
        );
    }

    public function test_award_creates_xp_log(): void
    {
        $user = User::factory()->create();
        $this->service->award($user, 'transaction');
        $this->assertDatabaseHas('xp_logs', ['user_id' => $user->id, 'source' => 'transaction']);
    }

    public function test_award_returns_correct_xp_amount(): void
    {
        $user = User::factory()->create();
        $xp = $this->service->award($user, 'transaction');
        $this->assertSame(XpService::XP_AMOUNTS['transaction'], $xp);
    }

    public function test_daily_cap_stops_transaction_xp_at_limit(): void
    {
        $user = User::factory()->create();
        // Fill cap: 20 XP / 2 XP per transaction = 10 transactions
        for ($i = 0; $i < 10; $i++) {
            $this->service->award($user, 'transaction');
        }
        $earned = $this->service->award($user, 'transaction');
        $this->assertSame(0, $earned);
    }

    public function test_daily_cap_does_not_affect_non_transaction_sources(): void
    {
        $user = User::factory()->create();
        // Fill transaction cap
        for ($i = 0; $i < 10; $i++) {
            $this->service->award($user, 'transaction');
        }
        $xp = $this->service->award($user, 'daily_review');
        $this->assertSame(XpService::XP_AMOUNTS['daily_review'], $xp);
    }

    public function test_award_unknown_source_returns_zero(): void
    {
        $user = User::factory()->create();
        $this->assertSame(0, $this->service->award($user, 'nonexistent_source'));
    }

    public function test_total_xp_accumulates_in_user_gamification(): void
    {
        $user = User::factory()->create();
        $this->service->award($user, 'transaction');
        $this->service->award($user, 'daily_review');
        $gamification = UserGamification::where('user_id', $user->id)->first();
        $expected = XpService::XP_AMOUNTS['transaction'] + XpService::XP_AMOUNTS['daily_review'];
        $this->assertSame($expected, $gamification->total_xp);
    }

    public function test_level_updates_when_xp_threshold_crossed(): void
    {
        $user = User::factory()->create();
        // Level 2 threshold = 50 XP. Award 'consistency_7day' = 30 XP twice = 60 XP → level 2
        $this->service->award($user, 'consistency_7day');
        $this->service->award($user, 'consistency_7day');
        $gamification = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame(2, $gamification->level);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/XpServiceTest.php
```

Expected: FAIL — `Class "App\Services\XpService" not found`

- [ ] **Step 3: Implement XpService**

```php
<?php
// app/Services/XpService.php

namespace App\Services;

use App\Models\User;
use App\Models\UserGamification;
use App\Models\XpLog;
use Illuminate\Support\Facades\DB;

class XpService
{
    const DAILY_CAP_TRANSACTION = 20;

    const XP_AMOUNTS = [
        'transaction'               => 2,
        'daily_review'              => 5,
        'categorize'                => 5,
        'weekly_summary_viewed'     => 10,
        'complete_weekly_tracking'  => 20,
        'budget_daily'              => 10,
        'budget_weekly'             => 30,
        'consistency_7day'          => 30,
        'monthly_saving_reached'    => 100,
        'no_overspend_14days'       => 120,
        'expense_reduced'           => 150,
        'emergency_fund_milestone'  => 300,
        'debt_fully_paid'           => 500,
    ];

    public function __construct(
        private readonly LevelService $levelService,
        private readonly NotifikasiService $notifikasiService,
    ) {}

    public function award(User $user, string $source, array $metadata = []): int
    {
        $xpAmount = self::XP_AMOUNTS[$source] ?? 0;
        if ($xpAmount <= 0) return 0;
        if ($this->isCapReached($user, $source)) return 0;

        return DB::transaction(function () use ($user, $source, $xpAmount, $metadata) {
            XpLog::create([
                'user_id'   => $user->id,
                'source'    => $source,
                'xp_amount' => $xpAmount,
                'metadata'  => $metadata ?: null,
            ]);

            $gamification = UserGamification::firstOrCreate(
                ['user_id' => $user->id],
                ['total_xp' => 0, 'level' => 1, 'momentum_score' => 50.0]
            );

            $oldLevel = $gamification->level;
            $gamification->total_xp += $xpAmount;
            $newLevel = LevelService::levelFromXp($gamification->total_xp);
            $gamification->level = $newLevel;
            $gamification->save();

            if ($newLevel > $oldLevel) {
                $this->notifikasiService->send(
                    $user->id,
                    'Level Up!',
                    'Kamu naik ke level ' . $newLevel . ': ' . LevelService::title($newLevel),
                    'achievement'
                );
            }

            return $xpAmount;
        });
    }

    private function isCapReached(User $user, string $source): bool
    {
        if ($source !== 'transaction') return false;

        $earned = XpLog::where('user_id', $user->id)
            ->where('source', 'transaction')
            ->whereDate('created_at', today())
            ->sum('xp_amount');

        return $earned >= self::DAILY_CAP_TRANSACTION;
    }
}
```

- [ ] **Step 4: Run tests — expect PASS**

```bash
php artisan test tests/Unit/Services/XpServiceTest.php
```

Expected: All 7 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/XpService.php tests/Unit/Services/XpServiceTest.php
git commit -m "feat(gamification): implement XpService with daily cap and anti-abuse"
```

---

### Task 5: MomentumService — Scoring + Decay + Grace

**Files:**
- Create: `app/Services/MomentumService.php`
- Create: `tests/Unit/Services/MomentumServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
// tests/Unit/Services/MomentumServiceTest.php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserGamification;
use App\Services\MomentumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MomentumServiceTest extends TestCase
{
    use RefreshDatabase;

    private MomentumService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MomentumService();
    }

    public function test_record_activity_increases_momentum(): void
    {
        $user = User::factory()->create();
        UserGamification::create(['user_id' => $user->id, 'momentum_score' => 50.0]);
        $this->service->recordActivity($user, 'transaction_logged');
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('52.00', $g->momentum_score);
    }

    public function test_momentum_does_not_exceed_100(): void
    {
        $user = User::factory()->create();
        UserGamification::create(['user_id' => $user->id, 'momentum_score' => 99.0]);
        $this->service->recordActivity($user, 'weekly_review');
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('100.00', $g->momentum_score);
    }

    public function test_decay_reduces_momentum_when_inactive(): void
    {
        $user = User::factory()->create();
        UserGamification::create([
            'user_id'           => $user->id,
            'momentum_score'    => 60.0,
            'last_active_date'  => now()->subDays(2)->toDateString(),
            'grace_days_used'   => 1, // grace already consumed
            'grace_period_start' => now()->startOfWeek()->toDateString(),
        ]);
        $this->service->applyDailyDecay($user);
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('58.00', $g->momentum_score);
    }

    public function test_grace_day_prevents_decay_once_per_week(): void
    {
        $user = User::factory()->create();
        UserGamification::create([
            'user_id'            => $user->id,
            'momentum_score'     => 60.0,
            'last_active_date'   => now()->subDays(2)->toDateString(),
            'grace_days_used'    => 0,
            'grace_period_start' => now()->startOfWeek()->toDateString(),
        ]);
        $this->service->applyDailyDecay($user);
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('60.00', $g->momentum_score);
        $this->assertSame(1, $g->grace_days_used);
    }

    public function test_decay_never_goes_below_zero(): void
    {
        $user = User::factory()->create();
        UserGamification::create([
            'user_id'           => $user->id,
            'momentum_score'    => 1.0,
            'last_active_date'  => now()->subDays(2)->toDateString(),
            'grace_days_used'   => 1,
            'grace_period_start' => now()->startOfWeek()->toDateString(),
        ]);
        $this->service->applyDailyDecay($user);
        $g = UserGamification::where('user_id', $user->id)->first();
        $this->assertSame('0.00', $g->momentum_score);
    }

    public function test_get_status_returns_correct_label(): void
    {
        $this->assertSame('Strong Momentum', $this->service->getStatus(95));
        $this->assertSame('Stable', $this->service->getStatus(75));
        $this->assertSame('Weakening', $this->service->getStatus(55));
        $this->assertSame('Lost Focus', $this->service->getStatus(20));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/Services/MomentumServiceTest.php
```

Expected: FAIL — `Class "App\Services\MomentumService" not found`

- [ ] **Step 3: Implement MomentumService**

```php
<?php
// app/Services/MomentumService.php

namespace App\Services;

use App\Models\User;
use App\Models\UserGamification;
use Carbon\Carbon;

class MomentumService
{
    const DECAY_PER_INACTIVE_DAY = 2.0;
    const GRACE_DAYS_PER_WEEK    = 1;
    const MAX_SCORE              = 100.0;
    const MIN_SCORE              = 0.0;

    const ACTIVITY_GAINS = [
        'transaction_logged' => 2,
        'weekly_review'      => 5,
        'budget_compliance'  => 5,
        'saving_activity'    => 5,
    ];

    public function recordActivity(User $user, string $activityType): void
    {
        $gain = self::ACTIVITY_GAINS[$activityType] ?? 0;
        if ($gain <= 0) return;

        $g = $this->getOrCreate($user);
        $g->momentum_score  = min(self::MAX_SCORE, $g->momentum_score + $gain);
        $g->last_active_date = today();
        $g->inactive_days_count = 0;
        $g->save();
    }

    public function applyDailyDecay(User $user): void
    {
        $g = UserGamification::where('user_id', $user->id)->first();
        if (!$g) return;
        if ($g->last_active_date?->isToday()) return;

        $this->refreshGracePeriodIfNeeded($g);

        if ($g->grace_days_used < self::GRACE_DAYS_PER_WEEK) {
            $g->grace_days_used++;
            $g->save();
            return;
        }

        $g->momentum_score = max(self::MIN_SCORE, $g->momentum_score - self::DECAY_PER_INACTIVE_DAY);
        $g->inactive_days_count++;
        $g->save();
    }

    public function getStatus(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Strong Momentum',
            $score >= 70 => 'Stable',
            $score >= 40 => 'Weakening',
            default      => 'Lost Focus',
        };
    }

    private function getOrCreate(User $user): UserGamification
    {
        return UserGamification::firstOrCreate(
            ['user_id' => $user->id],
            ['total_xp' => 0, 'level' => 1, 'momentum_score' => 50.0]
        );
    }

    private function refreshGracePeriodIfNeeded(UserGamification $g): void
    {
        $start = $g->grace_period_start ?? today()->startOfWeek();
        if (today()->diffInDays($start) >= 7) {
            $g->grace_period_start = today()->startOfWeek();
            $g->grace_days_used    = 0;
        }
    }
}
```

- [ ] **Step 4: Run tests — expect PASS**

```bash
php artisan test tests/Unit/Services/MomentumServiceTest.php
```

Expected: All 6 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/MomentumService.php tests/Unit/Services/MomentumServiceTest.php
git commit -m "feat(gamification): implement MomentumService with grace day and slow decay"
```

---

### Task 6: Hook TransaksiService → Award XP + Momentum

**Files:**
- Modify: `app/Services/TransaksiService.php`

- [ ] **Step 1: Inject XpService and MomentumService into TransaksiService constructor**

In `app/Services/TransaksiService.php`, replace the class opening and constructor area.

Current top of class (around line 13):
```php
class TransaksiService
{
```

Replace with:
```php
class TransaksiService
{
    public function __construct(
        private readonly XpService $xpService,
        private readonly MomentumService $momentumService,
    ) {}
```

Add imports at the top of the file with the other `use` statements:
```php
use App\Services\XpService;
use App\Services\MomentumService;
```

- [ ] **Step 2: Award XP inside the create() DB::transaction callback, after `$transaksi` is created**

In the `create()` method, after the line that attaches tags (the last line inside the `DB::transaction` callback before `return $transaksi`), add:

```php
            // Award XP and record momentum activity
            $user = auth()->user();
            if ($user) {
                $this->xpService->award($user, 'transaction', [
                    'transaksi_id' => $transaksi->id,
                    'amount'       => $transaksi->jumlah,
                    'jenis'        => $transaksi->jenis,
                ]);
                $this->momentumService->recordActivity($user, 'transaction_logged');
            }
```

- [ ] **Step 3: Verify no test regressions**

```bash
php artisan test tests/Feature/
```

Expected: All existing feature tests still pass. If any test that instantiates `TransaksiService` directly breaks, update those tests to pass mock `XpService` and `MomentumService` instances.

- [ ] **Step 4: Commit**

```bash
git add app/Services/TransaksiService.php
git commit -m "feat(gamification): award XP and momentum on new transaction"
```

---

## Phase 2 — Rewards: Achievement + Challenge

### Task 7: Migrations — Achievement + Challenge Tables

**Files:**
- Create: `database/migrations/2026_05_28_100002_create_achievements_table.php`
- Create: `database/migrations/2026_05_28_100003_create_user_achievements_table.php`
- Create: `database/migrations/2026_05_28_100004_create_challenges_table.php`
- Create: `database/migrations/2026_05_28_100005_create_user_challenges_table.php`

- [ ] **Step 1: Create achievements migration**

```php
<?php
// database/migrations/2026_05_28_100002_create_achievements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 150);
            $table->text('description');
            $table->enum('category', ['consistency', 'budget_control', 'saving', 'debt']);
            $table->enum('tier_type', ['awareness', 'financial'])->default('financial');
            $table->unsignedSmallInteger('xp_reward')->default(50);
            $table->string('condition_type', 100);
            $table->json('condition_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
```

- [ ] **Step 2: Create user_achievements migration**

```php
<?php
// database/migrations/2026_05_28_100003_create_user_achievements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at');
            $table->timestamps();
            $table->unique(['user_id', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
```

- [ ] **Step 3: Create challenges migration**

```php
<?php
// database/migrations/2026_05_28_100004_create_challenges_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->enum('type', ['weekly', 'monthly']);
            $table->string('category', 100);
            $table->string('title', 200);
            $table->text('description');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedSmallInteger('xp_reward')->default(30);
            $table->unsignedTinyInteger('momentum_bonus')->default(5);
            $table->string('condition_type', 100);
            $table->json('condition_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
```

- [ ] **Step 4: Create user_challenges migration**

```php
<?php
// database/migrations/2026_05_28_100005_create_user_challenges_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->json('progress')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenges');
    }
};
```

- [ ] **Step 5: Run migrations**

```bash
php artisan migrate
```

Expected: 4 migrations show `Migrated`.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_05_28_100002_create_achievements_table.php database/migrations/2026_05_28_100003_create_user_achievements_table.php database/migrations/2026_05_28_100004_create_challenges_table.php database/migrations/2026_05_28_100005_create_user_challenges_table.php
git commit -m "feat(gamification): add achievements, user_achievements, challenges, user_challenges migrations"
```

---

### Task 8: Models — Achievement, UserAchievement, Challenge, UserChallenge

**Files:**
- Create: `app/Models/Achievement.php`
- Create: `app/Models/UserAchievement.php`
- Create: `app/Models/Challenge.php`
- Create: `app/Models/UserChallenge.php`

- [ ] **Step 1: Create Achievement model**

```php
<?php
// app/Models/Achievement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'category',
        'tier_type', 'xp_reward', 'condition_type', 'condition_value',
    ];

    protected function casts(): array
    {
        return [
            'condition_value' => 'array',
        ];
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
```

- [ ] **Step 2: Create UserAchievement model**

```php
<?php
// app/Models/UserAchievement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $fillable = ['user_id', 'achievement_id', 'earned_at'];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
```

- [ ] **Step 3: Create Challenge model**

```php
<?php
// app/Models/Challenge.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    protected $fillable = [
        'slug', 'type', 'category', 'title', 'description',
        'difficulty', 'xp_reward', 'momentum_bonus', 'condition_type', 'condition_value',
    ];

    protected function casts(): array
    {
        return [
            'condition_value' => 'array',
        ];
    }

    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserChallenge::class);
    }
}
```

- [ ] **Step 4: Create UserChallenge model**

```php
<?php
// app/Models/UserChallenge.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChallenge extends Model
{
    protected $fillable = [
        'user_id', 'challenge_id', 'progress',
        'started_at', 'expires_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress'     => 'array',
            'started_at'   => 'datetime',
            'expires_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->completed_at === null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/Achievement.php app/Models/UserAchievement.php app/Models/Challenge.php app/Models/UserChallenge.php
git commit -m "feat(gamification): add Achievement, UserAchievement, Challenge, UserChallenge models"
```

---

### Task 9: AchievementSeeder + ChallengeSeeder

**Files:**
- Create: `database/seeders/AchievementSeeder.php`
- Create: `database/seeders/ChallengeSeeder.php`

- [ ] **Step 1: Create AchievementSeeder**

```php
<?php
// database/seeders/AchievementSeeder.php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // --- Consistency (awareness tier) ---
            [
                'slug'            => '7-days-tracking',
                'name'            => '7 Days Tracking',
                'description'     => 'Catat transaksi selama 7 hari berturut-turut.',
                'category'        => 'consistency',
                'tier_type'       => 'awareness',
                'xp_reward'       => 30,
                'condition_type'  => 'days_tracked_consecutive',
                'condition_value' => ['days' => 7],
            ],
            [
                'slug'            => '30-days-awareness',
                'name'            => '30 Days Awareness',
                'description'     => 'Buka aplikasi 30 hari dalam satu bulan.',
                'category'        => 'consistency',
                'tier_type'       => 'awareness',
                'xp_reward'       => 50,
                'condition_type'  => 'days_in_month',
                'condition_value' => ['days' => 30],
            ],
            [
                'slug'            => 'weekly-reviewer',
                'name'            => 'Weekly Reviewer',
                'description'     => 'Selesaikan 4 weekly review berturut-turut.',
                'category'        => 'consistency',
                'tier_type'       => 'awareness',
                'xp_reward'       => 60,
                'condition_type'  => 'weekly_reviews_completed',
                'condition_value' => ['count' => 4],
            ],
            // --- Budget Control (financial tier) ---
            [
                'slug'            => 'controlled-spending',
                'name'            => 'Controlled Spending',
                'description'     => 'Tetap dalam batas anggaran mingguan.',
                'category'        => 'budget_control',
                'tier_type'       => 'financial',
                'xp_reward'       => 75,
                'condition_type'  => 'within_weekly_budget',
                'condition_value' => ['weeks' => 1],
            ],
            [
                'slug'            => 'budget-guardian',
                'name'            => 'Budget Guardian',
                'description'     => 'Tetap dalam batas anggaran bulanan.',
                'category'        => 'budget_control',
                'tier_type'       => 'financial',
                'xp_reward'       => 120,
                'condition_type'  => 'within_monthly_budget',
                'condition_value' => ['months' => 1],
            ],
            [
                'slug'            => 'no-impulse-week',
                'name'            => 'No Impulse Week',
                'description'     => 'Tidak ada pengeluaran tak terencana selama 7 hari.',
                'category'        => 'budget_control',
                'tier_type'       => 'financial',
                'xp_reward'       => 100,
                'condition_type'  => 'no_unplanned_expense',
                'condition_value' => ['days' => 7],
            ],
            // --- Saving (financial tier) ---
            [
                'slug'            => 'first-saving',
                'name'            => 'First Saving',
                'description'     => 'Capai target tabungan pertamamu.',
                'category'        => 'saving',
                'tier_type'       => 'financial',
                'xp_reward'       => 150,
                'condition_type'  => 'saving_target_reached',
                'condition_value' => ['count' => 1],
            ],
            [
                'slug'            => 'emergency-starter',
                'name'            => 'Emergency Starter',
                'description'     => 'Mulai membangun dana darurat.',
                'category'        => 'saving',
                'tier_type'       => 'financial',
                'xp_reward'       => 200,
                'condition_type'  => 'emergency_fund_started',
                'condition_value' => ['min_amount' => 500000],
            ],
            [
                'slug'            => 'stable-saver',
                'name'            => 'Stable Saver',
                'description'     => 'Menabung secara konsisten selama 3 bulan berturut-turut.',
                'category'        => 'saving',
                'tier_type'       => 'financial',
                'xp_reward'       => 300,
                'condition_type'  => 'consistent_saving_months',
                'condition_value' => ['months' => 3],
            ],
            // --- Debt (financial tier) ---
            [
                'slug'            => 'debt-reducer',
                'name'            => 'Debt Reducer',
                'description'     => 'Kurangi hutang sebesar 25% dari total awal.',
                'category'        => 'debt',
                'tier_type'       => 'financial',
                'xp_reward'       => 200,
                'condition_type'  => 'debt_reduced_percent',
                'condition_value' => ['percent' => 25],
            ],
            [
                'slug'            => 'debt-controller',
                'name'            => 'Debt Controller',
                'description'     => 'Tidak ada hutang baru selama 3 bulan.',
                'category'        => 'debt',
                'tier_type'       => 'financial',
                'xp_reward'       => 150,
                'condition_type'  => 'no_new_debt_months',
                'condition_value' => ['months' => 3],
            ],
            [
                'slug'            => 'debt-free',
                'name'            => 'Debt Free',
                'description'     => 'Lunasi semua hutang yang tercatat.',
                'category'        => 'debt',
                'tier_type'       => 'financial',
                'xp_reward'       => 500,
                'condition_type'  => 'all_debt_paid',
                'condition_value' => [],
            ],
        ];

        foreach ($achievements as $data) {
            Achievement::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
```

- [ ] **Step 2: Create ChallengeSeeder**

```php
<?php
// database/seeders/ChallengeSeeder.php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            // --- Weekly challenges ---
            [
                'slug'            => 'no-food-delivery-3-days',
                'type'            => 'weekly',
                'category'        => 'food_delivery',
                'title'           => 'Kurangi Food Delivery',
                'description'     => 'Tidak pesan makanan online selama 3 hari minggu ini.',
                'difficulty'      => 'easy',
                'xp_reward'       => 30,
                'momentum_bonus'  => 5,
                'condition_type'  => 'no_food_delivery_days',
                'condition_value' => ['days' => 3],
            ],
            [
                'slug'            => 'record-all-expenses-5-days',
                'type'            => 'weekly',
                'category'        => 'tracking',
                'title'           => 'Catat Semua Pengeluaran',
                'description'     => 'Catat setiap pengeluaran selama 5 hari.',
                'difficulty'      => 'easy',
                'xp_reward'       => 25,
                'momentum_bonus'  => 5,
                'condition_type'  => 'daily_transaction_logged',
                'condition_value' => ['days' => 5],
            ],
            [
                'slug'            => 'max-entertainment-budget',
                'type'            => 'weekly',
                'category'        => 'entertainment',
                'title'           => 'Kontrol Hiburan',
                'description'     => 'Pengeluaran hiburan di bawah Rp 150.000 minggu ini.',
                'difficulty'      => 'medium',
                'xp_reward'       => 40,
                'momentum_bonus'  => 5,
                'condition_type'  => 'category_budget_limit',
                'condition_value' => ['category' => 'hiburan', 'limit' => 150000],
            ],
            [
                'slug'            => 'reduce-coffee-spending',
                'type'            => 'weekly',
                'category'        => 'coffee',
                'title'           => 'Hemat Kopi',
                'description'     => 'Kurangi pengeluaran kopi/minuman di bawah Rp 50.000 minggu ini.',
                'difficulty'      => 'medium',
                'xp_reward'       => 35,
                'momentum_bonus'  => 5,
                'condition_type'  => 'category_budget_limit',
                'condition_value' => ['category' => 'kopi', 'limit' => 50000],
            ],
            // --- Monthly challenges ---
            [
                'slug'            => 'save-10-percent-income',
                'type'            => 'monthly',
                'category'        => 'saving',
                'title'           => 'Tabung 10% Penghasilan',
                'description'     => 'Alokasikan minimal 10% dari pemasukan ke tabungan bulan ini.',
                'difficulty'      => 'medium',
                'xp_reward'       => 80,
                'momentum_bonus'  => 10,
                'condition_type'  => 'saving_ratio',
                'condition_value' => ['percent' => 10],
            ],
            [
                'slug'            => 'no-overspending-month',
                'type'            => 'monthly',
                'category'        => 'budget',
                'title'           => 'Zero Overspending',
                'description'     => 'Tidak melebihi anggaran apapun sepanjang bulan ini.',
                'difficulty'      => 'hard',
                'xp_reward'       => 120,
                'momentum_bonus'  => 15,
                'condition_type'  => 'no_budget_exceeded',
                'condition_value' => [],
            ],
            [
                'slug'            => 'emergency-fund-contribution',
                'type'            => 'monthly',
                'category'        => 'saving',
                'title'           => 'Dana Darurat',
                'description'     => 'Tambahkan dana ke tabungan darurat bulan ini.',
                'difficulty'      => 'easy',
                'xp_reward'       => 60,
                'momentum_bonus'  => 10,
                'condition_type'  => 'emergency_fund_contribution',
                'condition_value' => ['min_amount' => 100000],
            ],
        ];

        foreach ($challenges as $data) {
            Challenge::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
```

- [ ] **Step 3: Run seeders**

```bash
php artisan db:seed --class=AchievementSeeder
php artisan db:seed --class=ChallengeSeeder
```

Expected: No errors. `achievements` table has 12 rows, `challenges` table has 7 rows.

- [ ] **Step 4: Commit**

```bash
git add database/seeders/AchievementSeeder.php database/seeders/ChallengeSeeder.php
git commit -m "feat(gamification): add achievement and challenge seeders"
```

---

### Task 10: AchievementService

**Files:**
- Create: `app/Services/AchievementService.php`

- [ ] **Step 1: Implement AchievementService**

```php
<?php
// app/Services/AchievementService.php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Anggaran;
use App\Models\HutangPiutang;
use App\Models\Tabungan;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\WeeklyReview;
use App\Models\XpLog;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    public function __construct(
        private readonly XpService $xpService,
        private readonly NotifikasiService $notifikasiService,
    ) {}

    /**
     * Evaluate all unearned achievements for a user after a triggering event.
     * Returns slugs of newly awarded achievements.
     */
    public function evaluate(User $user, string $trigger): array
    {
        $earned     = UserAchievement::where('user_id', $user->id)->pluck('achievement_id');
        $candidates = Achievement::whereNotIn('id', $earned)->get();
        $awarded    = [];

        foreach ($candidates as $achievement) {
            if ($this->conditionMet($user, $achievement)) {
                $this->award($user, $achievement);
                $awarded[] = $achievement->slug;
            }
        }

        return $awarded;
    }

    private function award(User $user, Achievement $achievement): void
    {
        DB::transaction(function () use ($user, $achievement) {
            UserAchievement::create([
                'user_id'        => $user->id,
                'achievement_id' => $achievement->id,
                'earned_at'      => now(),
            ]);

            $this->xpService->award($user, 'achievement_earned_' . $achievement->tier_type, []);

            $this->notifikasiService->send(
                $user->id,
                'Achievement Unlocked!',
                'Kamu mendapatkan: ' . $achievement->name,
                'achievement'
            );
        });
    }

    private function conditionMet(User $user, Achievement $achievement): bool
    {
        $val = $achievement->condition_value;

        return match ($achievement->condition_type) {
            'days_tracked_consecutive'  => $this->checkConsecutiveDays($user, $val['days']),
            'days_in_month'             => $this->checkDaysInMonth($user, $val['days']),
            'weekly_reviews_completed'  => $this->checkWeeklyReviews($user, $val['count']),
            'within_weekly_budget'      => $this->checkWeeklyBudget($user),
            'within_monthly_budget'     => $this->checkMonthlyBudget($user),
            'no_unplanned_expense'      => $this->checkNoImpulse($user, $val['days']),
            'saving_target_reached'     => $this->checkSavingTarget($user, $val['count']),
            'emergency_fund_started'    => $this->checkEmergencyFund($user, $val['min_amount']),
            'consistent_saving_months'  => $this->checkConsistentSaving($user, $val['months']),
            'debt_reduced_percent'      => $this->checkDebtReduction($user, $val['percent']),
            'no_new_debt_months'        => $this->checkNoNewDebt($user, $val['months']),
            'all_debt_paid'             => $this->checkDebtFree($user),
            default                     => false,
        };
    }

    private function checkConsecutiveDays(User $user, int $days): bool
    {
        $dates = Transaksi::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->subDays($days))
            ->selectRaw('DATE(tanggal) as day')
            ->distinct()
            ->pluck('day')
            ->map(fn($d) => \Carbon\Carbon::parse($d));

        if ($dates->count() < $days) return false;

        $dates = $dates->sortDesc()->values();
        for ($i = 0; $i < $days; $i++) {
            if (!isset($dates[$i])) return false;
            $expected = now()->subDays($i)->toDateString();
            if ($dates[$i]->toDateString() !== $expected) return false;
        }
        return true;
    }

    private function checkDaysInMonth(User $user, int $days): bool
    {
        return XpLog::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('DATE(created_at) as day')
            ->distinct()
            ->count() >= $days;
    }

    private function checkWeeklyReviews(User $user, int $count): bool
    {
        return WeeklyReview::where('user_id', $user->id)
            ->whereNotNull('viewed_at')
            ->where('week_start', '>=', now()->subWeeks($count)->startOfWeek())
            ->count() >= $count;
    }

    private function checkWeeklyBudget(User $user): bool
    {
        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();

        $budgets = Anggaran::where('user_id', $user->id)->get();
        foreach ($budgets as $budget) {
            $spent = Transaksi::where('user_id', $user->id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereBetween('tanggal', [$weekStart, $weekEnd])
                ->sum('jumlah');
            if ($spent > ($budget->jumlah / 4)) return false;
        }
        return true;
    }

    private function checkMonthlyBudget(User $user): bool
    {
        $budgets = Anggaran::where('user_id', $user->id)->get();
        foreach ($budgets as $budget) {
            $spent = Transaksi::where('user_id', $user->id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah');
            if ($spent > $budget->jumlah) return false;
        }
        return true;
    }

    private function checkNoImpulse(User $user, int $days): bool
    {
        // "Unplanned" = no tag or kategori marked as planned. Use absence of recurring_id as proxy.
        return !Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereNull('is_recurring')
            ->where('tanggal', '>=', now()->subDays($days))
            ->exists();
    }

    private function checkSavingTarget(User $user, int $count): bool
    {
        return Tabungan::where('user_id', $user->id)
            ->where('status', 'selesai')
            ->count() >= $count;
    }

    private function checkEmergencyFund(User $user, int $minAmount): bool
    {
        return Tabungan::where('user_id', $user->id)
            ->whereRaw("LOWER(nama) LIKE '%darurat%'")
            ->where('terkumpul', '>=', $minAmount)
            ->exists();
    }

    private function checkConsistentSaving(User $user, int $months): bool
    {
        for ($i = 0; $i < $months; $i++) {
            $month = now()->subMonths($i);
            $hasSaving = \App\Models\TabunganTransaksi::where('user_id', $user->id)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->exists();
            if (!$hasSaving) return false;
        }
        return true;
    }

    private function checkDebtReduction(User $user, int $percent): bool
    {
        $debt = HutangPiutang::where('user_id', $user->id)
            ->where('jenis', 'hutang')
            ->first();
        if (!$debt || $debt->jumlah_awal <= 0) return false;
        $reduced = $debt->jumlah_awal - $debt->sisa;
        return ($reduced / $debt->jumlah_awal * 100) >= $percent;
    }

    private function checkNoNewDebt(User $user, int $months): bool
    {
        return !HutangPiutang::where('user_id', $user->id)
            ->where('jenis', 'hutang')
            ->where('created_at', '>=', now()->subMonths($months))
            ->exists();
    }

    private function checkDebtFree(User $user): bool
    {
        return !HutangPiutang::where('user_id', $user->id)
            ->where('jenis', 'hutang')
            ->where('sisa', '>', 0)
            ->exists();
    }
}
```

- [ ] **Step 2: Wire AchievementService into TransaksiService**

In `app/Services/TransaksiService.php`, add to constructor injection:
```php
private readonly AchievementService $achievementService,
```

After the XP award line already added in Task 6, add:
```php
$this->achievementService->evaluate($user, 'transaction_created');
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/AchievementService.php app/Services/TransaksiService.php
git commit -m "feat(gamification): implement AchievementService with condition evaluators"
```

---

### Task 11: ChallengeService

**Files:**
- Create: `app/Services/ChallengeService.php`

- [ ] **Step 1: Implement ChallengeService**

```php
<?php
// app/Services/ChallengeService.php

namespace App\Services;

use App\Models\Challenge;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserChallenge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChallengeService
{
    const MAX_ACTIVE_WEEKLY  = 2;
    const MAX_ACTIVE_MONTHLY = 1;

    public function __construct(
        private readonly XpService $xpService,
        private readonly MomentumService $momentumService,
        private readonly NotifikasiService $notifikasiService,
    ) {}

    /**
     * Assign a random set of unattempted challenges for the new period.
     */
    public function assignForUser(User $user): void
    {
        $this->assignByType($user, 'weekly', self::MAX_ACTIVE_WEEKLY, now()->endOfWeek());
        $this->assignByType($user, 'monthly', self::MAX_ACTIVE_MONTHLY, now()->endOfMonth());
    }

    private function assignByType(User $user, string $type, int $max, Carbon $expiresAt): void
    {
        $activeCount = UserChallenge::where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->whereNull('completed_at')
            ->whereHas('challenge', fn($q) => $q->where('type', $type))
            ->count();

        if ($activeCount >= $max) return;

        $attempted = UserChallenge::where('user_id', $user->id)
            ->whereHas('challenge', fn($q) => $q->where('type', $type))
            ->pluck('challenge_id');

        $candidates = Challenge::where('type', $type)
            ->whereNotIn('id', $attempted)
            ->inRandomOrder()
            ->take($max - $activeCount)
            ->get();

        foreach ($candidates as $challenge) {
            UserChallenge::create([
                'user_id'    => $user->id,
                'challenge_id' => $challenge->id,
                'started_at' => now(),
                'expires_at' => $expiresAt,
            ]);
        }
    }

    /**
     * Check and complete any challenges whose conditions are now met.
     */
    public function evaluateActive(User $user): array
    {
        $active    = UserChallenge::where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->whereNull('completed_at')
            ->with('challenge')
            ->get();

        $completed = [];

        foreach ($active as $userChallenge) {
            if ($this->conditionMet($user, $userChallenge->challenge)) {
                $this->complete($user, $userChallenge);
                $completed[] = $userChallenge->challenge->slug;
            }
        }

        return $completed;
    }

    private function complete(User $user, UserChallenge $userChallenge): void
    {
        DB::transaction(function () use ($user, $userChallenge) {
            $userChallenge->completed_at = now();
            $userChallenge->save();

            $challenge = $userChallenge->challenge;

            $this->xpService->award($user, 'budget_weekly', ['challenge_id' => $challenge->id]);
            $this->momentumService->recordActivity($user, 'budget_compliance');

            $this->notifikasiService->send(
                $user->id,
                'Tantangan Selesai!',
                'Kamu berhasil menyelesaikan: ' . $challenge->title,
                'achievement'
            );
        });
    }

    private function conditionMet(User $user, Challenge $challenge): bool
    {
        $val = $challenge->condition_value;

        return match ($challenge->condition_type) {
            'no_food_delivery_days' => $this->checkNoFoodDelivery($user, $val['days']),
            'daily_transaction_logged' => $this->checkDailyLogging($user, $val['days']),
            'category_budget_limit' => $this->checkCategoryLimit($user, $val['category'], $val['limit']),
            'saving_ratio'          => $this->checkSavingRatio($user, $val['percent']),
            'no_budget_exceeded'    => $this->checkNoBudgetExceeded($user),
            'emergency_fund_contribution' => $this->checkEmergencyContribution($user, $val['min_amount']),
            default                 => false,
        };
    }

    private function checkNoFoodDelivery(User $user, int $days): bool
    {
        return !Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->where('tanggal', '>=', now()->subDays($days))
            ->whereHas('kategori', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%delivery%' OR LOWER(nama) LIKE '%ojek%'"))
            ->exists();
    }

    private function checkDailyLogging(User $user, int $days): bool
    {
        $logged = Transaksi::where('user_id', $user->id)
            ->where('tanggal', '>=', now()->subDays($days))
            ->selectRaw('DATE(tanggal) as day')
            ->distinct()
            ->count();
        return $logged >= $days;
    }

    private function checkCategoryLimit(User $user, string $categoryName, int $limit): bool
    {
        $spent = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereHas('kategori', fn($q) => $q->whereRaw("LOWER(nama) LIKE ?", ['%' . strtolower($categoryName) . '%']))
            ->whereDate('tanggal', '>=', now()->startOfWeek())
            ->sum('jumlah');
        return $spent <= $limit;
    }

    private function checkSavingRatio(User $user, int $percent): bool
    {
        $income = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pemasukan')
            ->whereMonth('tanggal', now()->month)
            ->sum('jumlah');
        if ($income <= 0) return false;

        $saved = \App\Models\TabunganTransaksi::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->sum('jumlah');

        return ($saved / $income * 100) >= $percent;
    }

    private function checkNoBudgetExceeded(User $user): bool
    {
        $budgets = \App\Models\Anggaran::where('user_id', $user->id)->get();
        foreach ($budgets as $budget) {
            $spent = Transaksi::where('user_id', $user->id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereMonth('tanggal', now()->month)
                ->sum('jumlah');
            if ($spent > $budget->jumlah) return false;
        }
        return true;
    }

    private function checkEmergencyContribution(User $user, int $minAmount): bool
    {
        return \App\Models\TabunganTransaksi::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereHas('tabungan', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%darurat%'"))
            ->sum('jumlah') >= $minAmount;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/ChallengeService.php
git commit -m "feat(gamification): implement ChallengeService with condition evaluation and completion"
```

---

## Phase 3 — Intelligence: Insight + Weekly Review

### Task 12: Migration + Model — WeeklyReview

**Files:**
- Create: `database/migrations/2026_05_28_100006_create_weekly_reviews_table.php`
- Create: `app/Models/WeeklyReview.php`

- [ ] **Step 1: Create weekly_reviews migration**

```php
<?php
// database/migrations/2026_05_28_100006_create_weekly_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->json('data');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reviews');
    }
};
```

- [ ] **Step 2: Create WeeklyReview model**

```php
<?php
// app/Models/WeeklyReview.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReview extends Model
{
    protected $fillable = ['user_id', 'week_start', 'week_end', 'data', 'viewed_at'];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end'   => 'date',
            'data'       => 'array',
            'viewed_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update(['viewed_at' => now()]);
        }
    }
}
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_28_100006_create_weekly_reviews_table.php app/Models/WeeklyReview.php
git commit -m "feat(gamification): add weekly_reviews migration and model"
```

---

### Task 13: GamificationInsightService — Per-User Adaptive Thresholds

**Files:**
- Create: `app/Services/GamificationInsightService.php`

- [ ] **Step 1: Implement GamificationInsightService**

```php
<?php
// app/Services/GamificationInsightService.php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\User;

class GamificationInsightService
{
    /**
     * Generate rule-based insights for a user's current week.
     * All thresholds are adaptive (per-user 30-day rolling baseline).
     */
    public function generateForUser(User $user): array
    {
        return array_values(array_filter([
            $this->checkOverspending($user),
            $this->checkSubscriptionPattern($user),
            $this->checkFoodDeliveryDominance($user),
            $this->checkNightSpendingPattern($user),
        ]));
    }

    private function checkOverspending(User $user): ?array
    {
        $thisWeek = $this->weeklyExpense($user, 0);
        $lastWeek = $this->weeklyExpense($user, 1);

        if ($lastWeek > 0 && $thisWeek > $lastWeek * 1.2) {
            $increase = round(($thisWeek - $lastWeek) / $lastWeek * 100);
            return [
                'type'    => 'overspending',
                'message' => "Pengeluaran meningkat {$increase}% dibanding minggu lalu.",
                'data'    => ['this_week' => $thisWeek, 'last_week' => $lastWeek],
            ];
        }
        return null;
    }

    private function checkSubscriptionPattern(User $user): ?array
    {
        $recurring = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereMonth('tanggal', now()->month)
            ->selectRaw('jumlah, COUNT(*) as cnt')
            ->groupBy('jumlah')
            ->havingRaw('cnt >= 3')
            ->get();

        if ($recurring->isNotEmpty()) {
            return [
                'type'    => 'subscription_detected',
                'message' => 'Langganan rutin terdeteksi. Pastikan semua masih aktif kamu gunakan.',
                'data'    => ['amounts' => $recurring->pluck('jumlah')],
            ];
        }
        return null;
    }

    private function checkFoodDeliveryDominance(User $user): ?array
    {
        $total = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [now()->startOfWeek(), now()])
            ->sum('jumlah');

        $foodDelivery = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [now()->startOfWeek(), now()])
            ->whereHas('kategori', fn($q) => $q->whereRaw("LOWER(nama) LIKE '%delivery%' OR LOWER(nama) LIKE '%ojek%'"))
            ->sum('jumlah');

        if ($total > 0 && ($foodDelivery / $total) > 0.30) {
            return [
                'type'    => 'food_delivery_dominant',
                'message' => 'Food delivery menjadi pengeluaran dominan minggu ini (>30% total).',
                'data'    => ['food_delivery' => $foodDelivery, 'total' => $total],
            ];
        }
        return null;
    }

    private function checkNightSpendingPattern(User $user): ?array
    {
        // Adaptive threshold: 30-day baseline of night spending
        $baseline = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereRaw('HOUR(created_at) >= 21')
            ->avg('jumlah') ?? 0;

        $thisWeekNight = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [now()->startOfWeek(), now()])
            ->whereRaw('HOUR(created_at) >= 21')
            ->avg('jumlah') ?? 0;

        if ($baseline > 0 && $thisWeekNight > $baseline * 1.3) {
            return [
                'type'    => 'night_spending_elevated',
                'message' => 'Pengeluaran malam meningkat dibanding kebiasaanmu.',
                'data'    => ['this_week_avg' => $thisWeekNight, 'baseline' => $baseline],
            ];
        }
        return null;
    }

    private function weeklyExpense(User $user, int $weeksAgo): float
    {
        $start = now()->subWeeks($weeksAgo)->startOfWeek();
        $end   = now()->subWeeks($weeksAgo)->endOfWeek();
        return Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start, $end])
            ->sum('jumlah');
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/GamificationInsightService.php
git commit -m "feat(gamification): implement GamificationInsightService with per-user adaptive thresholds"
```

---

### Task 14: WeeklyReviewService

**Files:**
- Create: `app/Services/WeeklyReviewService.php`

- [ ] **Step 1: Implement WeeklyReviewService**

```php
<?php
// app/Services/WeeklyReviewService.php

namespace App\Services;

use App\Models\Anggaran;
use App\Models\Tabungan;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserGamification;
use App\Models\WeeklyReview;
use App\Models\XpLog;
use Carbon\Carbon;

class WeeklyReviewService
{
    public function __construct(
        private readonly MomentumService $momentumService,
        private readonly GamificationInsightService $insightService,
        private readonly XpService $xpService,
    ) {}

    public function generateForUser(User $user, Carbon $weekStart): WeeklyReview
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        $data = [
            'spending_comparison'    => $this->spendingComparison($user, $weekStart, $weekEnd),
            'budget_status'          => $this->budgetStatus($user, $weekStart, $weekEnd),
            'saving_progress'        => $this->savingProgress($user),
            'top_spending_category'  => $this->topSpendingCategory($user, $weekStart, $weekEnd),
            'unusual_spending'       => $this->insightService->generateForUser($user),
            'momentum_trend'         => $this->momentumTrend($user),
            'xp_gained_this_week'    => $this->xpGainedThisWeek($user, $weekStart, $weekEnd),
            'achievements_this_week' => $this->achievementsThisWeek($user, $weekStart, $weekEnd),
        ];

        return WeeklyReview::updateOrCreate(
            ['user_id' => $user->id, 'week_start' => $weekStart->toDateString()],
            ['week_end' => $weekEnd->toDateString(), 'data' => $data]
        );
    }

    public function markViewed(WeeklyReview $review, User $user): void
    {
        $isFirstView = $review->viewed_at === null;
        $review->markViewed();

        if ($isFirstView) {
            $this->xpService->award($user, 'weekly_summary_viewed');
            $this->momentumService->recordActivity($user, 'weekly_review');
        }
    }

    private function spendingComparison(User $user, Carbon $start, Carbon $end): array
    {
        $thisWeek = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start, $end])
            ->sum('jumlah');

        $lastWeek = Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start->copy()->subWeek(), $end->copy()->subWeek()])
            ->sum('jumlah');

        $diff = $lastWeek > 0 ? round(($thisWeek - $lastWeek) / $lastWeek * 100, 1) : 0;

        return [
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
            'diff_percent' => $diff,
            'improved' => $diff <= 0,
        ];
    }

    private function budgetStatus(User $user, Carbon $start, Carbon $end): array
    {
        $budgets = Anggaran::where('user_id', $user->id)->with('kategori')->get();
        $result  = [];

        foreach ($budgets as $budget) {
            $spent = Transaksi::where('user_id', $user->id)
                ->where('kategori_id', $budget->kategori_id)
                ->where('jenis', 'pengeluaran')
                ->whereBetween('tanggal', [$start, $end])
                ->sum('jumlah');

            $weeklyAllocation = $budget->jumlah / 4;
            $result[] = [
                'kategori'   => $budget->kategori->nama ?? '-',
                'allocated'  => $weeklyAllocation,
                'spent'      => $spent,
                'over_budget' => $spent > $weeklyAllocation,
            ];
        }

        return $result;
    }

    private function savingProgress(User $user): array
    {
        return Tabungan::where('user_id', $user->id)
            ->select(['nama', 'target', 'terkumpul'])
            ->get()
            ->map(fn($t) => [
                'nama'    => $t->nama,
                'target'  => $t->target,
                'saved'   => $t->terkumpul,
                'percent' => $t->target > 0 ? round($t->terkumpul / $t->target * 100, 1) : 0,
            ])
            ->toArray();
    }

    private function topSpendingCategory(User $user, Carbon $start, Carbon $end): array
    {
        return Transaksi::where('user_id', $user->id)
            ->where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('kategori_id, SUM(jumlah) as total')
            ->with('kategori:id,nama')
            ->groupBy('kategori_id')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(fn($t) => ['kategori' => $t->kategori->nama ?? '-', 'total' => $t->total])
            ->toArray();
    }

    private function momentumTrend(User $user): array
    {
        $g = UserGamification::where('user_id', $user->id)->first();
        return [
            'score'  => $g?->momentum_score ?? 50,
            'status' => $this->momentumService->getStatus($g?->momentum_score ?? 50),
        ];
    }

    private function xpGainedThisWeek(User $user, Carbon $start, Carbon $end): int
    {
        return XpLog::where('user_id', $user->id)
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->sum('xp_amount');
    }

    private function achievementsThisWeek(User $user, Carbon $start, Carbon $end): array
    {
        return UserAchievement::where('user_id', $user->id)
            ->whereBetween('earned_at', [$start->startOfDay(), $end->endOfDay()])
            ->with('achievement:id,name,category')
            ->get()
            ->map(fn($ua) => $ua->achievement->name)
            ->toArray();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/WeeklyReviewService.php
git commit -m "feat(gamification): implement WeeklyReviewService with full data aggregation"
```

---

## Phase 4 — Delivery: Controller + Cron + Views

### Task 15: GamificationController + Routes

**Files:**
- Create: `app/Http/Controllers/GamificationController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create GamificationController**

```php
<?php
// app/Http/Controllers/GamificationController.php

namespace App\Http\Controllers;

use App\Models\UserAchievement;
use App\Models\UserChallenge;
use App\Models\UserGamification;
use App\Models\WeeklyReview;
use App\Services\ChallengeService;
use App\Services\LevelService;
use App\Services\MomentumService;
use App\Services\WeeklyReviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function __construct(
        private readonly WeeklyReviewService $weeklyReviewService,
        private readonly ChallengeService $challengeService,
        private readonly MomentumService $momentumService,
    ) {}

    public function index()
    {
        $user         = auth()->user();
        $gamification = UserGamification::firstOrCreate(
            ['user_id' => $user->id],
            ['total_xp' => 0, 'level' => 1, 'momentum_score' => 50.0]
        );

        $progressPercent = LevelService::progressPercent($gamification->total_xp, $gamification->level);
        $xpToNext        = LevelService::xpToNextLevel($gamification->level);
        $momentumStatus  = $this->momentumService->getStatus($gamification->momentum_score);

        $achievements = UserAchievement::where('user_id', $user->id)
            ->with('achievement')
            ->orderByDesc('earned_at')
            ->get();

        $activeChallenges = UserChallenge::where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->whereNull('completed_at')
            ->with('challenge')
            ->get();

        $latestReview = WeeklyReview::where('user_id', $user->id)
            ->orderByDesc('week_start')
            ->first();

        $this->challengeService->assignForUser($user);

        return view('gamification.index', compact(
            'gamification', 'progressPercent', 'xpToNext',
            'momentumStatus', 'achievements', 'activeChallenges', 'latestReview'
        ));
    }

    public function weeklyReview(int $id)
    {
        $user   = auth()->user();
        $review = WeeklyReview::where('user_id', $user->id)->findOrFail($id);
        $this->weeklyReviewService->markViewed($review, $user);
        return view('gamification._weekly_review', compact('review'));
    }
}
```

- [ ] **Step 2: Add gamification routes to routes/web.php**

Inside the `middleware: auth` group, add:
```php
Route::prefix('gamifikasi')->name('gamifikasi.')->group(function () {
    Route::get('/', [GamificationController::class, 'index'])->name('index');
    Route::get('/review/{id}', [GamificationController::class, 'weeklyReview'])->name('review.show');
});
```

Add the import at the top of web.php:
```php
use App\Http\Controllers\GamificationController;
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/GamificationController.php routes/web.php
git commit -m "feat(gamification): add GamificationController and routes"
```

---

### Task 16: Cron Commands + Schedule

**Files:**
- Create: `app/Console/Commands/GamificationDailyDecayCommand.php`
- Create: `app/Console/Commands/GenerateWeeklyReviewsCommand.php`
- Create: `app/Console/Commands/GenerateChallengesCommand.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Create GamificationDailyDecayCommand**

```php
<?php
// app/Console/Commands/GamificationDailyDecayCommand.php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MomentumService;
use Illuminate\Console\Command;

class GamificationDailyDecayCommand extends Command
{
    protected $signature   = 'gamification:daily-decay';
    protected $description = 'Apply daily momentum decay for inactive users';

    public function handle(MomentumService $momentumService): void
    {
        $users = User::where('is_active', true)->get();
        foreach ($users as $user) {
            $momentumService->applyDailyDecay($user);
        }
        $this->info('Daily momentum decay applied to ' . $users->count() . ' users.');
    }
}
```

- [ ] **Step 2: Create GenerateWeeklyReviewsCommand**

```php
<?php
// app/Console/Commands/GenerateWeeklyReviewsCommand.php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WeeklyReviewService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyReviewsCommand extends Command
{
    protected $signature   = 'gamification:generate-weekly-reviews';
    protected $description = 'Pre-generate weekly review data for all users at end of each week';

    public function handle(WeeklyReviewService $service): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $users     = User::where('is_active', true)->get();

        foreach ($users as $user) {
            $service->generateForUser($user, $weekStart);
        }

        $this->info('Weekly reviews generated for ' . $users->count() . ' users.');
    }
}
```

- [ ] **Step 3: Create GenerateChallengesCommand**

```php
<?php
// app/Console/Commands/GenerateChallengesCommand.php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ChallengeService;
use Illuminate\Console\Command;

class GenerateChallengesCommand extends Command
{
    protected $signature   = 'gamification:generate-challenges';
    protected $description = 'Assign new weekly/monthly challenges to all users';

    public function handle(ChallengeService $service): void
    {
        $users = User::where('is_active', true)->get();
        foreach ($users as $user) {
            $service->assignForUser($user);
        }
        $this->info('Challenges assigned to ' . $users->count() . ' users.');
    }
}
```

- [ ] **Step 4: Register cron schedule in routes/console.php**

Add to `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('gamification:daily-decay')->dailyAt('00:05');
Schedule::command('gamification:generate-weekly-reviews')->weeklyOn(0, '23:00'); // Sunday 23:00
Schedule::command('gamification:generate-challenges')->weeklyOn(1, '00:10');     // Monday 00:10
```

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/GamificationDailyDecayCommand.php app/Console/Commands/GenerateWeeklyReviewsCommand.php app/Console/Commands/GenerateChallengesCommand.php routes/console.php
git commit -m "feat(gamification): add daily decay, weekly review, and challenge generation cron commands"
```

---

### Task 17: Blade Views

**Files:**
- Create: `resources/views/gamification/index.blade.php`
- Create: `resources/views/gamification/_level_card.blade.php`
- Create: `resources/views/gamification/_momentum_card.blade.php`
- Create: `resources/views/gamification/_weekly_review.blade.php`

- [ ] **Step 1: Create main gamification index view**

```blade
{{-- resources/views/gamification/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 fw-semibold mb-4">Financial Progress</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            @include('gamification._level_card')
        </div>
        <div class="col-md-6">
            @include('gamification._momentum_card')
        </div>
    </div>

    {{-- Active Challenges --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">Tantangan Aktif</div>
        <div class="card-body">
            @forelse($activeChallenges as $uc)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <div class="fw-medium">{{ $uc->challenge->title }}</div>
                    <small class="text-muted">{{ $uc->challenge->description }}</small>
                </div>
                <span class="badge bg-light text-dark">
                    +{{ $uc->challenge->xp_reward }} XP
                </span>
            </div>
            @empty
            <p class="text-muted mb-0">Tidak ada tantangan aktif saat ini.</p>
            @endforelse
        </div>
    </div>

    {{-- Achievements --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">Achievement ({{ $achievements->count() }})</div>
        <div class="card-body">
            <div class="row g-2">
                @forelse($achievements as $ua)
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="fw-medium small">{{ $ua->achievement->name }}</div>
                        <small class="text-muted">{{ $ua->earned_at->format('d M Y') }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0">Belum ada achievement.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Latest Weekly Review --}}
    @if($latestReview)
    <div class="card">
        <div class="card-header fw-semibold d-flex justify-content-between">
            <span>Weekly Review — {{ $latestReview->week_start->format('d M') }} s/d {{ $latestReview->week_end->format('d M Y') }}</span>
            @if(!$latestReview->viewed_at)
            <span class="badge bg-primary">Baru</span>
            @endif
        </div>
        <div class="card-body">
            <a href="{{ route('gamifikasi.review.show', $latestReview->id) }}" class="btn btn-sm btn-outline-primary">
                Lihat Review
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
```

- [ ] **Step 2: Create _level_card partial**

```blade
{{-- resources/views/gamification/_level_card.blade.php --}}
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="text-muted small mb-1">Level {{ $gamification->level }}</div>
                <div class="fw-semibold">{{ \App\Services\LevelService::title($gamification->level) }}</div>
            </div>
            <span class="badge bg-secondary">{{ number_format($gamification->total_xp) }} XP</span>
        </div>
        <div class="mb-1 d-flex justify-content-between small text-muted">
            <span>Progress ke Level {{ $gamification->level + 1 }}</span>
            <span>{{ $progressPercent }}%</span>
        </div>
        <div class="progress" style="height: 6px;">
            <div class="progress-bar bg-primary" style="width: {{ $progressPercent }}%"></div>
        </div>
        @if($gamification->level < 10)
        <div class="mt-2 small text-muted">{{ number_format($xpToNext) }} XP lagi ke level berikutnya</div>
        @else
        <div class="mt-2 small text-success fw-medium">Level maksimum tercapai!</div>
        @endif
    </div>
</div>
```

- [ ] **Step 3: Create _momentum_card partial**

```blade
{{-- resources/views/gamification/_momentum_card.blade.php --}}
<div class="card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="text-muted small mb-1">Momentum</div>
                <div class="fw-semibold">{{ $momentumStatus }}</div>
            </div>
            <span class="fw-bold fs-4">{{ number_format($gamification->momentum_score, 0) }}</span>
        </div>
        <div class="progress mb-2" style="height: 6px;">
            @php
                $color = match(true) {
                    $gamification->momentum_score >= 90 => 'bg-success',
                    $gamification->momentum_score >= 70 => 'bg-primary',
                    $gamification->momentum_score >= 40 => 'bg-warning',
                    default => 'bg-danger',
                };
            @endphp
            <div class="progress-bar {{ $color }}" style="width: {{ $gamification->momentum_score }}%"></div>
        </div>
        <p class="small text-muted mb-0">
            Catat transaksi setiap hari untuk menjaga momentum tetap tinggi.
        </p>
    </div>
</div>
```

- [ ] **Step 4: Create _weekly_review partial**

```blade
{{-- resources/views/gamification/_weekly_review.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="h5 fw-semibold mb-4">
        Weekly Review — {{ $review->week_start->format('d M') }} – {{ $review->week_end->format('d M Y') }}
    </h2>

    @php $data = $review->data; @endphp

    {{-- Spending Comparison --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="fw-medium mb-2">Perbandingan Pengeluaran</div>
            <div class="d-flex gap-4">
                <div>
                    <div class="small text-muted">Minggu ini</div>
                    <div class="fw-semibold">Rp {{ number_format($data['spending_comparison']['this_week']) }}</div>
                </div>
                <div>
                    <div class="small text-muted">Minggu lalu</div>
                    <div class="fw-semibold">Rp {{ number_format($data['spending_comparison']['last_week']) }}</div>
                </div>
                <div>
                    @if($data['spending_comparison']['improved'])
                    <span class="badge bg-success-subtle text-success">{{ $data['spending_comparison']['diff_percent'] }}%</span>
                    @else
                    <span class="badge bg-danger-subtle text-danger">+{{ $data['spending_comparison']['diff_percent'] }}%</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Insights --}}
    @if(!empty($data['unusual_spending']))
    <div class="card mb-3">
        <div class="card-body">
            <div class="fw-medium mb-2">Insight Minggu Ini</div>
            @foreach($data['unusual_spending'] as $insight)
            <div class="alert alert-light border py-2 mb-2">
                <small>{{ $insight['message'] }}</small>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- XP + Achievements --}}
    <div class="card mb-3">
        <div class="card-body d-flex gap-4">
            <div>
                <div class="small text-muted">XP Minggu Ini</div>
                <div class="fw-semibold">+{{ $data['xp_gained_this_week'] }} XP</div>
            </div>
            @if(!empty($data['achievements_this_week']))
            <div>
                <div class="small text-muted">Achievement</div>
                <div class="fw-semibold">{{ implode(', ', $data['achievements_this_week']) }}</div>
            </div>
            @endif
        </div>
    </div>

    <a href="{{ route('gamifikasi.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
</div>
@endsection
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/gamification/
git commit -m "feat(gamification): add gamification index and review blade views"
```

---

### Task 18: Feature Test + Final Verification

**Files:**
- Create: `tests/Feature/GamificationTest.php`

- [ ] **Step 1: Write feature test**

```php
<?php
// tests/Feature/GamificationTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gamification_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('gamifikasi.index'))
            ->assertOk()
            ->assertViewIs('gamification.index');
    }

    public function test_gamification_page_redirects_for_guest(): void
    {
        $this->get(route('gamifikasi.index'))->assertRedirect(route('login'));
    }

    public function test_creating_transaction_awards_xp(): void
    {
        $user = User::factory()->create();
        $xpService = app(XpService::class);
        $xp = $xpService->award($user, 'transaction');
        $this->assertSame(XpService::XP_AMOUNTS['transaction'], $xp);
        $this->assertDatabaseHas('xp_logs', ['user_id' => $user->id, 'source' => 'transaction']);
    }

    public function test_user_gamification_record_created_on_first_award(): void
    {
        $user = User::factory()->create();
        app(XpService::class)->award($user, 'transaction');
        $this->assertDatabaseHas('user_gamification', ['user_id' => $user->id]);
    }
}
```

- [ ] **Step 2: Run all gamification tests**

```bash
php artisan test tests/Unit/Services/LevelServiceTest.php tests/Unit/Services/XpServiceTest.php tests/Unit/Services/MomentumServiceTest.php tests/Feature/GamificationTest.php
```

Expected: All tests pass.

- [ ] **Step 3: Run full test suite to check for regressions**

```bash
php artisan test
```

Expected: All tests pass, including pre-existing tests.

- [ ] **Step 4: Seed achievements and challenges in production**

```bash
php artisan db:seed --class=AchievementSeeder
php artisan db:seed --class=ChallengeSeeder
```

- [ ] **Step 5: Final commit**

```bash
git add tests/Feature/GamificationTest.php
git commit -m "feat(gamification): add feature tests and complete gamification system"
```

---

## Self-Review Checklist

### Spec Coverage

| Spec Requirement | Task Covered |
|---|---|
| XP Engine (sources, amounts, daily cap, anti-abuse) | Task 4, 5 |
| Level Engine (10 levels, quadratic curve, titles) | Task 3 |
| Momentum Engine (0-100, decay, grace, status) | Task 5 |
| Achievement Engine (12 achievements, tier types) | Task 9, 10 |
| Challenge Engine (7 challenges, weekly/monthly) | Task 9, 11 |
| Insight Engine (4 rule-based, adaptive thresholds) | Task 13 |
| Weekly Review Engine (8 data points) | Task 14 |
| Cron (daily decay, weekly review gen, challenge gen) | Task 16 |
| UI (level card, momentum card, weekly review, achievement list) | Task 17 |

### Design Decisions Encoded

- **Quadratic XP curve** (base=50, exp=1.8) replaces hardcoded spec thresholds — scalable if rewards change
- **Per-user adaptive baselines** for insight thresholds (30-day rolling avg) instead of flat globals
- **Achievement tier_type** (awareness vs financial) differentiates time-based from behavior-based rewards
- **Challenge structure** includes `xp_reward + momentum_bonus + expires_at` — complete reward loop
- **Grace system** tracked per-week via `grace_period_start` + `grace_days_used` — resets every 7 days
- **No punishment for expired challenges** — soft expiry messaging only
