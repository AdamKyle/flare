<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketBoard extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_board', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'mb_character')
                  ->references('id')->on('characters');
            $table->unsignedBigInteger('item_id')->unassigned();
            $table->foreign('item_id')
                  ->references('id')->on('items');
            $table->integer('listed_price');
            $table->boolean('is_locked')->nullable()->default(false);
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
        Schema::dropIfExists('market_board');
    }
}
