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
        Schema::create('capital_city_building_queues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('status');
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('kingdom_id');
            $table->unsignedBigInteger('requested_kingdom');
            $table->json('building_request_data');
            $table->json('messages')->nullable();
            $table->dateTime('completed_at');
            $table->dateTime('started_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_city_building_queues');
    }
};
