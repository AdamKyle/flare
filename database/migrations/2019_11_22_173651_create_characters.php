<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('game_race_id')->unsigned();
            $table->bigInteger('game_class_id')->unsigned();
            $table->foreign('game_race_id')
                ->references('id')->on('game_races');
            $table->foreign('game_class_id')
                ->references('id')->on('game_classes');
            $table->foreign('user_id')
                ->references('id')->on('users');
            $table->string('name')->unique();
            $table->string('damage_stat');
            $table->bigInteger('level')->nullable()->default(1);
            $table->decimal('xp', 8, 2);
            $table->bigInteger('xp_next');
            $table->bigInteger('str');
            $table->bigInteger('dur');
            $table->bigInteger('dex');
            $table->bigInteger('chr');
            $table->bigInteger('int');
            $table->bigInteger('ac');
            $table->bigInteger('gold')->nullable()->default(250);
            $table->integer('inventory_max')->nullable()->default(75);
            $table->boolean('can_attack')->nullable()->default(true);
            $table->boolean('can_adventure')->nullable()->default(true);
            $table->boolean('can_move')->nullable()->default(true);
            $table->boolean('can_craft')->nullable()->default(true);
            $table->boolean('is_dead')->nullable()->default(false);
            $table->dateTime('can_move_again_at')->nullable();
            $table->dateTime('can_attack_again_at')->nullable();
            $table->dateTime('can_craft_again_at')->nullable();
            $table->dateTime('can_adventure_again_at')->nullable();
            $table->boolean('force_name_change')->nullable()->default(false);
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
        Schema::dropIfExists('characters');
    }
}
