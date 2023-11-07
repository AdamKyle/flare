<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->integer('only_during_event_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('game_maps', function (Blueprint $table) {
            //
        });
    }
};
