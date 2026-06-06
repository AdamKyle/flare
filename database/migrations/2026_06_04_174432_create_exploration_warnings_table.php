<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exploration_warnings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('exploration_log_id')->nullable();

            $table->string('type');
            $table->text('message');

            $table->timestamps();

            $table->index(['character_id', 'id'], 'exploration_warnings_character_id_id_index');
            $table->index('exploration_log_id', 'exploration_warnings_log_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exploration_warnings');
    }
};
