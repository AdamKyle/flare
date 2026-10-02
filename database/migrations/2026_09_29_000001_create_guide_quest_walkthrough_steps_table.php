<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the ordered Guide Quest walkthrough steps table.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('guide_quest_walkthrough_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guide_quest_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title');
            $table->text('content');
            $table->string('target')->nullable();
            $table->string('step_type');
            $table->string('required_event')->nullable();
            $table->boolean('optional_if_target_missing')->default(false);
            $table->timestamps();
            $table->unique(['guide_quest_id', 'position'], 'guide_quest_walkthrough_position_unique');
        });
    }

    /**
     * Drop the Guide Quest walkthrough steps table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_quest_walkthrough_steps');
    }
};
