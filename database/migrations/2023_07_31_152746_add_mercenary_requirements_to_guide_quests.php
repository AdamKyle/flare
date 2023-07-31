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
            $table->string('required_mercenary_type')->nullable();
            $table->string('required_secondary_mercenary_type')->nullable();
            $table->integer('required_mercenary_level')->nullable();
            $table->integer('required_secondary_mercenary_level')->nullable();
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
