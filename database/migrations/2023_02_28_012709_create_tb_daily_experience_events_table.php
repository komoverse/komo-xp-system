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
        Schema::create('tb_daily_experience_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_experience_id');
            $table->string('source');
            $table->bigInteger('delta')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_daily_experience_events');
    }
};
