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
        Schema::create('raid_boss_participations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('raid_id');
            $table->integer('attacks_left');
            $table->bigInteger('damage_dealt');
            $table->boolean('killed_boss')->default(false);
            $table->foreign('character_id')->references('id')->on('characters');
            $table->foreign('raid_id')->references('id')->on('raids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raid_boss_participations');
    }
};
