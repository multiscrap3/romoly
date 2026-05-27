<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $users = DB::table('users')->whereNotNull('role')->get();

        foreach ($users as $user) {
            $roleName = match ($user->role) {
                'owner'      => 'owner',
                'admin'      => 'admin',
                'member'     => 'member',
                'superadmin' => 'superadmin',
                default      => 'member',
            };

            $role = Role::findByName($roleName, 'web');
            if (!$role) {
                continue;
            }

            $alreadyHas = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('role_id', $role->id)
                ->where('model_type', 'App\\Models\\User')
                ->exists();

            if (!$alreadyHas) {
                DB::table('model_has_roles')->insert([
                    'role_id'    => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id'   => $user->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Rollback tidak diperlukan — data model_has_roles akan bersih jika tabel di-drop
    }
};
