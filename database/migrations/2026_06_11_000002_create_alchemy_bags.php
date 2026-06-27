<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alchemy_bags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->timestamps();

            $table->unique('character_id', 'alchemy_bags_character_id_unique');
            $table->foreign('character_id', 'alchemy_bags_character_id_foreign')
                ->references('id')->on('characters')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alchemy_bags');
    }
};
