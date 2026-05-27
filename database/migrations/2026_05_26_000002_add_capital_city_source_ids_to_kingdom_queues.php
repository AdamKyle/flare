<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings_in_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('capital_city_building_queue_id')->nullable()->after('paid_amount')->index('buildings_in_queue_capital_city_building_queue_id_index');
        });

        Schema::table('units_in_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('capital_city_unit_queue_id')->nullable()->after('gold_paid')->index('units_in_queue_capital_city_unit_queue_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('buildings_in_queue', function (Blueprint $table) {
            $table->dropColumn('capital_city_building_queue_id');
        });

        Schema::table('units_in_queue', function (Blueprint $table) {
            $table->dropColumn('capital_city_unit_queue_id');
        });
    }
};
