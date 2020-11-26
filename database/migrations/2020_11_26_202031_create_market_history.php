<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('item_id')->unassigned();
            $table->foreign('item_id')
                  ->references('id')->on('items');
            $table->integer('sold_for');
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
        Schema::dropIfExists('market_history');
    }
}
