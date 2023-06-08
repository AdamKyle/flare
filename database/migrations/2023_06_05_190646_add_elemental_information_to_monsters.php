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
            $table->decimal('fire_atonement', 8, 4)->nullable();
            $table->decimal('ice_atonement', 8, 4)->nullable();
            $table->decimal('water_atonement', 8, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monsters', function (Blueprint $table) {
            //
        });
    }
};
