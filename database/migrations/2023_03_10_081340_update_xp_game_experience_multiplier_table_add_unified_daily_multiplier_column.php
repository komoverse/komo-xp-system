<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('xp_game_experience_multipliers', function (Blueprint $table) {
            $table->float('unified_daily_multiplier')->default(1)->after('api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xp_game_experience_multipliers', function (Blueprint $table) {
            $table->dropColumn('unified_daily_multiplier');
        });
    }
};
