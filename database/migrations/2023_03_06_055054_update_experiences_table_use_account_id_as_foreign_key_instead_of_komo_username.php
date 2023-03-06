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
        Schema::table('tb_daily_experiences', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->after('id');
            $table->dropColumn('komo_username');
        });

        Schema::table('tb_mmr_experiences', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->after('id');
            $table->dropColumn('komo_username');
        });

        Schema::table('tb_compendium_experiences', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->after('id');
            $table->dropColumn('komo_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_daily_experiences', function (Blueprint $table) {
            $table->dropColumn('account_id');
            $table->string('komo_username')->after('id');
        });

        Schema::table('tb_mmr_experiences', function (Blueprint $table) {
            $table->dropColumn('account_id');
            $table->string('komo_username')->after('id');
        });

        Schema::table('tb_compendium_experiences', function (Blueprint $table) {
            $table->dropColumn('account_id');
            $table->string('komo_username')->after('id');
        });
    }
};
