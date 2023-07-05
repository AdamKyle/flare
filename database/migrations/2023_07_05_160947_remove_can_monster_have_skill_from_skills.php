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
        Schema::table('game_skills', function (Blueprint $table) {
            $table->dropColumn('can_monsters_have_skill');
            $table->decimal('class_bonus', 12, 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_skills', function (Blueprint $table) {
            //
        });
    }
};
