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
            $table->bigInteger('gold_reward')->change();
            $table->bigInteger('shards_reward')->change();
            $table->bigInteger('gold_dust_reward')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        //
    }
};
