<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNpcQuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('npc_quests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('npc_id')->unsigned()->nullable();
            $table->foreign('npc_id')
                ->references('id')->on('npcs');
            $table->bigInteger('quest_id')->unsigned()->nullable();
            $table->foreign('quest_id')
                ->references('id')->on('quests');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('npc_quests');
    }
}
