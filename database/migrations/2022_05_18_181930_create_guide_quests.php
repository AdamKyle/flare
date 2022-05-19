<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuideQuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guide_quests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('intro_text');
            $table->longText('instructions');
            $table->bigInteger('required_level')->nullable();
            $table->bigInteger('required_skill')->nullable();
            $table->bigInteger('required_skill_level')->nullable();
            $table->bigInteger('required_faction_id')->nullable();
            $table->bigInteger('required_faction_level')->nullable();
            $table->bigInteger('required_game_map_id')->nullable();
            $table->bigInteger('required_quest_id')->nullable();
            $table->bigInteger('required_quest_item_id')->nullable();
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
        Schema::dropIfExists('guide_quests');
    }
}
