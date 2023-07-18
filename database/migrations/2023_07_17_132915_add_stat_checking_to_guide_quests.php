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
            $table->integer('required_stats')->nullable();
            $table->integer('required_str')->nullable();
            $table->integer('required_dex')->nullable();
            $table->integer('required_int')->nullable();
            $table->integer('required_dur')->nullable();
            $table->integer('required_chr')->nullable();
            $table->integer('required_agi')->nullable();
            $table->integer('required_focus')->nullable();
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
