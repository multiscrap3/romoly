<?php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            // --- Weekly ---
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
                'description'     => 'Pengeluaran kopi/minuman di bawah Rp 50.000 minggu ini.',
                'difficulty'      => 'medium',
                'xp_reward'       => 35,
                'momentum_bonus'  => 5,
                'condition_type'  => 'category_budget_limit',
                'condition_value' => ['category' => 'kopi', 'limit' => 50000],
            ],
            // --- Monthly ---
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
