<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_units', function (Blueprint $table) {
            $table->integer('steel_cost')->after('iron_cost')->nullable()->default(0);
            $table->boolean('is_airship')->after('siege_weapon')->nullable()->default(false);
            $table->integer('wood_cost')->nullable()->default(0)->change();
            $table->integer('stone_cost')->nullable()->default(0)->change();
            $table->integer('iron_cost')->nullable()->default(0)->change();
            $table->integer('clay_cost')->nullable()->default(0)->change();
            $table->integer('required_population')->nullable()->default(0)->change();
            $table->boolean('attacker')->nullable()->default(false)->change();
            $table->boolean('defender')->nullable()->default(false)->change();
            $table->boolean('siege_weapon')->nullable()->default(false)->change();
            $table->boolean('can_heal')->nullable()->default(false)->change();
            $table->boolean('can_not_be_healed')->nullable()->default(false)->change();
            $table->boolean('is_settler')->nullable()->default(false)->change();
            $table->dropColumn('primary_target');
            $table->dropColumn('fall_back');
            $table->dropColumn('attacks_walls');
            $table->dropColumn('attacks_buildings');
            $table->dropColumn('travel_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_units', function (Blueprint $table) {
            $table->dropColumn('steel_cost');
            $table->dropColumn('is_airship');
        });
    }
};
