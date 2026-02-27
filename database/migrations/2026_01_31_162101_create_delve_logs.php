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
        Schema::create('delve_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('delve_exploration_id');

            $table->unsignedTinyInteger('pack_size')->default(1);
            $table->decimal('increased_enemy_strength', 8, 4)->default(0);

            $table->enum('outcome', ['survived', 'died', 'bailed', 'timeout', 'error'])->index();

            $table->json('fight_data');

            $table->timestamps();

            $table->index('character_id');
            $table->index('delve_exploration_id');
            $table->index(['character_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delve_logs');
    }
};
