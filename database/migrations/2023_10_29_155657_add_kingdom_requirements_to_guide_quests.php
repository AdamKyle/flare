<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->string('required_kingdom_building_id')->nullable();
            $table->integer('required_kingdom_building_level')->nullable();
            $table->bigInteger('required_gold_bars')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('guide_quests', function (Blueprint $table) {
            //
        });
    }
};
