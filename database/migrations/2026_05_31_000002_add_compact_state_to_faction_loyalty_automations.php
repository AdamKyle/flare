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
        Schema::table('faction_loyalty_automations', function (Blueprint $table) {
            $table->string('last_automation_action')->nullable()->after('failed_crafting_item_id');
            $table->dateTime('last_automation_action_at')->nullable()->after('last_automation_action');
            $table->unsignedBigInteger('last_fight_monster_id')->nullable()->after('last_automation_action_at');
            $table->string('last_fight_outcome')->nullable()->after('last_fight_monster_id');
            $table->boolean('last_fight_was_bounty_target')->default(false)->after('last_fight_outcome');
            $table->boolean('last_fight_was_training')->default(false)->after('last_fight_was_bounty_target');
            $table->unsignedInteger('last_fight_stalled_attempt')->default(0)->after('last_fight_was_training');
            $table->unsignedBigInteger('trained_failed_bounty_monster_id')->nullable()->after('last_fight_stalled_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faction_loyalty_automations', function (Blueprint $table) {
            $table->dropColumn([
                'last_automation_action',
                'last_automation_action_at',
                'last_fight_monster_id',
                'last_fight_outcome',
                'last_fight_was_bounty_target',
                'last_fight_was_training',
                'last_fight_stalled_attempt',
                'trained_failed_bounty_monster_id',
            ]);
        });
    }
};
