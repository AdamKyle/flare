<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequiresAccessToPlaneToQuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->bigInteger('access_to_map_id')->unsigned()->nullable();
            $table->foreign('access_to_map_id')
                ->references('id')
                ->on('game_maps');
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
            $table->dropForeign(['access_to_map_id']);
            $table->dropColumn('access_to_map_id');
        });
    }
}
