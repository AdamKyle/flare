<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simplify Game Races to identity/presentation only, dropping racial gameplay modifiers.
     */
    public function up(): void
    {
        Schema::table('game_races', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->string('image_path')->nullable()->after('description');
        });

        Schema::table('game_races', function (Blueprint $table): void {
            $table->dropColumn([
                'str_mod',
                'dur_mod',
                'dex_mod',
                'chr_mod',
                'int_mod',
                'agi_mod',
                'focus_mod',
                'accuracy_mod',
                'dodge_mod',
                'defense_mod',
                'looting_mod',
            ]);
        });
    }

    /**
     * Restore the dropped racial gameplay modifier schema; historical modifier data is not restored.
     */
    public function down(): void
    {
        Schema::table('game_races', function (Blueprint $table): void {
            $table->integer('str_mod')->default(0);
            $table->integer('dur_mod')->default(0);
            $table->integer('dex_mod')->default(0);
            $table->integer('chr_mod')->default(0);
            $table->integer('int_mod')->default(0);
            $table->integer('agi_mod')->default(0);
            $table->integer('focus_mod')->default(0);
            $table->decimal('accuracy_mod', 5, 4)->default(0.0000);
            $table->decimal('dodge_mod', 5, 4)->default(0.0000);
            $table->decimal('defense_mod', 5, 4)->default(0.0000);
            $table->decimal('looting_mod', 5, 4)->default(0.0000);
        });

        Schema::table('game_races', function (Blueprint $table): void {
            $table->dropColumn(['description', 'image_path']);
        });
    }
};
