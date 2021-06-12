<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllCharacterIdToBeNullOnKingdoms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->bigInteger('character_id')->unsigned()->nullable()->change();
            $table->boolean('npc_owned')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->bigInteger('character_id')->unsigned()->nullable(false)->change();
            $table->dropColumn('npc_owned');
        });
    }
}
