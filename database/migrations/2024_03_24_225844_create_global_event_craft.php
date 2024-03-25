<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_goal_participation_crafts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('global_event_goal_id');
            $table->unsignedBigInteger('character_id');
            $table->integer('crafts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_goal_participation_crafts');
    }
};
