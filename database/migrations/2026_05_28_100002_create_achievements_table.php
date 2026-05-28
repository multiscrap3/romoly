<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 150);
            $table->text('description');
            $table->string('category', 50);
            $table->string('tier_type', 20)->default('financial');
            $table->unsignedSmallInteger('xp_reward')->default(50);
            $table->string('condition_type', 100);
            $table->json('condition_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
