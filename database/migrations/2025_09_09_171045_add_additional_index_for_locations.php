<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->index('type', 'items_type_idx');
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->index('game_map_id', 'locations_game_map_id_idx');
            $table->index('quest_reward_item_id', 'locations_quest_reward_item_id_idx');
            $table->index('type', 'locations_type_idx');
        });

        Schema::table('quests', function (Blueprint $table): void {
            $table->index('item_id', 'quests_item_id_idx');
            $table->index('secondary_required_item', 'quests_secondary_required_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropIndex('items_type_idx');
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->dropIndex('locations_game_map_id_idx');
            $table->dropIndex('locations_quest_reward_item_id_idx');
            $table->dropIndex('locations_type_idx');
        });

        Schema::table('quests', function (Blueprint $table): void {
            $table->dropIndex('quests_item_id_idx');
            $table->dropIndex('quests_secondary_required_item_idx');
        });
    }
};
