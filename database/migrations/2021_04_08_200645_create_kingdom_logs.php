<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKingdomLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kingdom_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_kingdom_id');
            $table->foreign('from_kingdom_id', 'uimq_from_king_id')
                ->references('id')->on('kingdoms');
            $table->unsignedBigInteger('to_kingdom_id');
            $table->foreign('to_kingdom_id', 'uimq_to_king_id')
                ->references('id')->on('kingdoms');
            $table->enum('status', ['attacked', 'lost', 'taken']);
            $table->json('units_sent');
            $table->json('units_survived');
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
        Schema::dropIfExists('kingdom_logs');
    }
}
