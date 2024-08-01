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
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->decimal('stone_increase', 12, 8)->nullable();
            $table->decimal('iron_increase', 12, 8)->nullable();
            $table->decimal('clay_increase', 12, 8)->nullable();
            $table->decimal('wood_increase', 12, 8)->nullable();
            $table->decimal('population_increase', 12, 8)->nullable();
            $table->decimal('steal_increase', 12, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            //
        });
    }
};
