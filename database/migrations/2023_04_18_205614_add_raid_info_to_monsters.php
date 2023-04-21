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
        Schema::table('monsters', function (Blueprint $table) {
            $table->boolean('is_raid_monster')->default(false);
            $table->boolean('is_raid_boss')->default(false);
            $table->integer('raid_special_attack_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->dropColumn('is_raid_monster');
            $table->dropColumn('is_raid_boss');
            $table->dropColumn('raid_special_attack_type');
        });
    }
};
