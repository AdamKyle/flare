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
        Schema::create('raid_bosses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('raid_id');
            $table->unsignedBigInteger('raid_boss_id');
            $table->bigInteger('boss_max_hp');
            $table->bigInteger('boss_current_hp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raid_bosses');
    }
};
