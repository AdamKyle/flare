<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewSkillModifiersToItemAffixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->integer('affects_skill_type')->nullable();
            $table->decimal('base_damage_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('base_healing_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('base_ac_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('fight_time_out_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('move_time_out_mod_bonus', 8, 4)->nullable()->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->dropColumn('affects_skill_type');
            $table->dropColumn('base_damage_mod_bonus');
            $table->dropColumn('base_healing_mod_bonus');
            $table->dropColumn('base_ac_mod_bonus');
            $table->dropColumn('fight_time_out_mod_bonus');
            $table->dropColumn('move_time_out_mod_bonus');
        });
    }
}
