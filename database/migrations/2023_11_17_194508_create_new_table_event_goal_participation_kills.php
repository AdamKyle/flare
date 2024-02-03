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
        Schema::create('event_goal_participation_kills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('global_event_goal_id');
            $table->foreign('global_event_goal_id')->on('global_event_goals')->references('id');
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id')->on('characters')->references('id');
            $table->bigInteger('kills');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::table('event_goal_participation_kills', function (Blueprint $table) {
//            $table->dropForeign(['global_event_goal_id']);
//        });

        Schema::dropIfExists('new_table_event_goal_participation_kills');
    }
};
