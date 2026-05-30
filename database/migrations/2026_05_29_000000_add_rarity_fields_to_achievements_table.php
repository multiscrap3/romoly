<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('rarity', 20)->default('silver')->after('tier_type');
            $table->boolean('is_hidden')->default(false)->after('rarity');
            $table->boolean('is_major')->default(false)->after('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['rarity', 'is_hidden', 'is_major']);
        });
    }
};
