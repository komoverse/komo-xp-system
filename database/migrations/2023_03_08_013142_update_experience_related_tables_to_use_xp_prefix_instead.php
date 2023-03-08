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
        Schema::rename('tb_daily_experiences', 'xp_daily_experiences');
        Schema::rename('tb_daily_experience_events', 'xp_daily_experience_events');
        Schema::rename('tb_mmr_experiences', 'xp_mmr_experiences');
        Schema::rename('tb_mmr_experience_events', 'xp_mmr_experience_events');
        Schema::rename('tb_compendium_experiences', 'xp_compendium_experiences');
        Schema::rename('tb_compendium_experience_events', 'xp_compendium_experience_events');
        Schema::rename('tb_game_experience_multipliers', 'xp_game_experience_multipliers');
        Schema::rename('tb_raw_experience_records', 'xp_raw_experience_records');
        Schema::rename('tb_seasons', 'xp_compendium_seasons');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('xp_daily_experiences', 'tb_daily_experiences');
        Schema::rename('xp_daily_experience_events', 'tb_daily_experience_events');
        Schema::rename('xp_mmr_experiences', 'tb_mmr_experiences');
        Schema::rename('xp_mmr_experience_events', 'tb_mmr_experience_events');
        Schema::rename('xp_compendium_experiences', 'tb_compendium_experiences');
        Schema::rename('xp_compendium_experience_events', 'tb_compendium_experience_events');
        Schema::rename('xp_game_experience_multipliers', 'tb_game_experience_multipliers');
        Schema::rename('xp_raw_experience_records', 'tb_raw_experience_records');
        Schema::rename('xp_compendium_seasons', 'tb_seasons');
    }
};
