<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentDetailsToQuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->boolean('is_parent')->default(false);
            $table->bigInteger('parent_quest_id')->unsigned()->nullable();
            $table->bigInteger('secondary_required_item')->unsigned()->nullable();
            $table->foreign('secondary_required_item')
                  ->references('id')->on('items');
            $table->unsignedBigInteger('faction_game_map_id')->nullable();;
            $table->foreign('faction_game_map_id', 'gmid_gm')
                  ->references('id')->on('game_maps');
            $table->integer('required_faction_level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropForeign(['secondary_required_item']);
            $table->dropForeign(['faction_game_map_id']);
            $table->dropColumn('is_parent');
            $table->dropColumn('parent_quest_id');
            $table->dropColumn('secondary_required_item');
            $table->dropColumn('faction_map_id');
            $table->dropColumn('required_faction_level');
        });
    }
}
