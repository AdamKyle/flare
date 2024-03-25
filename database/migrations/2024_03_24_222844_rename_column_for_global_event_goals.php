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
        Schema::table('global_event_goals', function (Blueprint $table) {
            $table->renameColumn('reward_every_kills', 'reward_every');
            $table->integer('max_crafts')->nullable()->change();
            $table->integer('max_kills')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
