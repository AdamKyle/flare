<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the nullable description to Game Classes.
     */
    public function up(): void
    {
        Schema::table('game_classes', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Remove the description from Game Classes.
     */
    public function down(): void
    {
        Schema::table('game_classes', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
