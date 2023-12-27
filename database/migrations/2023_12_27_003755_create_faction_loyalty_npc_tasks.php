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
        Schema::create('faction_loyalty_npc_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('faction_loyalty_id');
            $table->unsignedBigInteger('faction_loyalty_npc_id');
            $table->foreign('faction_loyalty_id')->on('faction_loyalties')->references('id');
            $table->foreign('faction_loyalty_npc_id')->on('faction_loyalty_npcs')->references('id');
            $table->json('fame_tasks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faction_loyalty_npc_tasks');
    }
};
