<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        DB::statement("ALTER TABLE household_invitations MODIFY COLUMN role ENUM('admin', 'analyst', 'member', 'viewer') DEFAULT 'member'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        DB::statement("ALTER TABLE household_invitations MODIFY COLUMN role ENUM('admin', 'member') DEFAULT 'member'");
    }
};
