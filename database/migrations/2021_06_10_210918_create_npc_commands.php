<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNpcCommands extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('npc_commands', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('npc_id')->unsigned();
            $table->foreign('npc_id')
                  ->references('id')->on('npcs');
            $table->string('command')->unique();
            $table->integer('command_type');
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
        Schema::dropIfExists('npc_commands');
    }
}
