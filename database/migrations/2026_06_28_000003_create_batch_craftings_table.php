<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_craftings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('batch_type');
            $table->string('disposition');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('ended_reason')->nullable();
            $table->string('status')->default('running');
            $table->json('progress')->nullable();
            $table->boolean('info_acknowledged')->default(false);
            $table->timestamp('panel_dismissed_at')->nullable();
            $table->json('selected_items')->nullable();
            $table->json('selected_oils')->nullable();
            $table->unsignedInteger('crafted_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->unsignedInteger('destroyed_count')->default(0);
            $table->unsignedInteger('listed_count')->default(0);
            $table->unsignedInteger('kept_count')->default(0);
            $table->unsignedInteger('applied_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index(['character_id', 'completed_at', 'cancelled_at'], 'batch_craftings_active_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_craftings');
    }
};
