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
        Schema::create('kingdom_building_expansions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_building_id');
            $table->unsignedBigInteger('kingdom_id');
            $table->integer('expansion_type');
            $table->integer('expansion_count');
            $table->integer('expansions_left');
            $table->integer('minutes_until_next_expansion');
            $table->json('resource_costs');
            $table->integer('gold_bars_cost');
            $table->json('resource_increases');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kingdom_building_expansions');
    }
};
