<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCharacterBoons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('character_boons', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('stat_bonus');
            $table->dropColumn('affect_skill_type');
            $table->dropColumn('skill_bonus');
            $table->dropColumn('base_damage_mod_bonus');
            $table->dropColumn('base_healing_mod_bonus');
            $table->dropColumn('base_ac_mod_bonus');
            $table->dropColumn('base_ac_mod');
            $table->dropColumn('base_damage_mod');
            $table->dropColumn('base_healing_mod');
            $table->dropColumn('fight_time_out_mod_bonus');
            $table->dropColumn('move_time_out_mod_bonus');
            $table->dropColumn('skill_training_bonus');
            $table->dropColumn('str_mod');
            $table->dropColumn('dur_mod');
            $table->dropColumn('dex_mod');
            $table->dropColumn('int_mod');
            $table->dropColumn('chr_mod');
            $table->dropColumn('focus_mod');
            $table->dropColumn('agi_mod');

            $table->bigInteger('item_id')->unsigned();
            $table->foreign('item_id')
                  ->references('id')->on('items');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We do not want to go backwards.
    }
}
