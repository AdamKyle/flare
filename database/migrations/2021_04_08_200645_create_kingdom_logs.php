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
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'kl_character_id')
                ->references('id')->on('characters');
            $table->unsignedBigInteger('from_kingdom_id')->nullable();
            $table->foreign('from_kingdom_id', 'kl_from_king_id')
                ->references('id')->on('kingdoms');
            $table->unsignedBigInteger('to_kingdom_id')->nullable();
            $table->foreign('to_kingdom_id', 'kl_to_king_id')
                ->references('id')->on('kingdoms');
            $table->enum('status', ['attacked kingdom', 'lost attack', 'taken kingdom', 'lost kingdom', 'kingdom attacked', 'units returning']);
            $table->json('units_sent')->nullable();
            $table->json('units_survived')->nullable();
            $table->json('old_defender')->nullable();
            $table->json('new_defender')->nullable();
            $table->boolean('published');
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
