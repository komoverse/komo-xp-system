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
        Schema::table('tb_daily_experience_events', function (Blueprint $table) {
            $table->string('api_key')->after('source');
            $table->dropColumn('source');
        });

        Schema::table('tb_mmr_experience_events', function (Blueprint $table) {
            $table->string('api_key')->after('source');
            $table->dropColumn('source');
        });

        Schema::table('tb_compendium_experience_events', function (Blueprint $table) {
            $table->string('api_key')->after('source');
            $table->dropColumn('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_daily_experience_events', function (Blueprint $table) {
            $table->string('source')->after('api_key');
            $table->dropColumn('api_key');
        });

        Schema::table('tb_mmr_experience_events', function (Blueprint $table) {
            $table->string('source')->after('api_key');
            $table->dropColumn('api_key');
        });

        Schema::table('tb_compendium_experience_events', function (Blueprint $table) {
            $table->string('source')->after('api_key');
            $table->dropColumn('api_key');
        });
    }
};
