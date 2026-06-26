<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_log_file_states', function (Blueprint $table): void {
            $table->id();
            $table->string('channel_key');
            $table->string('file_path');
            $table->unsignedBigInteger('position')->default(0);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_key', 'file_path'], 'monitored_log_file_states_channel_file_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_log_file_states');
    }
};
