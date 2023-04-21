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
        Schema::create('raids', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('story');
            $table->unsignedBigInteger('raid_boss_id');
            $table->foreign('raid_boss_id')->references('id')->on('monsters');
            $table->json('raid_monster_ids');
            $table->unsignedBigInteger('raid_boss_location_id');
            $table->foreign('raid_boss_location_id')->references('id')->on('locations');
            $table->json('corrupted_location_ids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raids');
    }
};
