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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_xp_for_exploration')->default(true);
            $table->boolean('show_xp_per_kill')->default(true);
            $table->boolean('show_skill_xp_per_kill')->default(true);
            $table->boolean('show_gold_per_kill')->default(true);
            $table->boolean('show_gold_dust_per_kill')->default(true);
            $table->boolean('show_shards_per_kill')->default(true);
            $table->boolean('show_copper_coins_per_kill')->default(true);
            $table->boolean('show_faction_point_message')->default(true);
            $table->boolean('show_xp_for_class_masteries')->default(true);
            $table->boolean('show_xp_for_class_ranks')->default(true);
            $table->boolean('show_xp_for_equipped_class_specials')->default(true);
            $table->boolean('show_item_skill_kill_count')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
