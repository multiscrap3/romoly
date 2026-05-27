<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view transaksi', 'create transaksi', 'edit transaksi', 'delete transaksi',
            'view anggaran', 'create anggaran', 'edit anggaran', 'delete anggaran',
            'view tabungan', 'create tabungan', 'edit tabungan', 'delete tabungan',
            'view hutang-piutang', 'create hutang-piutang', 'edit hutang-piutang', 'delete hutang-piutang',
            'view laporan',
            'manage members',
            'manage roles',
            'manage household settings',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'superadmin' => Permission::all()->pluck('name')->toArray(),
            'owner'      => Permission::all()->pluck('name')->toArray(),
            'admin'      => Permission::whereNotIn('name', ['manage roles', 'manage household settings'])
                               ->pluck('name')->toArray(),
            'analyst'    => ['view transaksi', 'view anggaran', 'view tabungan', 'view hutang-piutang', 'view laporan'],
            'member'     => [
                'view transaksi', 'create transaksi', 'edit transaksi', 'delete transaksi',
                'view anggaran', 'create anggaran', 'edit anggaran', 'delete anggaran',
                'view tabungan', 'create tabungan', 'edit tabungan', 'delete tabungan',
                'view hutang-piutang', 'create hutang-piutang', 'edit hutang-piutang', 'delete hutang-piutang',
            ],
            'viewer'     => ['view transaksi', 'view anggaran', 'view tabungan', 'view hutang-piutang'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
