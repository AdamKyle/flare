<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')
                ->references('id')->on('users');
            $table->bigInteger('from_user')->unsigned()->nullable();
            $table->foreign('from_user')
                ->references('id')->on('users');
            $table->bigInteger('to_user')->unsigned()->nullable();
            $table->foreign('to_user')
                ->references('id')->on('users');
            $table->text('message');
            $table->integer('x_position')->nullable()->default(0);
            $table->integer('y_position')->nullable()->default(0);
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
        Schema::dropIfExists('messages');
    }
}
