<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAlchemyCostsToItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->bigInteger('gold_dust_cost')->nullable()->defeault(0);
            $table->bigInteger('shards_cost')->nullable()->defeault(0);
            $table->boolean('usable')->default(false);
            $table->boolean('damages_kingdoms')->default(false);
            $table->decimal('kingdom_damage', 8, 4)->nullable()->default(0.0);
            $table->integer('lasts_for')->nullable();
            $table->string('stat_increase')->nullable();
            $table->decimal('increase_stat_by', 8, 4)->nullable()->default(0.0);
            $table->integer('affects_skill_type')->nullable();
            $table->decimal('increase_skill_bonus_by', 8, 4)->nullable()->default(0.0);
            $table->decimal('increase_skill_training_bonus_by', 8, 4)->nullable()->default(0.0);
            $table->decimal('base_damage_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('base_healing_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('base_ac_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('fight_time_out_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('move_time_out_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('spell_evasion', 8, 4)->nullable()->default(0.0);
            $table->decimal('artifact_annulment', 8, 4)->nullable()->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('gold_dust_cost');
            $table->dropColumn('shards_cost');
            $table->dropColumn('gold_dust_cost');
            $table->dropColumn('shards_cost');
            $table->dropColumn('usable');
            $table->dropColumn('damages_kingdoms');
            $table->dropColumn('kingdom_damage');
            $table->dropColumn('lasts_for');
            $table->dropColumn('stat_increase');
            $table->dropColumn('increase_stat_by');
            $table->dropColumn('affects_skill_type');
            $table->dropColumn('increase_skill_bonus_by');
            $table->dropColumn('increase_skill_training_bonus_by');
            $table->dropColumn('base_damage_mod_bonus');
            $table->dropColumn('base_healing_mod_bonus');
            $table->dropColumn('base_ac_mod_bonus');
            $table->dropColumn('fight_time_out_mod_bonus');
            $table->dropColumn('move_time_out_mod_bonus');
            $table->dropColumn('spell_evasion');
            $table->dropColumn('artifact_annulment');
        });
    }
}
