<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('type', 20);
            $table->string('category', 100);
            $table->string('title', 200);
            $table->text('description');
            $table->string('difficulty', 20)->default('medium');
            $table->unsignedSmallInteger('xp_reward')->default(30);
            $table->unsignedTinyInteger('momentum_bonus')->default(5);
            $table->string('condition_type', 100);
            $table->json('condition_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
