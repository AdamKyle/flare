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
        Schema::create('global_event_crafting_inventory_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('global_event_crafting_inventory_id');
            $table->unsignedBigInteger('item_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_event_crafting_inventory_slots');
    }
};
