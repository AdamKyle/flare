<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delve_explorations', function (Blueprint $table) {
            $table->index(['character_id', 'completed_at'], 'delve_explorations_character_id_completed_at_index');
        });

        Schema::table('faction_loyalty_automations', function (Blueprint $table) {
            $table->index(['character_id', 'completed_at'], 'fl_automations_character_id_completed_at_index');
        });

        Schema::table('capital_city_building_queues', function (Blueprint $table) {
            $table->index(['kingdom_id', 'status'], 'ccbq_kingdom_id_status_index');
        });

        Schema::table('capital_city_unit_queues', function (Blueprint $table) {
            $table->index(['kingdom_id', 'status'], 'ccuq_kingdom_id_status_index');
        });

        Schema::table('capital_city_unit_cancellations', function (Blueprint $table) {
            $table->index(
                ['character_id', 'travel_time_completed_at'],
                'ccuc_character_id_travel_time_index'
            );
        });

        Schema::table('smelting_progress', function (Blueprint $table) {
            $table->index(['character_id', 'completed_at'], 'smelting_progress_character_completed_at_index');
        });

        Schema::table('building_expansion_queues', function (Blueprint $table) {
            $table->index(['character_id', 'completed_at'], 'beq_character_id_completed_at_index');
        });

        Schema::table('event_goal_participation_crafts', function (Blueprint $table) {
            $table->index(['character_id', 'global_event_goal_id'], 'egpc_character_id_goal_id_index');
        });

        Schema::table('event_goal_participation_enchants', function (Blueprint $table) {
            $table->index(['character_id', 'global_event_goal_id'], 'egpe_character_id_goal_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('event_goal_participation_enchants', function (Blueprint $table) {
            $table->dropIndex('egpe_character_id_goal_id_index');
        });

        Schema::table('event_goal_participation_crafts', function (Blueprint $table) {
            $table->dropIndex('egpc_character_id_goal_id_index');
        });

        Schema::table('building_expansion_queues', function (Blueprint $table) {
            $table->dropIndex('beq_character_id_completed_at_index');
        });

        Schema::table('smelting_progress', function (Blueprint $table) {
            $table->dropIndex('smelting_progress_character_completed_at_index');
        });

        Schema::table('capital_city_unit_cancellations', function (Blueprint $table) {
            $table->dropIndex('ccuc_character_id_travel_time_index');
        });

        Schema::table('capital_city_unit_queues', function (Blueprint $table) {
            $table->dropIndex('ccuq_kingdom_id_status_index');
        });

        Schema::table('capital_city_building_queues', function (Blueprint $table) {
            $table->dropIndex('ccbq_kingdom_id_status_index');
        });

        Schema::table('faction_loyalty_automations', function (Blueprint $table) {
            $table->dropIndex('fl_automations_character_id_completed_at_index');
        });

        Schema::table('delve_explorations', function (Blueprint $table) {
            $table->dropIndex('delve_explorations_character_id_completed_at_index');
        });
    }
};
