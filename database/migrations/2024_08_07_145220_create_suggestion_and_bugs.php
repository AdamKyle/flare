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
        Schema::create('suggestion_and_bugs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->UnsignedBigInteger('character_id');
            $table->string('title');
            $table->string('type');
            $table->string('platform');
            $table->text('description');
            $table->json('uploaded_image_paths');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_and_bugs');
    }
};
