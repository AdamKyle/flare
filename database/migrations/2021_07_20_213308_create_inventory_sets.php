<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventorySets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('character_id')->unsigned();
            $table->foreign('character_id')
                ->references('id')->on('characters');
            $table->boolean('is_equipped')->default(false);
            $table->boolean('can_be_equipped')->default(true);
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
        Schema::dropIfExists('inventory_sets');
    }
}
