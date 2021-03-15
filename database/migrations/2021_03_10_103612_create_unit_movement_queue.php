<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitMovementQueue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_movement_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kingdom_id');
            $table->foreign('kingdom_id', 'uimq_king_id')
                  ->references('id')->on('kingdoms');
            $table->json('units_moving');
            $table->dateTime('completed_at');
            $table->dateTime('started_at');
            $table->integer('moving_to_x');
            $table->integer('moving_to_y');
            $table->integer('from_x');
            $table->integer('from_y');
            $table->boolean('is_attacking')->nullable()->default(false);
            $table->boolean('is_recalled')->nullable()->default(false);
            $table->boolean('is_returning')->nullable()->default(false);
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
        Schema::dropIfExists('unit_movement_queue');
    }
}
