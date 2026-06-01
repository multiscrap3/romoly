<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Progress guided tour / user guide per-user (JSON):
            // { "welcome_completed": bool, "seen": ["dashboard", ...], "updated_at": "ISO8601" }
            $table->json('tour_progress')->nullable()->after('dashboard_cards');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tour_progress');
        });
    }
};
