<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Global settings (household_id = null)
        $globalSettings = [
            [
                'household_id' => null,
                'key' => 'app_name',
                'value' => 'Romoly',
                'type' => 'string',
                'description' => 'Nama aplikasi',
            ],
            [
                'household_id' => null,
                'key' => 'app_version',
                'value' => '1.0.0',
                'type' => 'string',
                'description' => 'Versi aplikasi',
            ],
            [
                'household_id' => null,
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Mode maintenance',
            ],
            [
                'household_id' => null,
                'key' => 'registration_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Registrasi user baru diaktifkan',
            ],
            [
                'household_id' => null,
                'key' => 'default_currency',
                'value' => 'IDR',
                'type' => 'string',
                'description' => 'Mata uang default',
            ],
            [
                'household_id' => null,
                'key' => 'default_locale',
                'value' => 'id',
                'type' => 'string',
                'description' => 'Bahasa default',
            ],
            [
                'household_id' => null,
                'key' => 'default_timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'description' => 'Timezone default',
            ],
        ];

        foreach ($globalSettings as $setting) {
            Setting::create($setting);
        }

        // Household-specific settings akan dibuat otomatis saat household dibuat
        // via observer atau service
    }
}
