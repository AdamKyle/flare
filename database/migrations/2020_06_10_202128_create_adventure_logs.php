<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdventureLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adventure_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('character_id')->unsigned();
            $table->foreign('character_id')
                  ->references('id')->on('characters');
            $table->bigInteger('adventure_id')->unsigned();
            $table->boolean('in_progress')->nullable()->default(false);
            $table->boolean('complete')->nullable()->default(false);
            $table->integer('last_completed_level')->nullable();
            $table->json('logs')->nullable();
            $table->json('rewards')->nullable();
            $table->boolean('took_to_long')->nullable()->default(false);
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
        Schema::dropIfExists('adventure_logs');
    }
}
