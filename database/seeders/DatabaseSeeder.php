<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PlanSeeder::class,
            SettingSeeder::class,
            AdminUserSeeder::class,
            KategoriDefaultSeeder::class,
            AchievementSeeder::class,
            ChallengeSeeder::class,
        ]);
    }
}
