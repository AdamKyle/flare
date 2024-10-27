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
        Schema::create('capital_city_resource_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kingdom_requesting_id');
            $table->unsignedBigInteger('request_from_kingdom_id');
            $table->json('resources');
            $table->dateTime('started_at');
            $table->dateTime('completed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_city_resource_requests');
    }
};
