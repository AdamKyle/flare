<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_battle_reward_queue_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->unique()->constrained('characters');
            $table->boolean('is_processing')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamps();

            $table->index(
                ['is_processing', 'updated_at'],
                'character_reward_queue_states_processing_index',
            );
            $table->index('heartbeat_at', 'character_reward_queue_states_heartbeat_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_battle_reward_queue_states');
    }
};
