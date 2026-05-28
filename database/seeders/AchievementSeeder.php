<?php

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
