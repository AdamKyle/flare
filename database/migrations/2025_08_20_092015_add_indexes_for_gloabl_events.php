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
        Schema::table('events', function (Blueprint $table) {
            $table->index('type', 'events_type_idx');
        });

        Schema::table('game_maps', function (Blueprint $table) {
            $table->index('only_during_event_type', 'game_maps_only_during_event_type_idx');
        });

        Schema::table('global_event_goals', function (Blueprint $table) {
            $table->index('event_type', 'global_event_goals_event_type_idx');
        });

        Schema::table('global_event_participation', function (Blueprint $table) {
            $table->index('character_id', 'global_event_participation_character_id_idx');
        });

        Schema::table('global_event_crafting_inventory_slots', function (Blueprint $table) {
            $table->index(['global_event_crafting_inventory_id', 'item_id'], 'global_event_craft_inv_slots_inv_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_type_idx');
        });

        Schema::table('game_maps', function (Blueprint $table) {
            $table->dropIndex('game_maps_only_during_event_type_idx');
        });

        Schema::table('global_event_goals', function (Blueprint $table) {
            $table->dropIndex('global_event_goals_event_type_idx');
        });

        Schema::table('global_event_participation', function (Blueprint $table) {
            $table->dropIndex('global_event_participation_character_id_idx');
        });

        Schema::table('global_event_crafting_inventory_slots', function (Blueprint $table) {
            $table->dropIndex('global_event_craft_inv_slots_inv_item_idx');
        });
    }
};
