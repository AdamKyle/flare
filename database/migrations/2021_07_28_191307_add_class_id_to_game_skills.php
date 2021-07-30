<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClassIdToGameSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_skills', function (Blueprint $table) {
            $table->dropColumn('specifically_assigned');
            $table->bigInteger('game_class_id')->unsigned()->nullable();
            $table->foreign('game_class_id')
                ->references('id')->on('game_classes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_skills', function (Blueprint $table) {
            $table->boolean('specifically_assigned')->default(false);
            $table->dropForeign(['game_class_id']);
            $table->dropColumn('game_class_id');
        });
    }
}
