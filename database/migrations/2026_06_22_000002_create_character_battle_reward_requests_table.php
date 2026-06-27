<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_battle_reward_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained('characters');
            $table->string('priority');
            $table->string('source_type');
            $table->string('source_id')->nullable();
            $table->json('handler_payload');
            $table->string('status');
            $table->text('failed_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['character_id', 'status', 'priority', 'id'],
                'character_reward_requests_processing_index',
            );
            $table->index(['status', 'created_at'], 'character_reward_requests_status_created_index');
            $table->index(
                ['priority', 'status', 'created_at'],
                'character_reward_requests_priority_status_index',
            );
            $table->index(
                ['source_type', 'source_id'],
                'character_reward_requests_source_index',
            );
            $table->index(
                ['character_id', 'source_type', 'source_id'],
                'character_reward_requests_character_source_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_battle_reward_requests');
    }
};
