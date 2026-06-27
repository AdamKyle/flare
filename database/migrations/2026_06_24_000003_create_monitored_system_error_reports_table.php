<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_system_error_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint')->unique();
            $table->string('title');
            $table->string('status')->default('open');
            $table->string('severity')->nullable();
            $table->string('environment')->nullable();
            $table->string('exception_class')->nullable();
            $table->text('latest_message')->nullable();
            $table->text('latest_stack_trace')->nullable();
            $table->longText('latest_raw_log_entry')->nullable();
            $table->unsignedInteger('occurrence_count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_seen_at'], 'monitored_system_errors_status_last_seen_index');
        });

        Schema::create('monitored_system_error_occurrences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('monitored_system_error_report_id');
            $table->timestamp('occurred_at')->nullable();
            $table->string('level')->nullable();
            $table->string('channel')->nullable();
            $table->string('file_path')->nullable();
            $table->text('message')->nullable();
            $table->string('exception_class')->nullable();
            $table->string('exception_file')->nullable();
            $table->unsignedInteger('exception_line')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->longText('raw_log_entry')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('request_path')->nullable();
            $table->string('job_class')->nullable();
            $table->string('queue')->nullable();
            $table->string('environment')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('occurred_at', 'monitored_system_error_occurrences_occurred_index');
            $table->foreign(
                'monitored_system_error_report_id',
                'monitored_error_occurrences_report_fk',
            )
                ->references('id')
                ->on('monitored_system_error_reports')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_system_error_occurrences');
        Schema::dropIfExists('monitored_system_error_reports');
    }
};
