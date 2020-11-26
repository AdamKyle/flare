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
            $table->bigInteger('character_id')->unassigned();
            $table->foreign('character_id')
                  ->references('id')->on('characters');
            $table->bigInteger('item_id')->unassigned();
            $table->foreign('item_id')
                  ->references('id')->on('items');
            $table->integer('listed_price');
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
