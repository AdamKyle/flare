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
        Schema::create('character_battle_reward_request_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_battle_reward_request_id');
            $table->foreignId('character_id');
            $table->foreignId('user_id');
            $table->string('step_name')->nullable();
            $table->text('message');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->string('source')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('link_text')->nullable();
            $table->timestamp('emitted_at')->nullable();
            $table->timestamps();

            $table->index(
                ['character_battle_reward_request_id', 'emitted_at'],
                'character_reward_request_messages_request_emitted',
            );
            $table->index(
                ['character_id', 'emitted_at'],
                'character_reward_request_messages_character_emitted',
            );
            $table->index(
                ['user_id', 'emitted_at'],
                'character_reward_request_messages_user_emitted',
            );
            $table->index('step_name', 'character_reward_request_messages_step_name');
            $table->index('created_at', 'character_reward_request_messages_created_at');
            $table->foreign('character_battle_reward_request_id', 'cbr_request_messages_request_fk')
                ->references('id')
                ->on('character_battle_reward_requests')
                ->cascadeOnDelete();
            $table->foreign('character_id', 'cbr_request_messages_character_fk')
                ->references('id')
                ->on('characters');
            $table->foreign('user_id', 'cbr_request_messages_user_fk')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_battle_reward_request_messages');
    }
};
