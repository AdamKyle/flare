<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the nullable Markdown description to Game Maps.
     */
    public function up(): void
    {
        Schema::table('game_maps', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Remove the Markdown description from Game Maps.
     */
    public function down(): void
    {
        Schema::table('game_maps', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
