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
        Schema::table('locations', function (Blueprint $table) {
            $table->bigInteger('raid_id')->nullable();
            $table->boolean('has_raid_boss')->default(false);
            $table->boolean('is_corrupted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('raid_id');
            $table->dropColumn('has_raid_boss');
            $table->dropColumn('is_corrupted');
        });
    }
};
