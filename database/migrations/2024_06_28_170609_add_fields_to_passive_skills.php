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
        Schema::table('passive_skills', function (Blueprint $table) {
            $table->decimal('capital_city_building_request_travel_time_reduction', 12, 8)->nullable();
            $table->decimal('capital_city_unit_request_travel_time_reduction', 12, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('passive_skills', function (Blueprint $table) {
            //
        });
    }
};
