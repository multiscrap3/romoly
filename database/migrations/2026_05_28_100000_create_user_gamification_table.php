<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_gamification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedTinyInteger('level')->default(1);
            $table->decimal('momentum_score', 5, 2)->default(50.00);
            $table->date('last_active_date')->nullable();
            $table->unsignedTinyInteger('inactive_days_count')->default(0);
            $table->unsignedTinyInteger('grace_days_used')->default(0);
            $table->date('grace_period_start')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gamification');
    }
};
