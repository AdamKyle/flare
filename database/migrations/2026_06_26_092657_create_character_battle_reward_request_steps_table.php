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
        Schema::create('character_battle_reward_request_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_battle_reward_request_id');
            $table->foreignId('character_id');
            $table->string('step_name');
            $table->string('status');
            $table->json('payload_json')->nullable();
            $table->json('result_json')->nullable();
            $table->json('checkpoint_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->unique(
                ['character_battle_reward_request_id', 'step_name'],
                'character_reward_request_steps_request_step_unique',
            );
            $table->index(
                ['character_id', 'status', 'heartbeat_at'],
                'character_reward_request_steps_character_status_heartbeat',
            );
            $table->index(
                ['character_battle_reward_request_id', 'status'],
                'character_reward_request_steps_request_status',
            );
            $table->index(
                ['step_name', 'status'],
                'character_reward_request_steps_step_status',
            );
            $table->index('completed_at', 'character_reward_request_steps_completed_at');
            $table->index('failed_at', 'character_reward_request_steps_failed_at');
            $table->foreign('character_battle_reward_request_id', 'cbr_request_steps_request_fk')
                ->references('id')
                ->on('character_battle_reward_requests')
                ->cascadeOnDelete();
            $table->foreign('character_id', 'cbr_request_steps_character_fk')
                ->references('id')
                ->on('characters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_battle_reward_request_steps');
    }
};
