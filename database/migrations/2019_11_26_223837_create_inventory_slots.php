<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventorySlots extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('inventory_id')->unsigned();
            $table->foreign('inventory_id')
                ->references('id')->on('inventories');
            $table->bigInteger('item_id')->unsigned();
            $table->foreign('item_id')
                ->references('id')->on('items');
            $table->timestamps();
            $table->boolean('equipped')->nullable()->default(false);
            $table->string('position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_slots');
    }
}
