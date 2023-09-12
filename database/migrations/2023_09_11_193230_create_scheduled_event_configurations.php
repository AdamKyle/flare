<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('scheduled_event_configurations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('event_type');
            $table->dateTime('start_date');
            $table->enum('generate_every', ['weekly', 'monthly']);
            $table->date('last_time_generated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('scheduled_event_configurations');
    }
};
