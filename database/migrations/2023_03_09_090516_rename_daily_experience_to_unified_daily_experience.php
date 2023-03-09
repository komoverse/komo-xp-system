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
        Schema::table('xp_daily_experience_events', function (Blueprint $table) {
            $table->unsignedBigInteger('unified_daily_experience_id')->after('daily_experience_id');
            $table->dropColumn('daily_experience_id');
        });

        Schema::rename('xp_daily_experiences', 'xp_unified_daily_experiences');
        Schema::rename('xp_daily_experience_events', 'xp_unified_daily_experience_events');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xp_unified_daily_experience_events', function (Blueprint $table) {
            $table->unsignedBigInteger('daily_experience_id')->after('unified_daily_experience_id');
            $table->dropColumn('unified_daily_experience_id');
        });

        Schema::rename('xp_unified_daily_experiences', 'xp_daily_experiences');
        Schema::rename('xp_unified_daily_experience_events', 'xp_daily_experience_events');
    }
};
