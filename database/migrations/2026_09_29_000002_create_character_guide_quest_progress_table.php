<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_guide_quest_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guide_quest_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('current_guide_quest_walkthrough_step_id')->nullable();
            $table->foreign('current_guide_quest_walkthrough_step_id', 'cgqp_current_step_fk')
                ->references('id')
                ->on('guide_quest_walkthrough_steps')
                ->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('walkthrough_completed_at')->nullable();
            $table->timestamp('hand_in_ready_at')->nullable();
            $table->timestamp('attention_acknowledged_at')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'guide_quest_id'], 'character_guide_quest_progress_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_guide_quest_progress');
    }
};
