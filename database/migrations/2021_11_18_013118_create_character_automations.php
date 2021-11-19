<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacterAutomations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('character_automations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'ca_cid')
                  ->references('id')->on('characters');
            $table->unsignedBigInteger('monster_id');
            $table->foreign('monster_id', 'ca_mid')
                  ->references('id')->on('monsters');
            $table->integer('type');
            $table->dateTime('started_at');
            $table->dateTime('completed_at');
            $table->string('attack_type')->nullable();
            $table->integer('move_down_monster_list_every')->nullable()->default(0);
            $table->integer('previous_level')->nullable()->default(0);
            $table->integer('current_level')->nullable()->default(0);
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
        Schema::dropIfExists('character_automations');
    }
}
