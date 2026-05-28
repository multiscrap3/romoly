<?php

namespace App\Services;

class LevelService
{
    const MAX_LEVEL   = 10;
    const XP_BASE     = 50;
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
