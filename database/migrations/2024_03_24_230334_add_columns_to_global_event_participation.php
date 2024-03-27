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
        Schema::table('global_event_participation', function (Blueprint $table) {
            $table->integer('current_kills')->nullable()->change();
            $table->integer('current_crafts')->nullable();
            $table->integer('current_enchants')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_event_participation', function (Blueprint $table) {
            //
        });
    }
};
