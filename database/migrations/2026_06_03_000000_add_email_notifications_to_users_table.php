<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferensi per-user untuk menerima notifikasi/reminder via email.
     * Default true (opt-out). Email auth-kritis (verifikasi, reset password)
     * TIDAK tunduk pada preferensi ini — selalu dikirim.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notifications')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_notifications');
        });
    }
};
