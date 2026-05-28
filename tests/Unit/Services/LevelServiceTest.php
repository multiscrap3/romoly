<?php

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
