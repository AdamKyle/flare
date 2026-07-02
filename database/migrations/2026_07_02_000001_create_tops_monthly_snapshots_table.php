<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tops_monthly_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('board_type');
            $table->string('metric_key');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('rank');
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->bigInteger('score_integer')->nullable();
            $table->decimal('score_decimal', 20, 6)->nullable();
            $table->json('snapshot_data');
            $table->timestamps();

            $table->unique(['board_type', 'metric_key', 'period_start', 'rank'], 'tops_monthly_snapshot_unique_rank');
            $table->index(['board_type', 'metric_key', 'period_start'], 'tops_monthly_snapshot_board_metric_period');
            $table->index(['character_id', 'period_start'], 'tops_monthly_snapshot_character_period');
            $table->index(['subject_type', 'subject_id'], 'tops_monthly_snapshot_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tops_monthly_snapshots');
    }
};
