# RBAC Multi-Role Per-Household Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrasi Finanku dari single-role enum ke RBAC multi-role per-user menggunakan Spatie Laravel Permission v6, sehingga 1 user bisa memiliki banyak role sekaligus.

**Architecture:** Install Spatie Laravel Permission tanpa fitur Teams (karena user saat ini hanya bisa berada di 1 household — Teams hanya relevan jika user bisa bergabung ke banyak household sekaligus). Definisikan 6 role global (superadmin, owner, admin, analyst, member, viewer) dengan permission granular per fitur. Middleware dan views diperbarui untuk menggunakan API Spatie (`hasRole()`, `can()`). Data lama dimigrasikan via artisan command, lalu kolom `role` lama dihapus.

**Tech Stack:** Laravel 11, Spatie Laravel Permission v6, PHP 8.3, MySQL, Bootstrap 5 Blade

---

## Role & Permission Matrix

| Permission | superadmin | owner | admin | analyst | member | viewer |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `view transaksi` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create transaksi` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `edit transaksi` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `delete transaksi` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `view anggaran` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create anggaran` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `edit anggaran` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `delete anggaran` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `view tabungan` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create tabungan` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `edit tabungan` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `delete tabungan` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `view hutang-piutang` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create hutang-piutang` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `edit hutang-piutang` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `delete hutang-piutang` | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| `view laporan` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `manage members` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage roles` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage household settings` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## File Map

### Baru (Create)
- `config/permission.php` — konfigurasi Spatie (auto-publish)
- `database/migrations/[ts]_create_permission_tables.php` — tabel Spatie (auto-publish)
- `database/migrations/[ts]_migrate_roles_to_spatie.php` — migrasi data role lama ke Spatie
- `database/migrations/[ts]_update_invitations_role_enum.php` — tambah viewer & analyst ke invitation enum
- `database/migrations/[ts]_drop_role_from_users.php` — hapus kolom role lama
- `database/seeders/RolePermissionSeeder.php` — definisi semua role & permission

### Modifikasi (Modify)
- `app/Models/User.php` — tambah `HasRoles` trait, update/remove helper methods
- `app/Http/Middleware/RoleMiddleware.php` — ganti string comparison ke `$user->hasRole()`
- `app/Http/Middleware/SuperadminGlobalMiddleware.php` — ganti ke `$user->hasRole('superadmin')`
- `bootstrap/app.php` — tidak perlu alias baru (Spatie auto-register via ServiceProvider)
- `app/Http/Controllers/HouseholdController.php` — ganti `owner_id` checks ke permission/role checks
- `app/Http/Controllers/Auth/RegisterController.php` — `assignRole()` instead of `role` column
- `app/Http/Controllers/SuperadminController.php` — ganti `$user->role === 'superadmin'`
- `app/Http/Controllers/PrivacyController.php` — ganti `$user->role` ke `$user->getRoleNames()`
- `database/seeders/AdminUserSeeder.php` — `assignRole('superadmin')` setelah create
- `resources/views/household/members.blade.php` — `@hasRole` / `@can` directives
- `resources/views/household/index.blade.php` — `@hasRole` / `@can` directives
- `resources/views/profile/index.blade.php` — display roles dari Spatie
- `resources/views/superadmin/users.blade.php` — display roles dari Spatie
- `resources/views/superadmin/household-show.blade.php` — display roles dari Spatie

---

## Task 1: Install Spatie Laravel Permission

**Files:**
- Modify: `composer.json` (via composer command)
- Create: `config/permission.php`
- Create: `database/migrations/[ts]_create_permission_tables.php`

- [ ] **Step 1: Install package via Composer**

```bash
composer require spatie/laravel-permission
```

Expected output: Package installed, no errors.

- [ ] **Step 2: Publish config dan migration**

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Expected output:
```
Copied File [...] To config/permission.php
Copied File [...] To database/migrations/[timestamp]_create_permission_tables.php
```

- [ ] **Step 3: Jalankan migration Spatie**

```bash
php artisan migrate
```

