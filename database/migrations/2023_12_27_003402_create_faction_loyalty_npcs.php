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
        Schema::create('faction_loyalty_npcs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('faction_loyalty_id');
            $table->unsignedBigInteger('npc_id');
            $table->foreign('faction_loyalty_id')->on('faction_loyalties')->references('id');
            $table->foreign('npc_id')->on('npcs')->references('id');
            $table->integer('current_level');
            $table->integer('max_level');
            $table->integer('next_level_fame');
            $table->boolean('currently_helping')->default(false);
            $table->decimal('kingdom_item_defence_bonus', 12, 8);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faction_loyalty_npcs');
    }
};
