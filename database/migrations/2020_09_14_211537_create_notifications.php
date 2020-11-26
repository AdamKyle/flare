<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('character_id')->unsigned();
            $table->foreign('character_id')
                  ->references('id')->on('characters');
            $table->bigInteger('adventure_id')->unsigned()->nullable();
            $table->foreign('adventure_id')
                ->references('id')->on('adventures');
            $table->string('title');
            $table->string('message');
            $table->string('status');
            $table->string('type');
            $table->boolean('read')->nullable()->default(false);
            $table->string('url')->default('');
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
        Schema::dropIfExists('notifications');
    }
}
