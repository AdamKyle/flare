<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alchemy_bag_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alchemy_bag_id');
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('amount')->default(1);
            $table->timestamps();

            $table->unique(['alchemy_bag_id', 'item_id'], 'alchemy_bag_slots_bag_item_unique');
            $table->index('character_id', 'alchemy_bag_slots_character_id_index');

            $table->foreign('alchemy_bag_id', 'alchemy_bag_slots_bag_id_foreign')
                ->references('id')->on('alchemy_bags')
                ->cascadeOnDelete();
            $table->foreign('character_id', 'alchemy_bag_slots_character_id_foreign')
                ->references('id')->on('characters')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alchemy_bag_slots');
    }
};
