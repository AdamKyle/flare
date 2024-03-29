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
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->unsignedBigInteger('be_on_game_map')->nullable();
            $table->bigInteger('required_event_goal_participation')->nullable();
            $table->integer('required_holy_stacks')->nullable();
            $table->integer('required_attached_gems')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            //
        });
    }
};
