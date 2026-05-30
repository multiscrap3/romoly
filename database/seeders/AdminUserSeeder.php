<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $plan = Plan::where('slug', 'premium')->first()
            ?? Plan::where('slug', 'free')->first()
            ?? Plan::first();

        $household = Household::firstOrCreate(
            ['slug' => 'rumah-admin'],
            [
                'nama' => 'Rumah Admin',
                'plan_id' => $plan?->id,
                'subscription_start' => now()->toDateString(),
                'subscription_end' => null,
                'status' => 'active',
            ]
        );

        if ($plan && ! $household->plan_id) {
            $household->update(['plan_id' => $plan->id]);
        }

        $user = User::updateOrCreate(
            ['email' => 'admin@finanku.test'],
            [
                'name' => 'Admin Romoly',
                'password' => 'password',
                'household_id' => $household->id,
                'is_active' => true,
            ]
        );

        if (!$user->hasRole('superadmin')) {
            $user->assignRole('superadmin');
        }
    }
}