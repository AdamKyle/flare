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
        Schema::create('dwelve_explorations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('monster_id');
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->string('attack_type');
            $table->decimal('increase_enemy_strength', 8, 4)->default(0);
            $table->json('battle_messages')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dwelve_explorations');
    }
};
