<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('global_event_goals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('max_kills');
            $table->bigInteger('reward_every_kills');
            $table->bigInteger('next_reward_at');
            $table->integer('event_type');
            $table->integer('item_specialty_type_reward');
            $table->boolean('should_be_unique');
            $table->integer('unique_type');
            $table->boolean('should_be_mythic');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('global_event_goals');
    }
};
