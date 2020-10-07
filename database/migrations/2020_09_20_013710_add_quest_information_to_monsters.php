<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuestInformationToMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->bigInteger('quest_item_id')->unsigned()->nullable();
            $table->foreign('quest_item_id')
                ->references('id')->on('items');
            $table->decimal('quest_item_drop_chance', 5, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->dropForeign('monsters_quest_item_id_foreign');
            $table->dropColumn('quest_item_id');
            $table->dropColumn('quest_item_drop_chance');
        });
    }
}