Expected: 5 tabel baru: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock config/permission.php database/migrations/*create_permission_tables*
git commit -m "feat: install spatie/laravel-permission v6"
```

---

## Task 2: Update User Model — Tambah HasRoles Trait

**Files:**
- Modify: `app/Models/User.php`

- [ ] **Step 1: Tambah HasRoles trait ke User model**

Buka `app/Models/User.php`. Tambahkan use statement dan trait:

```php
// Di bagian use statements (setelah use Illuminate\Notifications\Notifiable;)
use Spatie\Permission\Traits\HasRoles;

// Di dalam class User:
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;
    // ... sisa kode
}
```

- [ ] **Step 2: Hapus helper methods yang digantikan Spatie**

Hapus method `isHouseholdAdmin()` dan `isHouseholdOwner()` dari User.php (baris 121-132) karena Spatie menyediakan `hasRole()`:

```php
// HAPUS kedua method ini:
public function isHouseholdAdmin(): bool
{
    return $this->role === 'admin';
}

public function isHouseholdOwner(): bool
{
    return $this->role === 'owner';
}
```

> **Catatan:** Jika ada kode lain yang memanggil `isHouseholdAdmin()` / `isHouseholdOwner()`, ganti dengan `$user->hasRole('admin')` / `$user->hasRole('owner')`.

- [ ] **Step 3: Hapus 'role' dari fillable (opsional, setelah kolom dihapus di Task 8)**

Biarkan dulu di fillable hingga Task 8 selesai.

- [ ] **Step 4: Verifikasi dengan tinker**

```bash
php artisan tinker
>>> $u = App\Models\User::first();
>>> $u->getRoleNames(); // harus return Collection kosong (belum assign)
>>> $u->assignRole('member');
>>> $u->hasRole('member'); // harus return true
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: tambah HasRoles trait ke User model"
```

---

## Task 3: RolePermissionSeeder — Definisi Semua Role & Permission

**Files:**
- Create: `database/seeders/RolePermissionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Buat file RolePermissionSeeder.php**

```php
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
```

- [ ] **Step 2: Daftarkan di DatabaseSeeder.php**

Buka `database/seeders/DatabaseSeeder.php`, tambahkan `RolePermissionSeeder` di daftar call:

```php
$this->call([
    RolePermissionSeeder::class, // tambahkan di baris pertama
    PlanSeeder::class,
    SettingSeeder::class,
    AdminUserSeeder::class,
    KategoriDefaultSeeder::class,
]);
```

- [ ] **Step 3: Jalankan seeder**

```bash
php artisan db:seed --class=RolePermissionSeeder
```

Expected output:
```
Running seeder: Database\Seeders\RolePermissionSeeder
```

- [ ] **Step 4: Verifikasi roles & permissions terbuat**

```bash
php artisan tinker
>>> Spatie\Permission\Models\Role::all()->pluck('name');
// Expected: ['superadmin', 'owner', 'admin', 'analyst', 'member', 'viewer']
>>> Spatie\Permission\Models\Permission::count();
// Expected: 20
>>> Spatie\Permission\Models\Role::findByName('owner')->permissions->pluck('name');
// Expected: semua 20 permissions
```

- [ ] **Step 5: Commit**

```bash
git add database/seeders/RolePermissionSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat: tambah RolePermissionSeeder dengan 6 role dan 20 permission"
```

---

## Task 4: Migrasi Data — Assign Role Spatie ke User Existing

**Files:**
- Create: `database/migrations/[ts]_migrate_roles_to_spatie.php`

- [ ] **Step 1: Buat migration untuk migrasi data**

```bash
php artisan make:migration migrate_roles_to_spatie
```

- [ ] **Step 2: Isi migration dengan logika migrasi**

Buka file migration yang baru dibuat, isi dengan:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

            // Cek apakah user sudah punya role ini agar idempotent
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
        // Tidak ada rollback — data di model_has_roles akan dihapus saat drop tabel
    }
};
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

- [ ] **Step 4: Verifikasi data termigrasikan**

```bash
php artisan tinker
>>> $user = App\Models\User::first();
>>> $user->getRoleNames(); // harus return role yang sesuai dengan role lama
>>> App\Models\User::whereHas('roles')->count(); // harus sama dengan jumlah user yang punya role
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/*migrate_roles_to_spatie*
git commit -m "feat: migrasi data role lama ke spatie model_has_roles"
```

---

## Task 5: Update AdminUserSeeder — Assign Role Superadmin

**Files:**
- Modify: `database/seeders/AdminUserSeeder.php`

- [ ] **Step 1: Buka AdminUserSeeder.php dan tambahkan assignRole**

Tambahkan `assignRole('superadmin')` setelah user dibuat/diupdate:

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Di dalam run():
app()[PermissionRegistrar::class]->forgetCachedPermissions();

$user = User::updateOrCreate(
    ['email' => 'admin@finanku.test'],
    [
        // HAPUS 'role' => 'owner' dari sini setelah Task 8
        'name'      => 'Admin Finanku',
        'password'  => bcrypt('password'),
        'is_active' => true,
    ]
);

// Assign role via Spatie (idempotent)
if (!$user->hasRole('superadmin')) {
    $user->assignRole('superadmin');
}
```

- [ ] **Step 2: Jalankan seeder untuk verifikasi**

```bash
php artisan db:seed --class=AdminUserSeeder
```

- [ ] **Step 3: Commit**

```bash
git add database/seeders/AdminUserSeeder.php
git commit -m "feat: AdminUserSeeder assign role superadmin via spatie"
```

---

## Task 6: Update Middleware — RoleMiddleware & SuperadminGlobalMiddleware

**Files:**
- Modify: `app/Http/Middleware/RoleMiddleware.php`
- Modify: `app/Http/Middleware/SuperadminGlobalMiddleware.php`

- [ ] **Step 1: Update RoleMiddleware.php**

Ganti isi method `handle()` dari:
```php
if ($user->role !== $role) {
```
menjadi (mendukung pipe-separated roles seperti `owner|admin`):

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Support multiple roles: ->middleware('role:owner,admin')
        if (!$request->user()->hasAnyRole($roles)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Update SuperadminGlobalMiddleware.php**

Ganti baris `if ($user->role !== 'superadmin')` menjadi:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperadminGlobalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('superadmin')) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 3: Verifikasi middleware berjalan**

Coba akses route yang membutuhkan role dengan user yang punya role yang tepat, pastikan tidak 403. Coba dengan user tanpa role yang dibutuhkan, pastikan dapat 403.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/RoleMiddleware.php app/Http/Middleware/SuperadminGlobalMiddleware.php
git commit -m "feat: update middleware role menggunakan spatie hasRole/hasAnyRole"
```

---

## Task 7: Update HouseholdController — Ganti Permission Checks

**Files:**
- Modify: `app/Http/Controllers/HouseholdController.php`

- [ ] **Step 1: Ganti semua `$household->owner_id !== auth()->id()` ke permission check**

Buka `HouseholdController.php`. Ganti pengecekan otorisasi di method berikut:

**invite() (baris ~76):**
```php
// LAMA:
if ($household->owner_id !== auth()->id()) {
    abort(403, 'Hanya owner household yang dapat mengundang anggota');
}

// BARU:
if (!auth()->user()->hasPermissionTo('manage members')) {
    abort(403, 'Anda tidak memiliki izin untuk mengundang anggota');
}
```

**updateRole() (baris ~214-219):**
```php
// LAMA:
if ($household->owner_id !== auth()->id()) {
    abort(403, 'Only household owner can update member roles');
}
if ($user->id === $household->owner_id) {
    return back()->with('error', 'Tidak dapat mengubah role owner');
}

// BARU:
if (!auth()->user()->hasPermissionTo('manage roles')) {
    abort(403, 'Anda tidak memiliki izin untuk mengubah role anggota');
}
if ($user->hasRole('owner')) {
    return back()->with('error', 'Tidak dapat mengubah role owner');
}
```

**removeMember() (baris ~243):**
```php
// LAMA:
if ($household->owner_id !== auth()->id()) {
    abort(403);
}

// BARU:
if (!auth()->user()->hasPermissionTo('manage members')) {
    abort(403, 'Anda tidak memiliki izin untuk menghapus anggota');
}
```

- [ ] **Step 2: Update validasi role di updateRole()**

Validasi role yang bisa di-assign sekarang mencakup 4 role (owner tidak bisa di-assign via form):

```php
$request->validate([
    'role' => 'required|in:admin,analyst,member,viewer',
]);

// Revoke semua role lama user tersebut, lalu assign yang baru
$user->syncRoles([$request->role]);
```

- [ ] **Step 3: Verifikasi perubahan**

```bash
php artisan tinker
>>> $user = App\Models\User::first();
>>> $user->can('manage members'); // true jika owner/admin
>>> $user->can('manage roles');   // true jika owner saja
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/HouseholdController.php
git commit -m "feat: HouseholdController gunakan spatie permission checks"
```

---

## Task 8: Update RegisterController — assignRole saat Registrasi

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisterController.php`

- [ ] **Step 1: Ganti assignment `role` column ke Spatie assignRole**

Di `RegisterController.php`, setelah `User::create(...)`, tambahkan role assignment:

```php
// Tentukan role
$roleName = $invitation ? ($invitation->role ?? 'member') : 'owner';

$user = User::create([
    'name'                   => $request->name,
    'email'                  => $request->email,
    'password'               => bcrypt($request->password),
    'household_id'           => $household->id,
    // HAPUS 'role' => $role, — tidak lagi diperlukan setelah Task 9 selesai
    'is_active'              => true,
    'consent_given_at'       => now(),
    'consent_ip'             => $request->ip(),
    'privacy_policy_version' => '1.0',
]);

// Assign role via Spatie
$user->assignRole($roleName);
```

- [ ] **Step 2: Ganti pengecekan `$user->role === 'owner'` (baris ~108)**

```php
// LAMA:
if ($user->role === 'owner') {

// BARU:
if ($user->hasRole('owner')) {
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Auth/RegisterController.php
git commit -m "feat: RegisterController assign role via spatie saat registrasi"
```

---

## Task 9: Update SuperadminController & PrivacyController

**Files:**
- Modify: `app/Http/Controllers/SuperadminController.php`
- Modify: `app/Http/Controllers/PrivacyController.php`

- [ ] **Step 1: Update SuperadminController.php (baris ~82)**

```php
// LAMA:
if ($user->role === 'superadmin') {
    return back()->with('error', 'Tidak dapat menonaktifkan akun superadmin');
}

// BARU:
if ($user->hasRole('superadmin')) {
    return back()->with('error', 'Tidak dapat menonaktifkan akun superadmin');
}
```

- [ ] **Step 2: Update PrivacyController.php**

Cari baris yang menggunakan `$user->role` (baris ~48):

```php
// LAMA:
'peran' => $user->role,

// BARU:
'peran' => $user->getRoleNames()->implode(', '),
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/SuperadminController.php app/Http/Controllers/PrivacyController.php
git commit -m "feat: update SuperadminController & PrivacyController ke spatie role"
```

---

## Task 10: Update Blade Views

**Files:**
- Modify: `resources/views/household/members.blade.php`
- Modify: `resources/views/household/index.blade.php`
- Modify: `resources/views/profile/index.blade.php`
- Modify: `resources/views/superadmin/users.blade.php`
- Modify: `resources/views/superadmin/household-show.blade.php`

- [ ] **Step 1: Update household/members.blade.php**

Ganti badge role display — dari `$member->role` ke `$member->getRoleNames()->implode(', ')`:

```blade
{{-- LAMA: --}}
<span class="badge rounded-pill {{ $member->role === 'owner' ? 'bg-warning text-dark' : 'bg-secondary' }}">
    {{ ucfirst($member->role) }}
</span>

{{-- BARU: --}}
@php $memberRole = $member->getRoleNames()->first() ?? 'member'; @endphp
<span class="badge rounded-pill {{ $member->hasRole('owner') ? 'bg-warning text-dark' : 'bg-secondary' }}">
    {{ ucfirst($memberRole) }}
</span>
```

Ganti tombol remove member:
```blade
{{-- LAMA: --}}
@if(auth()->user()->role === 'owner' && $member->id !== auth()->id() && ...)

{{-- BARU: --}}
@can('manage members')
    @if(!$member->hasRole('owner') && $member->id !== auth()->id())
        {{-- tombol remove --}}
    @endif
@endcan
```

Ganti form invite (owner/admin can invite):
```blade
{{-- LAMA: --}}
@if(in_array(auth()->user()->role, ['owner', 'admin']))

{{-- BARU: --}}
@can('manage members')
```

Update dropdown role di form invite (tambahkan viewer & analyst, hapus viewer yang sebelumnya tidak valid di enum):
```blade
<select name="role" class="form-select form-select-sm" style="width:auto;">
    <option value="member">{{ __('household.role_member') }}</option>
    <option value="admin">{{ __('household.role_admin') }}</option>
    <option value="analyst">Analyst</option>
    <option value="viewer">{{ __('household.role_viewer') }}</option>
</select>
```

Update form ubah role:
```blade
{{-- Tombol ubah role hanya muncul jika punya permission manage roles --}}
@can('manage roles')
    <select name="role">
        <option value="admin">Admin</option>
        <option value="analyst">Analyst</option>
        <option value="member">Member</option>
        <option value="viewer">Viewer</option>
    </select>
@endcan
```

- [ ] **Step 2: Update household/index.blade.php**

Sama seperti members.blade.php untuk badge dan kondisi invite:

```blade
{{-- Badge role --}}
@php $memberRole = $member->getRoleNames()->first() ?? 'member'; @endphp
<span class="badge rounded-pill {{ $member->hasRole('owner') ? 'bg-warning text-dark' : 'bg-secondary' }}">
    {{ ucfirst($memberRole) }}
</span>

{{-- Form invite --}}
@can('manage members')
    {{-- form invite --}}
@endcan
```

- [ ] **Step 3: Update profile/index.blade.php (baris ~51)**

```blade
{{-- LAMA: --}}
<span class="badge rounded-pill bg-primary" title="Profile Role">
    {{ ucfirst($user->role ?? 'member') }}
</span>

{{-- BARU: --}}
<span class="badge rounded-pill bg-primary" title="Profile Role">
    {{ $user->getRoleNames()->map('ucfirst')->implode(', ') ?: 'member' }}
</span>
```

- [ ] **Step 4: Update superadmin/users.blade.php (baris 52, 60)**

```blade
{{-- LAMA (baris 52): --}}
<td class="text-muted">{{ $user->role }}</td>

{{-- BARU: --}}
<td class="text-muted">{{ $user->getRoleNames()->implode(', ') ?: '-' }}</td>

{{-- LAMA (baris 60): --}}
@if($user->role !== 'superadmin')

{{-- BARU: --}}
@if(!$user->hasRole('superadmin'))
```

- [ ] **Step 5: Update superadmin/household-show.blade.php (baris 62)**

```blade
{{-- LAMA: --}}
<span class="text-muted">{{ $user->role }}</span>

{{-- BARU: --}}
<span class="text-muted">{{ $user->getRoleNames()->implode(', ') ?: '-' }}</span>
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/household/members.blade.php \
        resources/views/household/index.blade.php \
        resources/views/profile/index.blade.php \
        resources/views/superadmin/users.blade.php \
        resources/views/superadmin/household-show.blade.php
git commit -m "feat: update semua blade view ke spatie hasRole & @can directives"
```

---

## Task 11: Update Invitation Enum — Tambah viewer & analyst

**Files:**
- Create: `database/migrations/[ts]_update_invitations_role_enum.php`

- [ ] **Step 1: Buat migration untuk update enum**

```bash
php artisan make:migration update_household_invitations_role_enum
```

- [ ] **Step 2: Isi migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify enum dengan nilai baru
        DB::statement("ALTER TABLE household_invitations MODIFY COLUMN role ENUM('admin', 'analyst', 'member', 'viewer') DEFAULT 'member'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE household_invitations MODIFY COLUMN role ENUM('admin', 'member') DEFAULT 'member'");
    }
};
```

- [ ] **Step 3: Jalankan migration**

```bash
php artisan migrate
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*update_household_invitations_role_enum*
git commit -m "feat: tambah viewer & analyst ke enum role di household_invitations"
```

---

## Task 12: Hapus Kolom role Lama dari Users Table

> **PENTING:** Lakukan task ini HANYA setelah Task 1–11 selesai semua dan diverifikasi di environment staging/development.

**Files:**
- Create: `database/migrations/[ts]_drop_role_from_users.php`
- Modify: `app/Models/User.php` (hapus 'role' dari fillable)

- [ ] **Step 1: Buat migration untuk drop kolom**

```bash
php artisan make:migration drop_role_from_users_table
```

- [ ] **Step 2: Isi migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('household_id');
        });
    }
};
```

- [ ] **Step 3: Hapus 'role' dari fillable di User.php**

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'household_id',
    // 'role', <- HAPUS baris ini
    'avatar',
    'is_active',
    'dashboard_cards',
    'consent_given_at',
    'consent_ip',
    'privacy_policy_version',
];
```

- [ ] **Step 4: Jalankan migration**

```bash
php artisan migrate
```

- [ ] **Step 5: Jalankan semua seeder untuk verifikasi fresh setup**

```bash
php artisan migrate:fresh --seed
```

Expected: Semua migration berjalan, semua seeder sukses, tidak ada error.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/*drop_role_from_users* app/Models/User.php
git commit -m "feat: hapus kolom role lama dari tabel users — migrasi RBAC ke spatie selesai"
```

---

## Task 13: Update lang files — Tambah label untuk role baru

**Files:**
- Modify: `lang/id/household.php`
- Modify: `lang/en/household.php`

- [ ] **Step 1: Update lang/id/household.php**

```php
// Tambahkan entri baru:
'role_analyst' => 'Analis',
'role_owner'   => 'Pemilik',
```

- [ ] **Step 2: Update lang/en/household.php**

```php
'role_analyst' => 'Analyst',
'role_owner'   => 'Owner',
```

- [ ] **Step 3: Commit**

```bash
git add lang/id/household.php lang/en/household.php
git commit -m "feat: tambah label i18n untuk role analyst & owner"
```

---

## Verifikasi Akhir

Setelah semua task selesai, lakukan verifikasi menyeluruh:

```bash
# 1. Fresh migration & seed
php artisan migrate:fresh --seed

# 2. Tinker verification
php artisan tinker
>>> $user = App\Models\User::first();
>>> $user->getRoleNames();           // ['owner'] atau sesuai role
>>> $user->hasRole('owner');         // true
>>> $user->can('manage members');    // true
>>> $user->can('manage roles');      // true jika owner
>>> $user->hasPermissionTo('view transaksi'); // true
>>> $user->roles()->count();         // 1 (bisa lebih jika multi-role)

# 3. Pastikan superadmin check berjalan
>>> $admin = App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'superadmin'))->first();
>>> $admin->hasRole('superadmin');   // true
```

---

## Catatan Penting

1. **Multi-role per user** — Dengan Spatie, user BISA punya lebih dari 1 role. Contoh assign multiple role:
   ```php
   $user->assignRole(['owner', 'analyst']); // user bisa lihat laporan sekaligus manage household
   $user->syncRoles(['admin', 'analyst']);   // replace semua role sekaligus
   ```

2. **Cache permissions** — Spatie meng-cache permissions. Jika ada perubahan permission di production:
   ```bash
   php artisan permission:cache-reset
   ```

3. **Superadmin bypass** — Jika ingin superadmin bypass SEMUA permission check secara otomatis, tambahkan di `AuthServiceProvider`:
   ```php
   Gate::before(function ($user, $ability) {
       if ($user->hasRole('superadmin')) {
           return true;
       }
   });
   ```

4. **Future: Multi-household** — Jika suatu saat user bisa bergabung di banyak household, migrate ke Spatie Teams feature.
