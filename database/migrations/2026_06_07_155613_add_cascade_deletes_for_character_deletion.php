<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->cleanOrphanedRowsBeforeAddingForeignKeys();
        $this->makeCharacterIdNullableOnSuggestions();
        $this->makeUserIdNullableOnMessages();
        $this->updateKingdomsFkToSetNull();
        $this->updateCelestialFightsFkToSetNull();
        $this->updateExistingCharacterFksToCascade();
        $this->updateChildTableFksToCascade();
        $this->addNewCharacterFksCascade();
        $this->addNewChildFksCascade();
    }

    public function down(): void
    {
        $this->revertNewChildFksCascade();
        $this->revertNewCharacterFksCascade();
        $this->revertChildTableFksToCascade();
        $this->revertExistingCharacterFksToCascade();
        $this->revertCelestialFightsFkToSetNull();
        $this->revertKingdomsFkToSetNull();
        $this->revertUserIdNullableOnMessages();
        $this->revertCharacterIdNullableOnSuggestions();
    }

    private function makeCharacterIdNullableOnSuggestions(): void
    {
        Schema::table('suggestion_and_bugs', function (Blueprint $table) {
            $table->foreign('character_id', 'sab_character_id_foreign')
                ->references('id')->on('characters')
                ->nullOnDelete();
        });
    }

    private function cleanOrphanedRowsBeforeAddingForeignKeys(): void
    {
        Schema::table('suggestion_and_bugs', function (Blueprint $table) {
            $table->unsignedBigInteger('character_id')->nullable()->change();
        });

        $this->deleteOrphanedChildRows('inventory_slots', 'inventory_id', 'inventories');
        $this->deleteOrphanedChildRows('set_slots', 'inventory_set_id', 'inventory_sets');
        $this->deleteOrphanedChildRows('gem_bag_slots', 'gem_bag_id', 'gem_bags');
        $this->deleteOrphanedChildRows('faction_loyalty_npc_tasks', 'faction_loyalty_id', 'faction_loyalties');
        $this->deleteOrphanedChildRows('faction_loyalty_npcs', 'faction_loyalty_id', 'faction_loyalties');
        $this->deleteOrphanedChildRows('faction_loyalty_npc_tasks', 'faction_loyalty_npc_id', 'faction_loyalty_npcs');
        $this->deleteOrphanedChildRows(
            'character_class_ranks_weapon_masteries',
            'character_class_rank_id',
            'character_class_ranks'
        );

        $characterTables = [
            'delve_explorations',
            'exploration_logs',
            'exploration_warnings',
            'faction_loyalty_automations',
            'faction_loyalty_automation_warnings',
            'global_event_crafting_inventories',
            'global_event_participation',
            'smelting_progress',
            'weekly_monster_fights',
            'building_expansion_queues',
            'capital_city_building_queues',
            'capital_city_building_cancellations',
            'capital_city_unit_queues',
            'capital_city_unit_cancellations',
            'event_goal_participation_crafts',
            'event_goal_participation_enchants',
        ];

        foreach ($characterTables as $table) {
            $this->deleteOrphanedChildRows($table, 'character_id', 'characters');
        }

        $this->deleteOrphanedChildRows('delve_logs', 'delve_exploration_id', 'delve_explorations');
        $this->deleteOrphanedChildRows(
            'global_event_crafting_inventory_slots',
            'global_event_crafting_inventory_id',
            'global_event_crafting_inventories'
        );
        $this->deleteOrphanedChildRows(
            'faction_loyalty_automation_logs',
            'faction_loyalty_automation_id',
            'faction_loyalty_automations'
        );

        $this->nullOrphanedChildRows('suggestion_and_bugs', 'character_id', 'characters');
    }

    private function deleteOrphanedChildRows(string $table, string $foreignKey, string $parentTable): void
    {
        DB::table($table)
            ->whereNotNull($foreignKey)
            ->whereNotExists(function ($query) use ($table, $foreignKey, $parentTable) {
                $query->selectRaw('1')
                    ->from($parentTable)
                    ->whereColumn($parentTable . '.id', $table . '.' . $foreignKey);
            })
            ->delete();
    }

    private function nullOrphanedChildRows(string $table, string $foreignKey, string $parentTable): void
    {
        DB::table($table)
            ->whereNotNull($foreignKey)
            ->whereNotExists(function ($query) use ($table, $foreignKey, $parentTable) {
                $query->selectRaw('1')
                    ->from($parentTable)
                    ->whereColumn($parentTable . '.id', $table . '.' . $foreignKey);
            })
            ->update([$foreignKey => null]);
    }

    private function makeUserIdNullableOnMessages(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign('messages_user_id_foreign');
            $table->dropForeign('messages_from_user_foreign');
            $table->dropForeign('messages_to_user_foreign');

            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->foreign('user_id', 'messages_user_id_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();
            $table->foreign('from_user', 'messages_from_user_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();
            $table->foreign('to_user', 'messages_to_user_foreign')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    private function updateKingdomsFkToSetNull(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->dropForeign('king_cid');
            $table->foreign('character_id', 'king_cid')
                ->references('id')->on('characters')
                ->nullOnDelete();
        });
    }

    private function updateCelestialFightsFkToSetNull(): void
    {
        Schema::table('celestial_fights', function (Blueprint $table) {
            $table->dropForeign('celestial_fights_character_id_foreign');
            $table->foreign('character_id', 'celestial_fights_character_id_foreign')
                ->references('id')->on('characters')
                ->nullOnDelete();
        });
    }

    private function updateExistingCharacterFksToCascade(): void
    {
        $cascadeFks = [
            'inventories' => 'inventories_character_id_foreign',
            'inventory_sets' => 'inventory_sets_character_id_foreign',
            'gem_bags' => 'gem_bags_character_id_foreign',
            'skills' => 'skills_character_id_foreign',
            'character_boons' => 'character_boons_character_id_foreign',
            'character_class_ranks' => 'character_class_ranks_character_id_foreign',
            'character_class_specialties_equipped' => 'cc_se_c_id',
            'character_in_celestial_fights' => 'character_in_celestial_fights_character_id_foreign',
            'character_automations' => 'ca_cid',
            'character_passive_skills' => 'c_cps',
            'factions' => 'ca_fid',
            'faction_loyalties' => 'faction_loyalties_character_id_foreign',
            'quests_completed' => 'quests_completed_character_id_foreign',
            'raid_boss_participations' => 'raid_boss_participations_character_id_foreign',
            'raid_participations' => 'raid_participations_character_id_foreign',
            'unit_movement_queue' => 'uimq_cid',
            'units_in_queue' => 'uiq_cid',
            'maps' => 'maps_character_id_foreign',
            'market_board' => 'mb_character',
            'kingdom_logs' => 'kl_character_id',
            'buildings_in_queue' => 'biq_cid',
            'event_goal_participation_kills' => 'event_goal_participation_kills_character_id_foreign',
        ];

        foreach ($cascadeFks as $table => $constraintName) {
            Schema::table($table, function (Blueprint $blueprint) use ($constraintName) {
                $blueprint->dropForeign($constraintName);
                $blueprint->foreign('character_id', $constraintName)
                    ->references('id')->on('characters')
                    ->cascadeOnDelete();
            });
        }
    }

    private function updateChildTableFksToCascade(): void
    {
        Schema::table('inventory_slots', function (Blueprint $table) {
            $table->dropForeign('inventory_slots_inventory_id_foreign');
            $table->foreign('inventory_id', 'inventory_slots_inventory_id_foreign')
                ->references('id')->on('inventories')
                ->cascadeOnDelete();
        });

        Schema::table('set_slots', function (Blueprint $table) {
            $table->dropForeign('set_id');
            $table->foreign('inventory_set_id', 'set_id')
                ->references('id')->on('inventory_sets')
                ->cascadeOnDelete();
        });

        Schema::table('gem_bag_slots', function (Blueprint $table) {
            $table->dropForeign('gem_bag_slots_gem_bag_id_foreign');
            $table->foreign('gem_bag_id', 'gem_bag_slots_gem_bag_id_foreign')
                ->references('id')->on('gem_bags')
                ->cascadeOnDelete();
        });

        Schema::table('faction_loyalty_npcs', function (Blueprint $table) {
            $table->dropForeign('faction_loyalty_npcs_faction_loyalty_id_foreign');
            $table->foreign('faction_loyalty_id', 'faction_loyalty_npcs_faction_loyalty_id_foreign')
                ->references('id')->on('faction_loyalties')
                ->cascadeOnDelete();
        });

        Schema::table('faction_loyalty_npc_tasks', function (Blueprint $table) {
            $table->dropForeign('faction_loyalty_npc_tasks_faction_loyalty_id_foreign');
            $table->dropForeign('faction_loyalty_npc_tasks_faction_loyalty_npc_id_foreign');

            $table->foreign('faction_loyalty_id', 'faction_loyalty_npc_tasks_faction_loyalty_id_foreign')
                ->references('id')->on('faction_loyalties')
                ->cascadeOnDelete();
            $table->foreign('faction_loyalty_npc_id', 'faction_loyalty_npc_tasks_faction_loyalty_npc_id_foreign')
                ->references('id')->on('faction_loyalty_npcs')
                ->cascadeOnDelete();
        });

        Schema::table('character_class_ranks_weapon_masteries', function (Blueprint $table) {
            $table->dropForeign('ccrank_id');
            $table->foreign('character_class_rank_id', 'ccrank_id')
                ->references('id')->on('character_class_ranks')
                ->cascadeOnDelete();
        });

        Schema::table('character_passive_skills', function (Blueprint $table) {
            $table->dropForeign('ps_cps');
            $table->foreign('parent_skill_id', 'ps_cps')
                ->references('id')->on('character_passive_skills')
                ->nullOnDelete();
        });
    }

    private function addNewCharacterFksCascade(): void
    {
        $newCascadeTables = [
            'delve_explorations',
            'exploration_logs',
            'exploration_warnings',
            'faction_loyalty_automations',
            'faction_loyalty_automation_warnings',
            'global_event_crafting_inventories',
            'global_event_participation',
            'smelting_progress',
            'weekly_monster_fights',
            'building_expansion_queues',
            'capital_city_building_queues',
            'capital_city_building_cancellations',
            'capital_city_unit_queues',
            'capital_city_unit_cancellations',
            'event_goal_participation_crafts',
            'event_goal_participation_enchants',
            'suggestion_and_bugs',
        ];

        Schema::table('smelting_progress', function (Blueprint $table) {
            $table->unsignedBigInteger('character_id')->change();
        });

        foreach ($newCascadeTables as $table) {
            if ($table === 'suggestion_and_bugs') {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $constraintName = $table . '_character_id_fk';
                $blueprint->foreign('character_id', $constraintName)
                    ->references('id')->on('characters')
                    ->cascadeOnDelete();
            });
        }
    }

    private function addNewChildFksCascade(): void
    {
        Schema::table('delve_logs', function (Blueprint $table) {
            $table->foreign('delve_exploration_id', 'delve_logs_exploration_id_fk')
                ->references('id')->on('delve_explorations')
                ->cascadeOnDelete();
        });

        Schema::table('global_event_crafting_inventory_slots', function (Blueprint $table) {
            $table->foreign(
                'global_event_crafting_inventory_id',
                'gecis_inventory_id_fk'
            )->references('id')->on('global_event_crafting_inventories')
                ->cascadeOnDelete();
        });

        Schema::table('faction_loyalty_automation_logs', function (Blueprint $table) {
            $table->foreign(
                'faction_loyalty_automation_id',
                'fl_auto_logs_automation_id_fk'
            )->references('id')->on('faction_loyalty_automations')
                ->cascadeOnDelete();
        });
    }

    private function revertNewChildFksCascade(): void
    {
        Schema::table('faction_loyalty_automation_logs', function (Blueprint $table) {
            $table->dropForeign('fl_auto_logs_automation_id_fk');
        });

        Schema::table('global_event_crafting_inventory_slots', function (Blueprint $table) {
            $table->dropForeign('gecis_inventory_id_fk');
        });

        Schema::table('delve_logs', function (Blueprint $table) {
            $table->dropForeign('delve_logs_exploration_id_fk');
        });
    }

    private function revertNewCharacterFksCascade(): void
    {
        $newCascadeTables = [
            'delve_explorations',
            'exploration_logs',
            'exploration_warnings',
            'faction_loyalty_automations',
            'faction_loyalty_automation_warnings',
            'global_event_crafting_inventories',
            'global_event_participation',
            'smelting_progress',
            'weekly_monster_fights',
            'building_expansion_queues',
            'capital_city_building_queues',
            'capital_city_building_cancellations',
            'capital_city_unit_queues',
            'capital_city_unit_cancellations',
            'event_goal_participation_crafts',
            'event_goal_participation_enchants',
        ];

        foreach ($newCascadeTables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign($table . '_character_id_fk');
            });
        }

        Schema::table('smelting_progress', function (Blueprint $table) {
            $table->bigInteger('character_id')->change();
        });
    }

    private function revertChildTableFksToCascade(): void
    {
        Schema::table('character_passive_skills', function (Blueprint $table) {
            $table->dropForeign('ps_cps');
            $table->foreign('parent_skill_id', 'ps_cps')
                ->references('id')->on('character_passive_skills');
        });

        Schema::table('character_class_ranks_weapon_masteries', function (Blueprint $table) {
            $table->dropForeign('ccrank_id');
            $table->foreign('character_class_rank_id', 'ccrank_id')
                ->references('id')->on('character_class_ranks');
        });

        Schema::table('faction_loyalty_npc_tasks', function (Blueprint $table) {
            $table->dropForeign('faction_loyalty_npc_tasks_faction_loyalty_id_foreign');
            $table->dropForeign('faction_loyalty_npc_tasks_faction_loyalty_npc_id_foreign');

            $table->foreign('faction_loyalty_id', 'faction_loyalty_npc_tasks_faction_loyalty_id_foreign')
                ->references('id')->on('faction_loyalties');
            $table->foreign('faction_loyalty_npc_id', 'faction_loyalty_npc_tasks_faction_loyalty_npc_id_foreign')
                ->references('id')->on('faction_loyalty_npcs');
        });

        Schema::table('faction_loyalty_npcs', function (Blueprint $table) {
            $table->dropForeign('faction_loyalty_npcs_faction_loyalty_id_foreign');
            $table->foreign('faction_loyalty_id', 'faction_loyalty_npcs_faction_loyalty_id_foreign')
                ->references('id')->on('faction_loyalties');
        });

        Schema::table('gem_bag_slots', function (Blueprint $table) {
            $table->dropForeign('gem_bag_slots_gem_bag_id_foreign');
            $table->foreign('gem_bag_id', 'gem_bag_slots_gem_bag_id_foreign')
                ->references('id')->on('gem_bags');
        });

        Schema::table('set_slots', function (Blueprint $table) {
            $table->dropForeign('set_id');
            $table->foreign('inventory_set_id', 'set_id')
                ->references('id')->on('inventory_sets');
        });

        Schema::table('inventory_slots', function (Blueprint $table) {
            $table->dropForeign('inventory_slots_inventory_id_foreign');
            $table->foreign('inventory_id', 'inventory_slots_inventory_id_foreign')
                ->references('id')->on('inventories');
        });
    }

    private function revertExistingCharacterFksToCascade(): void
    {
        $cascadeFks = [
            'inventories' => 'inventories_character_id_foreign',
            'inventory_sets' => 'inventory_sets_character_id_foreign',
            'gem_bags' => 'gem_bags_character_id_foreign',
            'skills' => 'skills_character_id_foreign',
            'character_boons' => 'character_boons_character_id_foreign',
            'character_class_ranks' => 'character_class_ranks_character_id_foreign',
            'character_class_specialties_equipped' => 'cc_se_c_id',
            'character_in_celestial_fights' => 'character_in_celestial_fights_character_id_foreign',
            'character_automations' => 'ca_cid',
            'character_passive_skills' => 'c_cps',
            'factions' => 'ca_fid',
            'faction_loyalties' => 'faction_loyalties_character_id_foreign',
            'quests_completed' => 'quests_completed_character_id_foreign',
            'raid_boss_participations' => 'raid_boss_participations_character_id_foreign',
            'raid_participations' => 'raid_participations_character_id_foreign',
            'unit_movement_queue' => 'uimq_cid',
            'units_in_queue' => 'uiq_cid',
            'maps' => 'maps_character_id_foreign',
            'market_board' => 'mb_character',
            'kingdom_logs' => 'kl_character_id',
            'buildings_in_queue' => 'biq_cid',
            'event_goal_participation_kills' => 'event_goal_participation_kills_character_id_foreign',
        ];

        foreach ($cascadeFks as $table => $constraintName) {
            Schema::table($table, function (Blueprint $blueprint) use ($constraintName) {
                $blueprint->dropForeign($constraintName);
                $blueprint->foreign('character_id', $constraintName)
                    ->references('id')->on('characters');
            });
        }
    }

    private function revertCelestialFightsFkToSetNull(): void
    {
        Schema::table('celestial_fights', function (Blueprint $table) {
            $table->dropForeign('celestial_fights_character_id_foreign');
            $table->foreign('character_id', 'celestial_fights_character_id_foreign')
                ->references('id')->on('characters');
        });
    }

    private function revertKingdomsFkToSetNull(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->dropForeign('king_cid');
            $table->foreign('character_id', 'king_cid')
                ->references('id')->on('characters');
        });
    }

    private function revertUserIdNullableOnMessages(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign('messages_user_id_foreign');
            $table->dropForeign('messages_from_user_foreign');
            $table->dropForeign('messages_to_user_foreign');

            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            $table->foreign('user_id', 'messages_user_id_foreign')
                ->references('id')->on('users');
            $table->foreign('from_user', 'messages_from_user_foreign')
                ->references('id')->on('users');
            $table->foreign('to_user', 'messages_to_user_foreign')
                ->references('id')->on('users');
        });
    }

    private function revertCharacterIdNullableOnSuggestions(): void
    {
        Schema::table('suggestion_and_bugs', function (Blueprint $table) {
            $table->dropForeign('sab_character_id_foreign');
            $table->unsignedBigInteger('character_id')->nullable(false)->change();
        });
    }
};
