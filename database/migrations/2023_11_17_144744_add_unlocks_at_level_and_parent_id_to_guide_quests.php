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
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('unlock_at_level')->nullable();
            $table->integer('only_during_event')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            //
        });
    }
};
