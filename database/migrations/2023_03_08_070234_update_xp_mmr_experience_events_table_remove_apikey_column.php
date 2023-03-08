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
        Schema::table('xp_mmr_experience_events', function (Blueprint $table) {
            $table->dropColumn('api_key');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xp_mmr_experience_events', function (Blueprint $table) {
            $table->string('api_key')->after('mmr_experience_id');
        });
    }
};
