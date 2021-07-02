<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('npc_id')->unsigned();
            $table->foreign('npc_id')
                ->references('id')->on('npcs');
            $table->bigInteger('item_id')->unsigned()->nullable();
            $table->foreign('item_id')
                ->references('id')->on('items');
            $table->bigInteger('gold_dust_cost')->nullable()->default(0);
            $table->bigInteger('shard_cost')->nullable()->default(0);
            $table->bigInteger('gold_cost')->nullable()->default(0);
            $table->bigInteger('reward_item')->unsigned()->nullable();
            $table->foreign('reward_item')
                ->references('id')->on('items');
            $table->bigInteger('reward_gold_dust')->nullable()->default(0);
            $table->bigInteger('reward_shards')->nullable()->default(0);
            $table->bigInteger('reward_gold')->nullable()->default(0);
            $table->bigInteger('reward_xp')->nullable()->default(0);
            $table->boolean('unlocks_skill')->default(false);
            $table->integer('unlocks_skill_type')->nullable();
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
        Schema::dropIfExists('quests');
    }
}
