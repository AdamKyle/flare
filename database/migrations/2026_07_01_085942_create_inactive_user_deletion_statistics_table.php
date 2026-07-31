<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inactive_user_deletion_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('deleted_count');
            $table->timestamp('tracked_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inactive_user_deletion_statistics');
    }
};
