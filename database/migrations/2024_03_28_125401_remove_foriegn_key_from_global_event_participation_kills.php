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
        Schema::table('event_goal_participation_kills', function (Blueprint $table) {
            $table->dropForeign('event_goal_participation_kills_global_event_goal_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_goal_participation_kills', function (Blueprint $table) {
            //
        });
    }
};
