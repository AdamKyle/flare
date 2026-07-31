<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_event_crafting_inventories', function (Blueprint $table) {
            $table->renameColumn('global_event_id', 'global_event_goal_id');
        });

        $this->guardAgainstDuplicates('global_event_participation', 'global_event_goal_id');
        $this->guardAgainstDuplicates('event_goal_participation_kills', 'global_event_goal_id');
        $this->guardAgainstDuplicates('event_goal_participation_crafts', 'global_event_goal_id');
        $this->guardAgainstDuplicates('event_goal_participation_enchants', 'global_event_goal_id');
        $this->guardAgainstDuplicates('global_event_crafting_inventories', 'global_event_goal_id');

        Schema::table('global_event_participation', function (Blueprint $table) {
            $table->foreign('global_event_goal_id', 'gep_global_event_goal_fk')
                ->references('id')->on('global_event_goals')
                ->cascadeOnDelete();

            $table->unique(['global_event_goal_id', 'character_id'], 'gep_goal_character_unique');
        });

        Schema::table('event_goal_participation_kills', function (Blueprint $table) {
            $table->foreign('global_event_goal_id', 'egpk_global_event_goal_fk')
                ->references('id')->on('global_event_goals')
                ->cascadeOnDelete();

            $table->unique(['global_event_goal_id', 'character_id'], 'egpk_goal_character_unique');
        });

        Schema::table('event_goal_participation_crafts', function (Blueprint $table) {
            $table->foreign('global_event_goal_id', 'egpc_global_event_goal_fk')
                ->references('id')->on('global_event_goals')
                ->cascadeOnDelete();

            $table->unique(['global_event_goal_id', 'character_id'], 'egpc_goal_character_unique');
        });

        Schema::table('event_goal_participation_enchants', function (Blueprint $table) {
            $table->foreign('global_event_goal_id', 'egpe_global_event_goal_fk')
                ->references('id')->on('global_event_goals')
                ->cascadeOnDelete();

            $table->unique(['global_event_goal_id', 'character_id'], 'egpe_goal_character_unique');
        });

        Schema::table('global_event_crafting_inventories', function (Blueprint $table) {
            $table->foreign('global_event_goal_id', 'gec_inv_global_event_goal_fk')
                ->references('id')->on('global_event_goals')
                ->cascadeOnDelete();

            $table->unique(['global_event_goal_id', 'character_id'], 'gec_inv_goal_character_unique');
        });
    }

    private function guardAgainstDuplicates(string $table, string $goalColumn): void
    {
        $duplicate = DB::table($table)
            ->select($goalColumn, 'character_id', DB::raw('COUNT(*) as total'))
            ->groupBy($goalColumn, 'character_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if (! is_null($duplicate)) {
            throw new RuntimeException(
                'Duplicate rows found on table: ' . $table . ' for key: (' . $goalColumn . ', character_id) = ('
                . $duplicate->{$goalColumn} . ', ' . $duplicate->character_id . '). Refusing to add unique constraint.'
            );
        }
    }

    public function down(): void
    {
        Schema::table('global_event_crafting_inventories', function (Blueprint $table) {
            $table->dropUnique('gec_inv_goal_character_unique');
            $table->dropForeign('gec_inv_global_event_goal_fk');
        });

        Schema::table('event_goal_participation_enchants', function (Blueprint $table) {
            $table->dropUnique('egpe_goal_character_unique');
            $table->dropForeign('egpe_global_event_goal_fk');
        });

        Schema::table('event_goal_participation_crafts', function (Blueprint $table) {
            $table->dropUnique('egpc_goal_character_unique');
            $table->dropForeign('egpc_global_event_goal_fk');
        });

        Schema::table('event_goal_participation_kills', function (Blueprint $table) {
            $table->dropUnique('egpk_goal_character_unique');
            $table->dropForeign('egpk_global_event_goal_fk');
        });

        Schema::table('global_event_participation', function (Blueprint $table) {
            $table->dropUnique('gep_goal_character_unique');
            $table->dropForeign('gep_global_event_goal_fk');
        });

        Schema::table('global_event_crafting_inventories', function (Blueprint $table) {
            $table->renameColumn('global_event_goal_id', 'global_event_id');
        });
    }
};
