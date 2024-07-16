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
        Schema::create('capital_city_building_cancellations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('building_id');
            $table->unsignedBigInteger('kingdom_id');
            $table->unsignedBigInteger('request_kingdom_id');
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('capital_city_building_queue_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_city_building_cancellations');
    }
};
